<?php

namespace SMW\SQLStore\QueryEngine\Fulltext;

use SMW\SQLStore\CompositePropertyTableDiffIterator;
use SMW\SQLStore\ChangeOp\TableChangeOp;
use SMW\MediaWiki\Database;
use SMW\DeferredRequestDispatchManager;
use SMW\DIWikiPage;
use SMW\SQLStore\ChangeOp\TempChangeOpStore;
use Psr\Log\LoggerInterface;
use Psr\Log\LoggerAwareInterface;
use SMW\Utils\Timer;

/**
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class TextByChangeUpdater implements LoggerAwareInterface {

	/**
	 * @var Database
	 */
	private $connection;

	/**
	 * @var SearchTableUpdater
	 */
	private $searchTableUpdater;

	/**
	 * @var TextSanitizer
	 */
	private $textSanitizer;

	/**
	 * @var TempChangeOpStore
	 */
	private $tempChangeOpStore;

	/**
	 * @var LoggerInterface
	 */
	private $logger;

	/**
	 * @var boolean
	 */
	private $asDeferredUpdate = true;

	/**
	 * @var boolean
	 */
	private $isCommandLineMode = false;

	/**
	 * @since 2.5
	 *
	 * @param Database $connection
	 * @param SearchTableUpdater $searchTableUpdater
	 * @param TextSanitizer $textSanitizer
	 * @param TempChangeOpStore $tempChangeOpStore
	 */
	public function __construct( Database $connection, SearchTableUpdater $searchTableUpdater, TextSanitizer $textSanitizer, TempChangeOpStore $tempChangeOpStore ) {
		$this->connection = $connection;
		$this->searchTableUpdater = $searchTableUpdater;
		$this->textSanitizer = $textSanitizer;
		$this->tempChangeOpStore = $tempChangeOpStore;
	}

	/**
	 * @see LoggerAwareInterface::setLogger
	 *
	 * @since 2.5
	 *
	 * @param LoggerInterface $logger
	 */
	public function setLogger( LoggerInterface $logger ) {
		$this->logger = $logger;
	}

	/**
	 * @note See comments in the DefaultSettings.php on the smwgFulltextDeferredUpdate setting
	 *
	 * @since 2.5
	 *
	 * @param boolean $asDeferredUpdate
	 */
	public function asDeferredUpdate( $asDeferredUpdate ) {
		$this->asDeferredUpdate = (bool)$asDeferredUpdate;
	}

	/**
	 * When running from commandLine, push updates directly to avoid overhead when
	 * it is known that within that mode transactions are FIFO (i.e. the likelihood
	 * for race conditions of unfinished updates are diminishable).
	 *
	 * @since 2.5
	 *
	 * @param boolean $isCommandLineMode
	 */
	public function isCommandLineMode( $isCommandLineMode ) {
		$this->isCommandLineMode = (bool)$isCommandLineMode;
	}

	/**
	 * @see SMW::SQLStore::AfterDataUpdateComplete hook
	 *
	 * @since 2.5
	 *
	 * @param CompositePropertyTableDiffIterator $compositePropertyTableDiffIterator
	 * @param DeferredRequestDispatchManager $deferredRequestDispatchManager
	 */
	public function pushUpdates( CompositePropertyTableDiffIterator $compositePropertyTableDiffIterator, DeferredRequestDispatchManager $deferredRequestDispatchManager ) {

		if ( !$this->searchTableUpdater->isEnabled() ) {
			return;
		}

		// Update within the same transaction as started by SMW::SQLStore::AfterDataUpdateComplete
		if ( !$this->asDeferredUpdate || $this->isCommandLineMode ) {
			return $this->pushUpdatesFromPropertyTableDiff( $compositePropertyTableDiffIterator );
		}

		if ( !$this->canPostUpdate( $compositePropertyTableDiffIterator ) ) {
			return;
		}

		$slot = $this->tempChangeOpStore->createSlotFrom(
			$compositePropertyTableDiffIterator
		);

		$deferredRequestDispatchManager->dispatchFulltextSearchTableUpdateJobWith(
			$compositePropertyTableDiffIterator->getSubject()->getTitle(),
			array(
				'slot:id' => $slot
			)
		);
	}

	/**
	 * @see SearchTableUpdateJob::run
	 *
	 * @since 2.5
	 *
	 * @param array|boolan $parameters
	 */
	public function pushUpdatesFromJobParameters( $parameters ) {

		if ( !$this->searchTableUpdater->isEnabled() || !isset( $parameters['slot:id'] ) || $parameters['slot:id'] === false ) {
			return;
		}

		Timer::start( __METHOD__ );

		$tableChangeOps = $this->tempChangeOpStore->newTableChangeOpsFrom(
			$parameters['slot:id']
		);

		foreach ( $tableChangeOps as $tableChangeOp ) {
			$this->doUpdateFromTableChangeOp( $tableChangeOp );
		}

		$this->tempChangeOpStore->delete( $parameters['slot:id'] );

		$this->log( __METHOD__ . ' procTime (sec): '. Timer::getElapsedTime( __METHOD__, 5 ) );
	}

	/**
	 * @since 2.5
	 *
	 * @param CompositePropertyTableDiffIterator $compositePropertyTableDiffIterator
	 */
	public function pushUpdatesFromPropertyTableDiff( CompositePropertyTableDiffIterator $compositePropertyTableDiffIterator ) {

		if ( !$this->searchTableUpdater->isEnabled() ) {
			return;
		}

		Timer::start( __METHOD__ );

		foreach ( $compositePropertyTableDiffIterator->getTableChangeOps() as $tableChangeOp ) {
			$this->doUpdateFromTableChangeOp( $tableChangeOp );
		}

		$this->log( __METHOD__ . ' procTime (sec): '. Timer::getElapsedTime( __METHOD__, 5 ) );
	}

	private function doUpdateFromTableChangeOp( TableChangeOp $tableChangeOp ) {

		$deletes = array();
		$inserts = array();

		foreach ( $tableChangeOp->getFieldChangeOps( 'insert' ) as $insertFieldChangeOp ) {

			// Copy fields temporarily
			if ( $tableChangeOp->isFixedPropertyOp() ) {
				$insertFieldChangeOp->set( 'p_id', $tableChangeOp->getFixedPropertyValueBy( 'p_id' ) );
			}

			$this->doAggregateFromFieldChangeOp( TableChangeOp::OP_INSERT, $insertFieldChangeOp, $inserts );
		}

		foreach ( $tableChangeOp->getFieldChangeOps( 'delete' ) as $deleteFieldChangeOp ) {

			// Copy fields temporarily
			if ( $tableChangeOp->isFixedPropertyOp() ) {
				$deleteFieldChangeOp->set( 'p_id', $tableChangeOp->getFixedPropertyValueBy( 'p_id' ) );
			}

			$this->doAggregateFromFieldChangeOp( TableChangeOp::OP_DELETE, $deleteFieldChangeOp, $deletes );
		}

		$this->doUpdateOnAggregatedValues( $inserts, $deletes );
	}

	private function doAggregateFromFieldChangeOp( $type, $fieldChangeOp, &$aggregate ) {

		$searchTable = $this->searchTableUpdater->getSearchTable();

		// Exempted property -> out
		if ( !$fieldChangeOp->has( 'p_id' ) || $searchTable->isExemptedPropertyById( $fieldChangeOp->get( 'p_id' ) ) ) {
			return;
		}

		if ( !$fieldChangeOp->has( 'o_blob' ) && !$fieldChangeOp->has( 'o_hash' ) && !$fieldChangeOp->has( 'o_serialized' ) && !$fieldChangeOp->has( 'o_id' ) ) {
			return;
		}

		// Re-map (url type)
		if ( $fieldChangeOp->has( 'o_serialized' ) ) {
			$fieldChangeOp->set( 'o_blob', $fieldChangeOp->get( 'o_serialized' ) );
		}

		// Re-map (wpg type)
		if ( $fieldChangeOp->has( 'o_id' ) ) {
			$dataItem = $searchTable->getDataItemById( $fieldChangeOp->get( 'o_id' ) );
			$fieldChangeOp->set( 'o_blob', $dataItem !== null ? $dataItem->getSortKey() : 'NO_TEXT' );
		}

		// Build a temporary stable key for the diff match
		$key = $fieldChangeOp->get( 's_id' ) . ':' . $fieldChangeOp->get( 'p_id' );

		// If the blob value is empty then the DIHandler has put any text < 72
		// into the hash field
		$text = $fieldChangeOp->get( 'o_blob' );

		if ( $text === null || $text === '' ) {
			$text = $fieldChangeOp->get( 'o_hash' );
		}

		if ( !isset( $aggregate[$key] ) ) {
			$aggregate[$key] = $type === TableChangeOp::OP_DELETE ? array() : '';
		}

		// Concatenate the inserts but keep the deletes separate to allow
		// for them to be removed individually
		if ( $type === TableChangeOp::OP_INSERT ) {
			$aggregate[$key] = trim( $aggregate[$key] . ' ' . trim( $text ) );
		} elseif ( $type === TableChangeOp::OP_DELETE ) {
			$aggregate[$key][] = $this->textSanitizer->sanitize( $text );
		}
	}

	private function doUpdateOnAggregatedValues( $inserts, $deletes ) {
		// Remove any "deletes" first
		$this->doUpdateOnDeletes( $deletes );
		$this->doUpdateOnInserts( $inserts );
	}

	private function doUpdateOnDeletes( $deletes ) {

		foreach ( $deletes as $key => $values ) {
			list( $sid, $pid ) = explode( ':', $key, 2 );

			$text = $this->searchTableUpdater->read(
				$sid,
				$pid
			);

			if ( $text === false ) {
				continue;
			}

			foreach ( $values as $k => $value ) {
				$text = str_replace( $value, '', $text );
			}

			//$this->log( "Delete update on $sid with $pid" );

			$this->searchTableUpdater->update( $sid, $pid, $text );
		}
	}

	private function doUpdateOnInserts( $inserts ) {

		foreach ( $inserts as $key => $value ) {
			list( $sid, $pid ) = explode( ':', $key, 2 );

			if ( $value === '' ) {
				continue;
			}

			$text = $this->searchTableUpdater->read(
				$sid,
				$pid
			);

			if ( $text === false ) {
				$this->searchTableUpdater->insert( $sid, $pid );
			}

			//$this->log( "Insert update on $sid with $pid " );

			$this->searchTableUpdater->update( $sid, $pid, $text . ' ' . $value );
		}
	}

	private function canPostUpdate( $compositePropertyTableDiffIterator ) {

		$searchTable = $this->searchTableUpdater->getSearchTable();
		$canPostUpdate = false;

		// Find out whether we should actual initiate an update
		foreach ( $compositePropertyTableDiffIterator->getCombinedIdListOfChangedEntities() as $id ) {
			if ( ( $dataItem = $searchTable->getDataItemById( $id ) ) instanceof DIWikiPage && $dataItem->getNamespace() === SMW_NS_PROPERTY ) {
				if ( !$searchTable->isExemptedPropertyById( $id ) ) {
					$canPostUpdate = true;
					break;
				}
			}
		}

		return $canPostUpdate;
	}

	private function log( $message, $context = array() ) {

		if ( $this->logger === null ) {
			return;
		}

		$this->logger->info( $message, $context );
	}

}

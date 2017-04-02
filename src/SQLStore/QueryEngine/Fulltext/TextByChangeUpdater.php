<?php

namespace SMW\SQLStore\QueryEngine\Fulltext;

use SMW\SQLStore\CompositePropertyTableDiffIterator;
use SMW\SQLStore\ChangeOp\TableChangeOp;
use SMW\MediaWiki\Database;
use SMW\DeferredRequestDispatchManager;
use SMW\DIWikiPage;
use SMWDIBlob as DIBlob;
use SMWDIUri as DIUri;
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

		Timer::start( __METHOD__ );

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

		$this->log( __METHOD__ . ' (procTime in sec: '. Timer::getElapsedTime( __METHOD__, 5 ) . ')' );
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

		$compositePropertyTableDiffIterator = $this->tempChangeOpStore->newCompositePropertyTableDiffIterator(
			$parameters['slot:id']
		);

		if ( $compositePropertyTableDiffIterator === null ) {
			return $this->log( __METHOD__ . ' Failed compositePropertyTableDiff from slot: ' . $parameters['slot:id'] );
		}

		$this->pushUpdatesFromPropertyTableDiff( $compositePropertyTableDiffIterator );

		$this->tempChangeOpStore->delete(
			$parameters['slot:id']
		);

		$this->log( __METHOD__ . ' (procTime in sec: '. Timer::getElapsedTime( __METHOD__, 5 ) . ')' );
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

		$dataChangeOps = $compositePropertyTableDiffIterator->getDataChangeOps();
		$diffChangeOps = $compositePropertyTableDiffIterator->getTableChangeOps();

		$insertIds = $compositePropertyTableDiffIterator->getListOfChangedEntityIdsByType(
			$compositePropertyTableDiffIterator::TYPE_INSERT
		);

		$updates = array();

		// Ensure that any delete operation is being accounted for to avoid that
		// removed value annotation remain
		if ( $diffChangeOps !== array() ) {
			$this->doDeleteFromTableChangeOps( $diffChangeOps );
		}

		if ( $insertIds === array() ) {
			return;
		}

		// Build a composite of replacements where a change occured, this my
		// contain some false positives
		foreach ( $dataChangeOps as $dataChangeOp ) {
			$this->doCreateCompositeUpdate( $dataChangeOp, $updates, $insertIds );
		}

		foreach ( $updates as $key => $value ) {
			list( $sid, $pid ) = explode( ':', $key, 2 );

			if ( $this->searchTableUpdater->exists( $sid, $pid ) === false ) {
				$this->searchTableUpdater->insert( $sid, $pid );
			}

			$this->searchTableUpdater->update(
				$sid,
				$pid,
				$value
			);
		}

		$this->log( __METHOD__ . ' (procTime in sec: '. Timer::getElapsedTime( __METHOD__, 5 ) . ')' );
	}

	private function doCreateCompositeUpdate( TableChangeOp $dataChangeOp, &$updates, $ids ) {

		$searchTable = $this->searchTableUpdater->getSearchTable();

		foreach ( $dataChangeOp->getFieldChangeOps() as $fieldChangeOp ) {

			// Exempted property -> out
			if ( !$fieldChangeOp->has( 'p_id' ) || $searchTable->isExemptedPropertyById( $fieldChangeOp->get( 'p_id' ) ) ) {
				continue;
			}

			$sid = $fieldChangeOp->get( 's_id' );
			$pid = $fieldChangeOp->get( 'p_id' );

			// Check whether changes occured for a matchable pair of subject/property
			// IDs
			if ( !isset( $ids[$sid] ) && !isset( $ids[$pid] ) ) {
				continue;
			}

			if ( !$fieldChangeOp->has( 'o_blob' ) && !$fieldChangeOp->has( 'o_hash' ) && !$fieldChangeOp->has( 'o_serialized' ) && !$fieldChangeOp->has( 'o_id' ) ) {
				continue;
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

			// If the blob value is empty then the DIHandler has put any text < 72
			// into the hash field
			$text = $fieldChangeOp->get( 'o_blob' );
			$key = $sid . ':' . $pid;

			if ( $text === null || $text === '' ) {
				$text = $fieldChangeOp->get( 'o_hash' );
			}

			$updates[$key] = !isset( $updates[$key] ) ? $text : $updates[$key] . ' ' . $text;
		}
	}

	private function doDeleteFromTableChangeOps( array $tableChangeOps ) {
		foreach ( $tableChangeOps as $tableChangeOp ) {
			$this->doDeleteFromTableChangeOp( $tableChangeOp );
		}
	}

	private function doDeleteFromTableChangeOp( TableChangeOp $tableChangeOp ) {

		foreach ( $tableChangeOp->getFieldChangeOps( 'delete' ) as $fieldChangeOp ) {

			// Replace s_id for subobjects etc. with the o_id
			if ( $tableChangeOp->isFixedPropertyOp() ) {
				$fieldChangeOp->set( 's_id', $fieldChangeOp->has( 'o_id' ) ? $fieldChangeOp->get( 'o_id' ) : $fieldChangeOp->get( 's_id' ) );
				$fieldChangeOp->set( 'p_id', $tableChangeOp->getFixedPropertyValueBy( 'p_id' ) );
			}

			if ( !$fieldChangeOp->has( 'p_id' ) ) {
				continue;
			}

			$this->searchTableUpdater->delete(
				$fieldChangeOp->get( 's_id' ),
				$fieldChangeOp->get( 'p_id' )
			);
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

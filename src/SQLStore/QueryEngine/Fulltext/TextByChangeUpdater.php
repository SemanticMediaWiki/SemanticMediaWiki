<?php

namespace SMW\SQLStore\QueryEngine\Fulltext;

use SMW\SQLStore\CompositePropertyTableDiffIterator;
use SMW\SQLStore\ChangeOp\TableChangeOp;
use SMW\MediaWiki\Database;
use SMW\DeferredRequestDispatchManager;
use SMW\DIWikiPage;

/**
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class TextByChangeUpdater {

	/**
	 * @var SearchTableUpdater
	 */
	private $searchTableUpdater;

	/**
	 * @var Database
	 */
	private $connection;

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
	 * @param SearchTableUpdater $searchTableUpdater
	 * @param Database $connection
	 */
	public function __construct( SearchTableUpdater $searchTableUpdater, Database $connection ) {
		$this->searchTableUpdater = $searchTableUpdater;
		$this->connection = $connection;
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
	 * @param DIWikiPage $subject
	 * @param CompositePropertyTableDiffIterator $compositePropertyTableDiffIterator
	 * @param DeferredRequestDispatchManager $deferredRequestDispatchManager
	 */
	public function pushUpdates( DIWikiPage $subject, CompositePropertyTableDiffIterator $compositePropertyTableDiffIterator, DeferredRequestDispatchManager $deferredRequestDispatchManager ) {

		if ( !$this->searchTableUpdater->isEnabled() ) {
			return;
		}

		// Update within the same transaction as started by SMW::SQLStore::AfterDataUpdateComplete
		if ( !$this->asDeferredUpdate || $this->isCommandLineMode ) {
			return $this->pushUpdatesFromPropertyTableDiff( $compositePropertyTableDiffIterator );
		}

		$deferredRequestDispatchManager->dispatchSearchTableUpdateJobFor(
			$subject->getTitle(),
			$this->buildSearchTableUpdateJobParametersFrom( $compositePropertyTableDiffIterator )
		);
	}

	/**
	 * @since 2.5
	 *
	 * @param CompositePropertyTableDiffIterator $compositePropertyTableDiffIterator
	 *
	 * @return array
	 */
	public function buildSearchTableUpdateJobParametersFrom( CompositePropertyTableDiffIterator $compositePropertyTableDiffIterator ) {
		return array(
			'diff' => $compositePropertyTableDiffIterator->getOrderedDiffByTable()
		);
	}

	/**
	 * @see SearchTableUpdateJob::run
	 *
	 * @since 2.5
	 *
	 * @param array $parameters
	 */
	public function pushUpdatesFromJobParameters( array $parameters ) {

		if ( !$this->searchTableUpdater->isEnabled() || !isset( $parameters['diff'] ) || $parameters['diff'] === false ) {
			return;
		}

		foreach ( $parameters['diff'] as $tableName => $changeOp ) {
			$this->doUpdateFromTableChangeOp( new TableChangeOp( $tableName, $changeOp ) );
		}
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

		$start = microtime( true );

		foreach ( $compositePropertyTableDiffIterator->getTableChangeOps() as $tableChangeOp ) {
			$this->doUpdateFromTableChangeOp( $tableChangeOp );
		}


		wfDebugLog( 'smw', __METHOD__ . ' procTime (sec): '. round( ( microtime( true ) - $start ), 5 ) );
	}

	private function doUpdateFromTableChangeOp( TableChangeOp $tableChangeOp ) {

		$deletes = array();
		$inserts = array();

		foreach ( $tableChangeOp->getFieldChangeOps( 'insert' ) as $insertFieldChangeOp ) {

			// Copy fields temporarily
			if ( $tableChangeOp->isFixedPropertyOp() ) {
				$insertFieldChangeOp->set( 'p_id', $tableChangeOp->getFixedPropertyValueFor( 'p_id' ) );
			}

			$this->doAggregateFromFieldChangeOp( TableChangeOp::OP_INSERT, $insertFieldChangeOp, $inserts );
		}

		foreach ( $tableChangeOp->getFieldChangeOps( 'delete' ) as $deleteFieldChangeOp ) {

			// Copy fields temporarily
			if ( $tableChangeOp->isFixedPropertyOp() ) {
				$deleteFieldChangeOp->set( 'p_id', $tableChangeOp->getFixedPropertyValueFor( 'p_id' ) );
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

		// Only text components
		if ( !$fieldChangeOp->has( 'o_blob' ) && !$fieldChangeOp->has( 'o_hash' ) && !$fieldChangeOp->has( 'o_serialized' ) ) {
			return;
		}

		// Re-map (url type)
		if ( $fieldChangeOp->has( 'o_serialized' ) ) {
			$fieldChangeOp->set( 'o_blob', $fieldChangeOp->get( 'o_serialized' ) );
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
			$aggregate[$key][] = $searchTable->getTextSanitizer()->sanitize( $text );
		}
	}

	private function doUpdateOnAggregatedValues( $inserts, $deletes ) {

		// Remove any "deletes" first
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

			//wfDebugLog( 'smw', "Delete update on $sid with $pid" );

			$this->searchTableUpdater->update( $sid, $pid, $text );
		}

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

			//wfDebugLog( 'smw', "Insert update on $sid with $pid " );

			$this->searchTableUpdater->update( $sid, $pid, $text . ' ' . $value );
		}
	}

}

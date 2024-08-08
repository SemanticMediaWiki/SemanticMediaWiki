<?php

namespace SMW\SQLStore\QueryEngine\Fulltext;

use Onoi\MessageReporter\MessageReporter;
use Onoi\MessageReporter\MessageReporterFactory;
use SMW\DIProperty;
use SMW\MediaWiki\Database;
use SMWDataItem as DataItem;
use SMW\Utils\CliMsgFormatter;

/**
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class SearchTableRebuilder {

	/**
	 * @var Database
	 */
	private $connection;

	/**
	 * @var SearchTableUpdater
	 */
	private $searchTableUpdater;

	/**
	 * @var MessageReporter
	 */
	private $messageReporter;

	/**
	 * @var boolean
	 */
	private $reportVerbose = false;

	/**
	 * @var boolean
	 */
	private $optimization = false;

	/**
	 * @var array
	 */
	private $skippedTables = [];

	/**
	 * @since 2.5
	 *
	 * @param SearchTableUpdater $searchTableUpdater
	 * @param Database $connection
	 */
	public function __construct( Database $connection, SearchTableUpdater $searchTableUpdater ) {
		$this->connection = $connection;
		$this->searchTableUpdater = $searchTableUpdater;
		$this->messageReporter = MessageReporterFactory::getInstance()->newNullMessageReporter();
	}

	/**
	 * @since 2.5
	 *
	 * @return SearchTable
	 */
	public function getSearchTable() {
		return $this->searchTableUpdater->getSearchTable();
	}

	/**
	 * @since 2.5
	 *
	 * @param MessageReporter $messageReporter
	 */
	public function setMessageReporter( MessageReporter $messageReporter ) {
		$this->messageReporter = $messageReporter;
	}

	/**
	 * @since 2.5
	 *
	 * @param boolean $reportVerbose
	 */
	public function reportVerbose( $reportVerbose ) {
		$this->reportVerbose = (bool)$reportVerbose;
	}

	/**
	 * @since 2.5
	 *
	 * @param boolean $optimization
	 */
	public function requestOptimization( $optimization ) {
		$this->optimization = (bool)$optimization;
	}

	/**
	 * @since 3.2
	 *
	 * @return boolean
	 */
	public function canRebuild() {
		return $this->searchTableUpdater->isEnabled();
	}

	/**
	 * @see RebuildFulltextSearchTable::execute
	 *
	 * @since 2.5
	 *
	 * @return boolean
	 */
	public function rebuild() {
		if ( !$this->canRebuild() ) {
			return;
		}

		if ( $this->optimization ) {
			return $this->doOptimize();
		}

		$this->doRebuild();

		return true;
	}

	/**
	 * @since 3.0
	 */
	public function flushTable() {
		if ( $this->searchTableUpdater->isEnabled() ) {
			$this->searchTableUpdater->flushTable();
		}
	}

	/**
	 * @since 2.5
	 *
	 * @return array
	 */
	public function getQualifiedTableList() {
		$tableList = [];

		if ( !$this->searchTableUpdater->isEnabled() ) {
			return $tableList;
		}

		foreach ( $this->searchTableUpdater->getPropertyTables() as $proptable ) {

			if ( !$this->getSearchTable()->isValidByType( $proptable->getDiType() ) ) {
				continue;
			}

			$tableList[] = $proptable->getName();
		}

		return $tableList;
	}

	/**
	 * @since 2.5
	 *
	 * @param string $tableName
	 */
	public function rebuildByTable( $tableName ) {
		foreach ( $this->searchTableUpdater->getPropertyTables() as $proptable ) {
			if ( $proptable->getName() === $tableName && $this->getSearchTable()->isValidByType( $proptable->getDiType() ) ) {
				$this->doRebuildByPropertyTable( $proptable );
			}
		}
	}

	private function doOptimize() {
		$cliMsgFormatter = new CliMsgFormatter();

		$this->reportMessage(
			$cliMsgFormatter->section( 'optimization', 3, '-', true )
		);

		$text = [
			"Running table optimization (Depending on the SQL back-end",
			"this operation may lock the table and suspend any inserts or",
			"deletes during the process.)"
		];

		$this->reportMessage(
			"\n" . $cliMsgFormatter->wordwrap( $text ) . "\n"
		);

		if ( $this->searchTableUpdater->optimize() ) {
			$this->reportMessage( "\n   ... optimization has finished.\n" );
		} else {
			$this->reportMessage( "\nThe SQL back-end does not support this operation.\n" );
		}

		return true;
	}

	private function doRebuild() {
		$cliMsgFormatter = new CliMsgFormatter();
		$propertyTables = [];

		$this->reportMessage( "\nProcessing table(s) ..." );

		$this->reportMessage(
			"\n" . $cliMsgFormatter->firstCol( "... purging the index table ...", 3 )
		);

		$this->searchTableUpdater->flushTable();

		$this->reportMessage(
			$cliMsgFormatter->secondCol( CliMsgFormatter::OK )
		);

		$this->reportMessage(
			$cliMsgFormatter->firstCol( "... counting suitable table(s) ...", 3 )
		);

		foreach ( $this->searchTableUpdater->getPropertyTables() as $proptable ) {

			// Only care for Blob/Uri tables
			if ( !$this->getSearchTable()->isValidByType( $proptable->getDiType() ) ) {
				$this->skippedTables[$proptable->getName()] = '[INVALID]';
				continue;
			}

			$propertyTables[] = $proptable;
		}

		$this->reportMessage(
			$cliMsgFormatter->secondCol( count( $propertyTables ) )
		);

		foreach ( $propertyTables as $propertyTable ) {
			$this->doRebuildByPropertyTable( $propertyTable );
		}

		$this->reportMessage( "   ... done.\n" );

		$this->reportMessage(
			$cliMsgFormatter->section( "Unindexed table(s)", 3, '-', true ),
			$this->reportVerbose
		);

		$text = [
			"[INVALID] refers to an invalid `DataItem` type, [EMPTY] describes",
			"a table to contain no data, [EXEMPT] is exempted from processing"
		];

		$this->reportMessage(
			"\n" . $cliMsgFormatter->wordwrap( $text ) . "\n",
			$this->reportVerbose
		);

		$this->reportMessage(
			"\nList unprocessed table(s) ...\n",
			$this->reportVerbose
		);

		foreach ( $this->skippedTables as $tableName => $reason ) {
			$this->reportMessage(
				$cliMsgFormatter->twoCols( "... $tableName", $reason, 3, '.' ),
				$this->reportVerbose
			);
		}
	}

	private function doRebuildByPropertyTable( $proptable ) {
		$searchTable = $this->getSearchTable();

		if ( $proptable->getDiType() === DataItem::TYPE_URI ) {
			$fetchFields = [ 's_id', 'p_id', 'o_blob', 'o_serialized' ];
		} elseif ( $proptable->getDiType() === DataItem::TYPE_WIKIPAGE ) {
			$fetchFields = [ 's_id', 'p_id', 'o_id' ];
		} else {
			$fetchFields = [ 's_id', 'p_id', 'o_blob', 'o_hash' ];
		}

		$table = $proptable->getName();
		$pid = '';

		// Fixed tables don't have a p_id column therefore get it
		// from the ID TABLE
		if ( $proptable->isFixedPropertyTable() ) {
			unset( $fetchFields[1] ); // p_id

			$property = new DIProperty( $proptable->getFixedProperty() );

			if ( $property->getLabel() === '' ) {
				return $this->skippedTables[$table] = '[FIXED]';
			}

			$pid = $searchTable->getIdByProperty(
				$property
			);

			if ( $searchTable->isExemptedPropertyById( $pid ) ) {
				return $this->skippedTables[$table] = '[EXEMPT]';
			}
		}

		$rows = $this->connection->select(
			$table,
			$fetchFields,
			[],
			__METHOD__
		);

		if ( $rows === false || $rows === null ) {
			return $this->skippedTables[$table] = '[EMPTY]';
		}

		$this->doRebuildFromRows( $searchTable, $table, $pid, $rows );
	}

	private function doRebuildFromRows( $searchTable, $table, $pid, $rows ) {
		$cliMsgFormatter = new CliMsgFormatter();

		$i = 0;
		$expected = $rows->numRows();

		if ( $expected == 0 ) {
			return $this->skippedTables[$table] = '[EMPTY]';
		}

		foreach ( $rows as $row ) {

			$sid = $row->s_id;
			$pid = !isset( $row->p_id ) ? $pid : $row->p_id;

			$indexableText = $this->getIndexableTextFromRow(
				$searchTable,
				$row
			);

			if (
				$searchTable->isExemptedPropertyById( $pid ) ||
				!$searchTable->hasMinTokenLength( $indexableText ) ) {
				continue;
			}

			$progress = $cliMsgFormatter->progressCompact( ++$i, $expected );

			$this->reportMessage(
				$cliMsgFormatter->twoColsOverride( "... {$table} ...", $progress, 7 )
			);

			$text = $this->searchTableUpdater->read( $sid, $pid );

			// Unknown, so let's create the row
			if ( $text === false ) {
				$this->searchTableUpdater->insert( $sid, $pid );
			}

			$this->searchTableUpdater->update( $sid, $pid, trim( $text ?? '' ) . ' ' . $indexableText );
		}

		$this->reportMessage( "\n" );
	}

	private function reportMessage( $message, $verbose = true ) {
		if ( $verbose ) {
			$this->messageReporter->reportMessage( $message );
		}
	}

	private function getIndexableTextFromRow( $searchTable, $row ) {
		$indexableText = '';

		// Page, Uri, or blob?
		if ( isset( $row->o_id ) ) {
			$dataItem = $searchTable->getDataItemById( $row->o_id );
			$indexableText = $dataItem instanceof DataItem ? $dataItem->getSortKey() : '';
		} elseif ( isset( $row->o_serialized ) ) {
			$indexableText = $row->o_blob === null ? $row->o_serialized : $row->o_blob;
		} elseif ( isset( $row->o_blob ) ) {
			$indexableText = $row->o_blob;
		} elseif ( isset( $row->o_hash ) ) {
			$indexableText = $row->o_hash;
		}

		return trim( $indexableText );
	}

}

<?php

namespace SMW\SQLStore\QueryEngine\Fulltext;

use Onoi\MessageReporter\MessageReporter;
use Onoi\MessageReporter\MessageReporterFactory;
use SMW\MediaWiki\Database;
use SMW\DIProperty;
use SMWDataItem as DataItem;
use SMWDIBlob as DIBlob;

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
	private $skippedTables = array();

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
	 * @see RebuildFulltextSearchTable::execute
	 *
	 * @since 2.5
	 *
	 * @return boolean
	 */
	public function rebuild() {

		if ( !$this->searchTableUpdater->isEnabled() ) {
			return $this->reportMessage( "\n" . "FullText search indexing is not enabled or supported." ."\n" );
		}

		if ( $this->optimization ) {
			return $this->doOptimize();
		}

		$this->doRebuild();

		return true;
	}

	/**
	 * @since 2.5
	 *
	 * @return array
	 */
	public function getQualifiedTableList() {

		$tableList = array();

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

		$this->reportMessage(
			"\n## Optimization\n"
		);

		$this->reportMessage(
			"\nRunning table optimization (Depending on the SQL back-end " .
			"\nthis operation may lock the table and suspend any inserts or" .
			"\ndeletes during the process.)\n"
		);

		if ( $this->searchTableUpdater->optimize() ) {
			$this->reportMessage( "\nOptimization has finished.\n" );
		} else {
			$this->reportMessage( "\nThe SQL back-end does not support this operation.\n" );
		}

		return true;
	}

	private function doRebuild() {

		$this->reportMessage(
			"\n## Indexing\n"
		);

		$this->reportMessage(
			"\nThe entire index table is going to be purged first and \n" .
			"it may take a moment before the rebuild is completed due to\n" .
			"dependencies on table content including varying options.\n"
		);

		$this->searchTableUpdater->flushTable();

		$this->reportMessage( "\n" . "The index table was purged." ."\n" );
		$this->reportMessage( "\n" . "Rebuilding the content from (rows finished/expected):" ."\n\n" );

		foreach ( $this->searchTableUpdater->getPropertyTables() as $proptable ) {

			// Only care for Blob/Uri tables
			if ( !$this->getSearchTable()->isValidByType( $proptable->getDiType() ) ) {
				$this->skippedTables[$proptable->getName()] = 'Not a valid DI type.';
				continue;
			}

			$this->doRebuildByPropertyTable( $proptable );
		}

		$this->reportMessage(
			"\n## Notes\n",
			$this->reportVerbose
		);

		$this->reportMessage(
			"\n" . "Table(s) not used for indexing:" ."\n\n",
			$this->reportVerbose
		);

		foreach ( $this->skippedTables as $tableName => $reason ) {
			$this->reportMessage( "\r". sprintf( "%-36s%s", "- {$tableName}", $reason . "\n" ), $this->reportVerbose );
		}
	}

	private function doRebuildByPropertyTable( $proptable ) {

		$searchTable = $this->getSearchTable();

		if ( $proptable->getDiType() === DataItem::TYPE_URI ) {
			$fetchFields = array( 's_id', 'p_id', 'o_blob', 'o_serialized' );
		} elseif ( $proptable->getDiType() === DataItem::TYPE_WIKIPAGE ) {
			$fetchFields = array( 's_id', 'p_id', 'o_id' );
		} else {
			$fetchFields = array( 's_id', 'p_id', 'o_blob', 'o_hash' );
		}

		$table = $proptable->getName();
		$pid = '';

		// Fixed tables don't have a p_id column therefore get it
		// from the ID TABLE
		if ( $proptable->isFixedPropertyTable() ) {
			unset( $fetchFields[1] ); // p_id

			$property = new DIProperty( $proptable->getFixedProperty() );

			if ( $property->getLabel() === '' ) {
				return $this->skippedTables[$table] = 'Fixed property is ' . $property->getKey() . ' invalid.';
			}

			$pid = $searchTable->getPropertyIdBy(
				$property
			);

			if ( $searchTable->isExemptedPropertyById( $pid ) ) {
				return $this->skippedTables[$table] = 'Fixed property table that belongs to the ' . $proptable->getFixedProperty() . ' exempted property.';
			}
		}

		$rows = $this->connection->select(
			$table,
			$fetchFields,
			array(),
			__METHOD__
		);

		if ( $rows === false || $rows === null ) {
			return $this->skippedTables[$table] = 'Empty table.';
		}

		$this->doRebuildFromRows( $searchTable, $table, $pid, $rows );
	}

	private function doRebuildFromRows( $searchTable, $table, $pid, $rows ) {

		$i = 0;
		$expected = $rows->numRows();

		if ( $expected == 0 ) {
			return $this->skippedTables[$table] = 'Empty table.';
		}

		foreach ( $rows as $row ) {
			$i++;

			$sid = $row->s_id;
			$pid = !isset( $row->p_id ) ? $pid : $row->p_id;

			$indexableText = $this->getIndexableTextFromRow(
				$searchTable,
				$row
			);

			if ( $searchTable->isExemptedPropertyById( $pid ) || !$searchTable->hasMinTokenLength( $indexableText ) ) {
				continue;
			}

			$this->reportMessage(
				"\r". sprintf( "%-35s%s", "- {$table}", sprintf( "%4.0f%% (%s/%s)", ( $i / $expected ) * 100, $i, $expected ) )
			);

			$text = $this->searchTableUpdater->read( $sid, $pid );

			// Unkown, so let's create the row
			if ( $text === false ) {
				$this->searchTableUpdater->insert( $sid, $pid );
			}

			$this->searchTableUpdater->update( $sid, $pid, trim( $text ) . ' ' . $indexableText );
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
			$indexableText = $dataItem !== false ? $dataItem->getSortKey() : '';
		} elseif ( isset( $row->o_serialized ) ) {
			$indexableText = $row->o_blob === null ? $row->o_serialized : $row->o_blob;
		} else {
			$indexableText = $row->o_blob === null ? $row->o_hash : $row->o_blob;
		}

		return trim( $indexableText );
	}

}

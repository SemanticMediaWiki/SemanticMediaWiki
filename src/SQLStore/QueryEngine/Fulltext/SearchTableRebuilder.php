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
	 * @see RebuildFulltextSearchTable::execute
	 *
	 * @since 2.5
	 *
	 * @return boolean
	 */
	public function run() {

		if ( !$this->searchTableUpdater->isEnabled() ) {
			return $this->reportMessage( "\n" . "FullText search indexing is not enabled or supported." ."\n\n" );
		}

		$this->searchTableUpdater->flushTable();

		$this->reportMessage( "\n" . "The index table was purged." ."\n" );
		$this->reportMessage( "\n" . "Rebuilding the text index from (rows finished/expected):" ."\n\n" );

		foreach ( $this->searchTableUpdater->getPropertyTables() as $proptable ) {

			// Only care for Blob/Uri tables
			if ( $proptable->getDiType() !== DataItem::TYPE_BLOB && $proptable->getDiType() !== DataItem::TYPE_URI ) {
				$this->skippedTables[$proptable->getName()] = 'Not a blob or URI table type.';
				continue;
			}

			$this->doRebuildByPropertyTable( $proptable );
		}

		$this->reportMessage( "\n" . "Table(s) not used for indexing:" ."\n\n", $this->reportVerbose );

		foreach ( $this->skippedTables as $tableName => $reason ) {
			$this->reportMessage( "\r". sprintf( "%-36s%s", "- {$tableName}", $reason . "\n" ), $this->reportVerbose );
		}

		return true;
	}

	private function doRebuildByPropertyTable( $proptable ) {

		$searchTable = $this->getSearchTable();

		if ( $proptable->getDiType() === DataItem::TYPE_URI ) {
			$fetchFields = array( 's_id', 'p_id', 'o_serialized' );
		} else {
			$fetchFields = array( 's_id', 'p_id', 'o_blob', 'o_hash' );
		}

		$table = $proptable->getName();
		$pid = '';

		// Fixed tables don't have a p_id column therefore get it
		// from the ID TABLE
		if ( $proptable->isFixedPropertyTable() ) {
			unset( $fetchFields[1] ); // p_id

			$pid = $searchTable->getPropertyIdBy(
				new DIProperty( $proptable->getFixedProperty() )
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

			// Uri or blob?
			if ( isset( $row->o_serialized ) ) {
				$indexableText = $row->o_serialized;
			} else {
				$indexableText = $row->o_blob === null ? $row->o_hash : $row->o_blob;
			}

			if ( $searchTable->isExemptedPropertyById( $pid ) ) {
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

			$this->searchTableUpdater->update( $sid, $pid, trim( $text ) . ' ' . trim( $indexableText ) );
		}

		$this->reportMessage( "\n" );
	}

	private function reportMessage( $message, $verbose = true ) {
		if ( $verbose ) {
			$this->messageReporter->reportMessage( $message );
		}
	}

}

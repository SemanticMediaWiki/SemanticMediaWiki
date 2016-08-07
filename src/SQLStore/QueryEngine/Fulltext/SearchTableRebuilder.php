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
	 * @var SearchTableUpdater
	 */
	private $searchTableUpdater;

	/**
	 * @var Database
	 */
	private $connection;

	/**
	 * @var MessageReporter
	 */
	private $messageReporter;

	/**
	 * @since 2.5
	 *
	 * @param SearchTableUpdater $searchTableUpdater
	 * @param Database $connection
	 */
	public function __construct( SearchTableUpdater $searchTableUpdater, Database $connection ) {
		$this->searchTableUpdater = $searchTableUpdater;
		$this->connection = $connection;
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
	 * @see RebuildFulltextSearchTable::execute
	 *
	 * @since 2.5
	 *
	 * @return boolean
	 */
	public function run() {

		if ( !$this->searchTableUpdater->isEnabled() ) {
			return $this->messageReporter->reportMessage( "\n" . "FullText search indexing is not enabled or supported." ."\n\n" );
		}

		$this->searchTableUpdater->flushTable();

		$this->messageReporter->reportMessage( "\n" . "The index table was purged." ."\n" );
		$this->messageReporter->reportMessage( "\n" . "Rebuilding the text index from (rows finished/expected):" ."\n\n" );

		foreach ( $this->searchTableUpdater->getPropertyTables() as $proptable ) {

			// Only care for Blob/Uri tables
			if ( $proptable->getDiType() !== DataItem::TYPE_BLOB && $proptable->getDiType() !== DataItem::TYPE_URI ) {
				continue;
			}

			$this->doRebuildOnPropertyTable( $proptable );
		}

		return true;
	}

	private function doRebuildOnPropertyTable( $proptable ) {

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

			$pid = $searchTable->getPropertyID(
				new DIProperty( $proptable->getFixedProperty() )
			);

			if ( $searchTable->isExemptedPropertyById( $pid ) ) {
				return;
			}
		}

		$rows = $this->connection->select(
			$table,
			$fetchFields,
			array(),
			__METHOD__
		);

		if ( $rows === false || $rows === null ) {
			return;
		}

		$this->doRebuildFromRows( $searchTable, $table, $pid, $rows );
	}

	private function doRebuildFromRows( $searchTable, $table, $pid, $rows ) {

		$i = 0;
		$expected = $rows->numRows();

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

			$this->messageReporter->reportMessage(
				"\r". sprintf( "%-35s%s", "- {$table}", sprintf( "%4.0f%% (%s/%s)",( $i / $expected ) * 100, $i, $expected ) )
			);

			$text = $this->searchTableUpdater->read( $sid, $pid );

			// Unkown, so let's create the row
			if ( $text === false ) {
				$this->searchTableUpdater->insert( $sid, $pid );
			}

			$this->searchTableUpdater->update( $sid, $pid, trim( $text ) . ' ' . trim( $indexableText ) );
		}

		$this->messageReporter->reportMessage( "\n" );
	}

}

<?php

namespace SMW\Maintenance;

use SMW\SQLStore\SQLStore;
use Onoi\MessageReporter\MessageReporter;
use Onoi\MessageReporter\MessageReporterFactory;
use SMW\SearchField;

/**
 * @license GNU GPL v2+
 * @since 2.4
 *
 * @author mwjames
 */
class SearchFieldContentRebuilder {

	/**
	 * @var Store
	 */
	private $store = null;

	/**
	 * @var MessageReporter
	 */
	private $reporter;

	/**
	 * @since 2.4
	 *
	 * @param SQLStore $store
	 */
	public function __construct( SQLStore $store ) {
		$this->store = $store;
		$this->reporter = MessageReporterFactory::getInstance()->newNullMessageReporter();
	}

	/**
	 * @since  2.4
	 *
	 * @param MessageReporter $messageReporter
	 */
	public function setMessageReporter( MessageReporter $messageReporter ) {
		$this->reporter = $messageReporter;
	}

	/**
	 * @since 2.4
	 */
	public function rebuild() {
		$this->doRebuildContentForPropertyTables();
		$this->doRebuildContentForIdTable();
	}

	private function doRebuildContentForPropertyTables() {

		$this->reportMessage( "\n" . "Starting the property tables update ... " ."\n\n" );
		$connection = $this->store->getConnection( 'mw.db' );

		foreach ( $this->store->getPropertyTables() as $proptable ) {

			$diHandler = $this->store->getDataItemHandlerForDIType( $proptable->getDiType() );
			$fields = $diHandler->getTableFields();

			// Doesn't have a different search field so we leave ...
			if ( $diHandler->getExtraSearchIndexField() === $diHandler->getIndexField() ) {
				continue;
			}

			$this->doSelectRowsFor( $connection, $proptable, $diHandler );
		}
	}

	private function doSelectRowsFor( $connection, $proptable, $diHandler ) {

		// Fixed prop tables don't have a p_id field
		$fetchFields = $proptable->isFixedPropertyTable() ? array( 's_id' ) : array( 's_id', 'p_id' );
		$fetchFields = array_merge( $fetchFields, array_keys( $diHandler->getFetchFields() ) );

		// We feel lucky by doing a unconditional select
		$rows = $connection->select(
			$proptable->getName(),
			$fetchFields,
			array(),
			__METHOD__
		);

		if ( $rows === false ) {
			return;
		}

		$i = 0;
		$expected = $rows->numRows();
		$table = $proptable->getName();

		foreach ( $rows as $row ) {
			$i++;
			$this->reportMessage( "\r". sprintf( "%-20s%s", "- {$table}", sprintf("%4.0f%% (%s/%s)",( $i / $expected) * 100, $i, $expected ) ) );
			$this->doRealUpdateOnRow( $connection, $proptable, $diHandler, $row );
		}

		$this->reportMessage( "\n" );
	}

	private function doRealUpdateOnRow( $connection, $proptable, $diHandler, $row ) {

		// Get the original content and use indexer to create
		// the search content
		$condition = isset( $row->p_id ) ? array( 's_id' => $row->s_id, 'p_id' => $row->p_id ) : array( 's_id' => $row->s_id );
		$searchString = isset( $row->o_serialized ) ? $row->o_serialized : ( $row->o_blob === null ? $row->o_hash : $row->o_blob );

		if ( $searchString === '' || $searchString === null ) {
			continue;
		}

		$connection->update(
			$proptable->getName(),
			array( $diHandler->getExtraSearchIndexField() => SearchField::getIndexStringFrom( $searchString ) ),
			$condition,
			__METHOD__
		);
	}

	private function doRebuildContentForIdTable() {

		$this->reportMessage( "\n" . "Starting the ID table update ... " ."\n\n" );
		$connection = $this->store->getConnection( 'mw.db' );

		$rows = $connection->select(
			SQLStore::ID_TABLE,
			array( 'smw_id', 'smw_sortkey' ),
			array( 'smw_sortkey!=' . "''" ), // Postgres "empty quotes ..." to avoid zero-length delimited identifier at or near """"
			__METHOD__
		);

		if ( $rows === false ) {
			continue;
		}

		$i = 0;
		$expected = $rows->numRows();
		$table = SQLStore::ID_TABLE;

		foreach ( $rows as $row ) {
			$i++;
			$this->reportMessage( "\r". sprintf( "%-20s%s", "- {$table}", sprintf("%4.0f%% (%s/%s)",( $i / $expected) * 100, $i, $expected ) ) );

			$connection->update(
				SQLStore::ID_TABLE,
				array( 'smw_searchkey' => SearchField::getIndexStringFrom( $row->smw_sortkey ) ),
				array( 'smw_id' => $row->smw_id ),
				__METHOD__
			);
		}

		$this->reportMessage( "\n" );
	}

	protected function reportMessage( $message ) {
		$this->reporter->reportMessage( $message );
	}

}

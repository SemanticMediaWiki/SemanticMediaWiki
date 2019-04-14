<?php

namespace SMW\SQLStore;

use SMWQuery as Query;
use Onoi\Cache\Cache;

/**
 * @license GNU GPL v2+
 * @since 3.1
 *
 * @author mwjames
 */
class TableStatistics {

	/**
	 * @var SQLStore
	 */
	private $store;

	/**
	 * @since 3.1
	 *
	 * @param SQLStore $store
	 */
	public function __construct( SQLStore $store ) {
		$this->store = $store;
	}

	/**
	 * @since 3.1
	 *
	 * @return array
	 */
	public function getStats() {
		return $this->loadFromDB( $this->store->getConnection( 'mw.db' ) );
	}

	/**
	 * @since 3.1
	 *
	 * @return array
	 */
	public function get( $key ) {
		return $this->{$key}( $this->store->getConnection( 'mw.db' ) );
	}

	private function loadFromDB( $connection ) {

		$start_time = -microtime( true );
		$duplicates = $this->store->getObjectIds()->findDuplicates();

		if ( isset( $duplicates[SQLStore::ID_TABLE] ) ) {
			$duplicate_count = count( $duplicates[SQLStore::ID_TABLE] );
		} else {
			$duplicate_count = 0;
		}

		// Gets the last ID currently in use
		$last_id = $this->last_id( $connection );

		// Counts rows currently available in the id_table
		$rows_total_count = $this->rows_total_count( $connection );

		// Counts the rows currently marked as disposable
		$rows_delete_count = $this->rows_delete_count( $connection );

		// Counts the rows currently marked as redirects
		$rows_redirect_count = $this->rows_redirect_count( $connection );

		// Counts rows that have a revision_id assigned indicating
		// a direct wikipage link
		$rows_rev_count = $this->rows_rev_count( $connection );

		// Counts the total rows in the query_links table
		$rows_query_links_total_count = $this->rows_query_links_total_count( $connection );

		/**
		 * @note `Query::ID_PREFIX . '%'` queries are expensive but there is no
		 * other indicator available to cross reference with what is recorded in
		 * the id_table to identify those objects that represent a query entity.
		 */

		// Counts those subobjects identified as representing a query but are
		// missing an table entry == unlinked (floating reference)
		$unlinked_query_proptable_hash_count = $this->unlinked_query_proptable_hash_count( $connection );

		// Count those subobjects identified as representing a query that have
		// a recorded table entry == linked
		$linked_query_proptable_hash_count = $this->linked_query_proptable_hash_count( $connection );

		// Counts query links that are active by linking entries from the
		// query_links table with that of the id_table
		$active_query_links_count = $this->active_query_links_count( $connection );

		// Counts those IDs in the query_links table that have no subobject
		// representation in the id_table == invalid links, simple page references
		// instead of subobject references
		$invalid_query_links_count = $this->invalid_query_links_count( $connection );

		// Counts query_links table IDs that have no ID assigned in the id_table
		// == unassigned, lost
		$unassigned_query_links_count = $this->unassigned_query_links_count( $connection );

		$snapshot_date = new \DateTime( 'now' );

		$stats = [
			SQLStore::ID_TABLE => [
				'last_id' => $last_id,
				'duplicate_count' => $duplicate_count,
				'rows' => [
					'total_count' => $rows_total_count,
					'rev_count' => $rows_rev_count,
					'smw_iw' => [
						'delete_count' => $rows_delete_count,
						'redirect_count' => $rows_redirect_count,
					],
					'smw_proptable_hash' => [
						'query_match_count' => $linked_query_proptable_hash_count,
						'query_null_count' => $unlinked_query_proptable_hash_count,
					]
				]
			],
			SQLStore::QUERY_LINKS_TABLE => [
				'total_count' => $rows_query_links_total_count,
				'rows' => [
					'active_links_count' => $active_query_links_count,
					'invalid_links_count' => $invalid_query_links_count,
					'unassigned_count' => $unassigned_query_links_count,
				],
			],
			'meta' => [
				'query_time' => round( microtime( true ) + $start_time, 5 ),
				'snapshot_date' => $snapshot_date->format( 'Y-m-d H:i:s' )
			]
		];

		return $stats;
	}

	private function last_id( $connection ) {
		return (int)$connection->selectField(
			SQLStore::ID_TABLE,
			'MAX(smw_id)',
			'',
			__METHOD__
		);
	}

	private function rows_total_count( $connection ) {
		return (int)$connection->selectField(
			SQLStore::ID_TABLE,
			'Count(*)',
			'',
			__METHOD__
		);
	}

	private function rows_delete_count( $connection ) {
		return (int)$connection->selectField(
			SQLStore::ID_TABLE,
			'Count(*)',
			[
				'smw_iw' => SMW_SQL3_SMWDELETEIW
			],
			__METHOD__
		);
	}

	private function rows_redirect_count( $connection ) {
		return (int)$connection->selectField(
			SQLStore::ID_TABLE,
			'Count(*)',
			[
				'smw_iw' => SMW_SQL3_SMWREDIIW
			],
			__METHOD__
		);
	}

	private function rows_rev_count( $connection ) {
		return (int)$connection->selectField(
			SQLStore::ID_TABLE,
			'Count(*)',
			[
				'smw_rev IS NOT NULL'
			],
			__METHOD__
		);
	}

	private function rows_query_links_total_count( $connection ) {
		return (int)$connection->selectField(
			SQLStore::QUERY_LINKS_TABLE,
			'Count(*)',
			'',
			__METHOD__
		);
	}

	private function unlinked_query_proptable_hash_count( $connection ) {
		return (int)$connection->selectField(
			SQLStore::ID_TABLE,
			'Count(*)',
			[
				'smw_subobject LIKE ' . $connection->addQuotes( Query::ID_PREFIX . '%' ),
				'smw_proptable_hash IS NULL'
			],
			__METHOD__
		);
	}

	private function linked_query_proptable_hash_count( $connection ) {
		return (int)$connection->selectField(
			SQLStore::ID_TABLE,
			'Count(*)',
			[
				'smw_subobject LIKE ' . $connection->addQuotes( Query::ID_PREFIX . '%' ),
				'smw_proptable_hash IS NOT NULL'
			],
			__METHOD__
		);
	}

	private function active_query_links_count( $connection ) {

		$row = $connection->selectRow(
			[ SQLStore::QUERY_LINKS_TABLE, SQLStore::ID_TABLE ],
			'COUNT(*) as count',
			[
				'smw_subobject LIKE ' . $connection->addQuotes( Query::ID_PREFIX . '%' ),
			],
			__METHOD__,
			[],
			[
				SQLStore::QUERY_LINKS_TABLE => [
					'INNER JOIN', "s_id=smw_id"
				]
			]
		);

		return (int)$row->count;
	}

	private function invalid_query_links_count( $connection ) {

		$row = $connection->selectRow(
			[ SQLStore::QUERY_LINKS_TABLE, SQLStore::ID_TABLE ],
			'COUNT(*) as count',
			[
				"smw_subobject=''"
			],
			__METHOD__,
			[],
			[
				SQLStore::QUERY_LINKS_TABLE => [
					'INNER JOIN', "s_id=smw_id"
				]
			]
		);

		return (int)$row->count;
	}

	private function unassigned_query_links_count( $connection ) {

		$row = $connection->selectRow(
			[ SQLStore::QUERY_LINKS_TABLE, SQLStore::ID_TABLE ],
			'COUNT(*) as count',
			[
				"smw_id IS NULL"
			],
			__METHOD__,
			[],
			[
				SQLStore::ID_TABLE => [
					'LEFT JOIN', "smw_id=s_id"
				]
			]
		);

		return (int)$row->count;
	}

}

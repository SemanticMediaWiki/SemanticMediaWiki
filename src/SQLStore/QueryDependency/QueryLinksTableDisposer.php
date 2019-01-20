<?php

namespace SMW\SQLStore\QueryDependency;

use SMW\ApplicationFactory;
use SMW\DIWikiPage;
use SMW\SQLStore\SQLStore;
use SMW\Store;
use SMW\IteratorFactory;

/**
 * @private
 *
 * @license GNU GPL v2+
 * @since 3.1
 *
 * @author mwjames
 */
class QueryLinksTableDisposer {

	/**
	 * @var SQLStore
	 */
	private $store;

	/**
	 * @var IteratorFactory
	 */
	private $iteratorFactory;

	/**
	 * @var Database
	 */
	private $connection;

	/**
	 * @var boolean
	 */
	private $onTransactionIdle = false;

	/**
	 * @var boolean
	 */
	private $waitForReplication = false;

	/**
	 * @since 3.1
	 *
	 * @param Store $store
	 * @param IteratorFactory $iteratorFactory
	 */
	public function __construct( Store $store, IteratorFactory $iteratorFactory ) {
		$this->store = $store;
		$this->iteratorFactory = $iteratorFactory;
		$this->connection = $this->store->getConnection( 'mw.db' );
	}

	/**
	 * @since 3.1
	 */
	public function waitOnTransactionIdle() {
		$this->onTransactionIdle = true;
	}

	/**
	 * @since 3.1
	 */
	public function waitForReplication() {
		$this->waitForReplication = true;
	}

	/**
	 * @since 3.1
	 *
	 * @return ResultIterator
	 */
	public function newOutdatedQueryLinksResultIterator() {

		$res = $this->connection->select(
			[ SQLStore::QUERY_LINKS_TABLE, SQLStore::ID_TABLE ],
			's_id as id',
			[
				"smw_subobject=''"
			],
			__METHOD__,
			[],
			[
				SQLStore::QUERY_LINKS_TABLE => [ 'INNER JOIN', "s_id=smw_id" ]
			]
		);

		return $this->iteratorFactory->newResultIterator( $res );
	}

	/**
	 * Returns a list of matches with IDs being listed in the query links table
	 * but no longer reside in the entity (ID) table.
	 *
	 * @since 3.1
	 *
	 * @return ResultIterator
	 */
	public function newUnassignedQueryLinksResultIterator() {

		$res = $this->connection->select(
			[ SQLStore::QUERY_LINKS_TABLE, SQLStore::ID_TABLE ],
			's_id as id',
			[
				"smw_id IS NULL"
			],
			__METHOD__,
			[],
			[
				SQLStore::ID_TABLE => [ 'LEFT JOIN', "smw_id=s_id" ]
			]
		);

		return $this->iteratorFactory->newResultIterator( $res );
	}

	/**
	 * @since 3.1
	 *
	 * @param stdClass|integer $id
	 */
	public function cleanUpTableEntriesById( $id ) {

		$fname = __METHOD__;

		if ( isset( $id->id ) ) {
			$id = $id->id;
		}

		if ( $this->onTransactionIdle ) {
			return $this->connection->onTransactionIdle( function() use ( $id, $fname ) {
				$this->connection->delete(
					SQLStore::QUERY_LINKS_TABLE,
					[
						's_id' => $id
					],
					$fname
				);
			} );
		}

		$this->connection->delete(
			SQLStore::QUERY_LINKS_TABLE,
			[
				's_id' => $id
			],
			$fname
		);
	}

}

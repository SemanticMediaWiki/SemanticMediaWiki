<?php

namespace SMW\SQLStore\QueryDependency;

use SMW\IteratorFactory;
use SMW\Iterators\ResultIterator;
use SMW\MediaWiki\Connection\Database;
use SMW\SQLStore\SQLStore;
use SMW\Store;
use stdClass;

/**
 * @private
 *
 * @license GPL-2.0-or-later
 * @since 3.1
 *
 * @author mwjames
 */
class QueryLinksTableDisposer {

	/**
	 * @var Database
	 */
	private $connection;

	private bool $onTransactionIdle = false;

	private bool $waitForReplication = false;

	/**
	 * @since 3.1
	 */
	public function __construct(
		private readonly Store $store,
		private readonly IteratorFactory $iteratorFactory,
	) {
		$this->connection = $this->store->getConnection( 'mw.db' );
	}

	/**
	 * @since 3.1
	 */
	public function waitOnTransactionIdle(): void {
		$this->onTransactionIdle = true;
	}

	/**
	 * @since 3.1
	 */
	public function waitForReplication(): void {
		$this->waitForReplication = true;
	}

	/**
	 * @since 3.1
	 *
	 * @return ResultIterator
	 */
	public function newOutdatedQueryLinksResultIterator(): ResultIterator {
		$res = $this->connection->newSelectQueryBuilder()
			->select( [ 'id' => 's_id' ] )
			->from( SQLStore::QUERY_LINKS_TABLE )
			->join( SQLStore::ID_TABLE, null, [ 's_id=smw_id' ] )
			->where( [ "smw_subobject=''" ] )
			->caller( __METHOD__ )
			->fetchResultSet();

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
	public function newUnassignedQueryLinksResultIterator(): ResultIterator {
		$res = $this->connection->newSelectQueryBuilder()
			->select( [ 'id' => 's_id' ] )
			->from( SQLStore::QUERY_LINKS_TABLE )
			->leftJoin( SQLStore::ID_TABLE, null, [ 'smw_id=s_id' ] )
			->where( [ 'smw_id IS NULL' ] )
			->caller( __METHOD__ )
			->fetchResultSet();

		return $this->iteratorFactory->newResultIterator( $res );
	}

	/**
	 * @since 3.1
	 *
	 * @param stdClass|int $id
	 */
	public function cleanUpTableEntriesById( $id ) {
		$fname = __METHOD__;

		if ( isset( $id->id ) ) {
			$id = $id->id;
		}

		if ( $this->onTransactionIdle ) {
			return $this->connection->onTransactionCommitOrIdle( function () use ( $id, $fname ): void {
				$this->connection->newDeleteQueryBuilder()
					->deleteFrom( SQLStore::QUERY_LINKS_TABLE )
					->where( [ 's_id' => $id ] )
					->caller( $fname )
					->execute();
			} );
		}

		$this->connection->newDeleteQueryBuilder()
			->deleteFrom( SQLStore::QUERY_LINKS_TABLE )
			->where( [ 's_id' => $id ] )
			->caller( $fname )
			->execute();
	}

}

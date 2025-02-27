<?php

namespace SMW\Elastic\Indexer\Rebuilder;

use SMW\Elastic\Connection\Client as ElasticClient;
use SMW\Elastic\Exception\NoConnectionException;

/**
 * The index uses V1/V2 to switch between versions during a rebuild allowing the
 * index to be available while a reindex is on going and after the process has
 * been indices will be switched (aka rollover) without down time. The index uses
 * aliases to hide the "real" identity of the current active index.
 *
 * @see https://www.elastic.co/guide/en/elasticsearch/reference/master/indices-rollover-index.html
 *
 * @license GPL-2.0-or-later
 * @since 3.0
 *
 * @author mwjames
 */
class Rollover {

	/**
	 * @var ElasticClient
	 */
	private $connection;

	/**
	 * @since 3.0
	 *
	 * @param ElasticClient $connection
	 */
	public function __construct( ElasticClient $connection ) {
		$this->connection = $connection;
	}

	/**
	 * @since 3.0
	 *
	 * @param string $type
	 * @param string $version
	 *
	 * @return string
	 */
	public function rollover( $type, $version ) {
		$index = $this->connection->getIndexName( $type );

		$params = [];

		$old = $version === 'v2' ? 'v1' : 'v2';
		$check = false;

		if ( $this->connection->indexExists( "$index-$old" ) ) {
			$actions = [
				[ 'remove' => [ 'index' => "$index-$old", 'alias' => $index ] ],
				[ 'add' => [ 'index' => "$index-$version", 'alias' => $index ] ]
			];

			$check = true;
		} else {
			// No old index
			$old = $version;

			$actions = [
				[ 'add' => [ 'index' => "$index-$version", 'alias' => $index ] ]
			];
		}

		$params['body'] = [ 'actions' => $actions ];

		$this->connection->updateAliases( $params );

		if ( $check && $this->connection->indexExists( "$index-$old" ) ) {
			$this->connection->deleteIndex( "$index-$old" );
		}

		$this->connection->releaseLock( $type );

		return $old;
	}

	/**
	 * @since 3.0
	 *
	 * @param string $type
	 *
	 * @throws NoConnectionException
	 */
	public function update( $type ) {
		// Fail hard since we expect to create an index but are unable to do so!
		if ( !$this->connection->ping() ) {
			throw new NoConnectionException();
		}

		$index = $this->connection->getIndexName(
			$type
		);

		// Shouldn't happen but just in case where the root index is
		// used as index but not an alias
		if ( $this->connection->indexExists( "$index" ) && !$this->connection->aliasExists( "$index" ) ) {
			$this->connection->deleteIndex( "$index" );
		}

		// Check v1/v2 and if both exists (which shouldn't happen but most likely
		// caused by an unfinshed rebuilder run) then use v1 as master
		if ( $this->connection->indexExists( "$index-v1" ) ) {

			// Just in case
			if ( $this->connection->indexExists( "$index-v2" ) ) {
				$this->connection->deleteIndex( "$index-v2" );
			}

			$actions[] = [ 'add' => [ 'index' => "$index-v1", 'alias' => $index ] ];
		} elseif ( $this->connection->indexExists( "$index-v2" ) ) {
			$actions[] = [ 'add' => [ 'index' => "$index-v2", 'alias' => $index ] ];
		} else {
			$version = $this->connection->createIndex( $type );

			$actions = [
				[ 'add' => [ 'index' => "$index-$version", 'alias' => $index ] ]
			];
		}

		$params['body'] = [ 'actions' => $actions ];
		$this->connection->updateAliases( $params );

		return $index;
	}

	/**
	 * @since 3.0
	 *
	 * @param string $type
	 *
	 * @throws NoConnectionException
	 */
	public function delete( $type ) {
		// Fail hard since we expect to delete an index but are unable to do so!
		if ( !$this->connection->ping() ) {
			throw new NoConnectionException();
		}

		$index = $this->connection->getIndexName( $type );

		if ( $this->connection->indexExists( "$index-v1" ) ) {
			$this->connection->deleteIndex( "$index-v1" );
		}

		if ( $this->connection->indexExists( "$index-v2" ) ) {
			$this->connection->deleteIndex( "$index-v2" );
		}

		if ( $this->connection->indexExists( "$index" ) && !$this->connection->aliasExists( "$index" ) ) {
			$this->connection->deleteIndex( "$index" );
		}

		$this->connection->releaseLock( $type );

		return $index;
	}

}

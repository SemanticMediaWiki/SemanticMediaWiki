<?php

namespace SMW\SQLStore;

use SMW\Cache\FixedInMemoryCache;
use SMW\MediaWiki\Database;
use SMW\Cache\Cache;

use SMW\HashBuilder;

/**
 * @license GNU GPL v2+
 * @since 2.1
 *
 * @author mwjames
 */
class RedirectInfoStore {

	const TABLENAME = 'smw_fpt_redi';

	/**
	 * @var Database
	 */
	private $connection = null;

	/**
	 * @var Cache
	 */
	private $cache = null;

	/**
	 * @since 2.1
	 *
	 * @param Database $connection
	 * @param Cache|null $cache
	 */
	public function __construct( Database $connection, Cache $cache = null ) {
		$this->connection = $connection;
		$this->cache = $cache;

		if ( $this->cache === null ) {
			$this->cache = new FixedInMemoryCache( 500 );
		}
	}

	/**
	 * Returns an id for a redirect if no redirect is found 0 is returned
	 *
	 * @since 2.1
	 *
	 * @param string $title DB key
	 * @param integer $namespace
	 *
	 * @return integer
	 */
	public function findRedirectIdFor( $title, $namespace ) {

		$hash = HashBuilder::createHashIdFromSegments(
			$title,
			$namespace
		);

		if ( $this->cache->contains( $hash ) ) {
			return $this->cache->fetch( $hash );
		}

		$id = $this->select( $title, $namespace );

		$this->cache->save( $hash, $id );

		return $id;
	}

	/**
	 * @since 2.1
	 *
	 * @param integer $id
	 * @param string $title
	 * @param integer $namespace
	 */
	public function addRedirectForId( $id, $title, $namespace ) {

		$this->insert( $id, $title, $namespace );

		$hash = HashBuilder::createHashIdFromSegments(
			$title,
			$namespace
		);

		$this->cache->save( $hash, $id );
	}

	/**
	 * @since 2.1
	 *
	 * @param string $title
	 * @param integer $namespace
	 */
	public function deleteRedirectEntry( $title, $namespace ) {

		$this->delete( $title, $namespace );

		$hash = HashBuilder::createHashIdFromSegments(
			$title,
			$namespace
		);

		$this->cache->delete( $hash );
	}

	private function select( $title, $namespace ) {

		$row = $this->connection->selectRow(
			self::TABLENAME,
			'o_id',
			array(
				's_title' => $title,
				's_namespace' => $namespace
			),
			__METHOD__
		);

		return $row !== false && isset( $row->o_id ) ? (int)$row->o_id : 0;
	}

	private function insert( $id, $title, $namespace ) {

		$this->connection->insert(
			self::TABLENAME,
			array(
				's_title' => $title,
				's_namespace' => $namespace,
				'o_id' => $id ),
			__METHOD__
		);
	}

	private function delete( $title, $namespace ) {

		$this->connection->delete(
			self::TABLENAME,
			array(
				's_title' => $title,
				's_namespace' => $namespace ),
			__METHOD__
		);
	}

}

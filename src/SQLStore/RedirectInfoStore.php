<?php

namespace SMW\SQLStore;

use SMW\HashBuilder;
use SMW\InMemoryPoolCache;
use SMW\MediaWiki\Database;
use SMW\Store;
use Onoi\Cache\Cache;

/**
 * @license GNU GPL v2+
 * @since 2.1
 *
 * @author mwjames
 */
class RedirectInfoStore {

	const TABLENAME = 'smw_fpt_redi';

	/**
	 * @var Store
	 */
	private $store;

	/**
	 * @var Cache
	 */
	private $cache;

	/**
	 * @since 2.1
	 *
	 * @param Store $store
	 * @param Cache|null $cache
	 */
	public function __construct( Store $store, Cache $cache = null ) {
		$this->store = $store;
		$this->cache = $cache;

		if ( $this->cache === null ) {
			$this->cache = InMemoryPoolCache::getInstance()->getPoolCacheById( 'sql.store.redirect.infostore' );
		}
	}

	/**
	 * @since 3.0
	 *
	 * @param string $title DB key
	 * @param integer $namespace
	 *
	 * @return boolean
	 */
	public function isRedirect( $title, $namespace ) {
		return $this->findRedirect( $title, $namespace ) != 0;
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
	public function findRedirect( $title, $namespace ) {

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
	public function addRedirect( $id, $title, $namespace ) {

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
	public function deleteRedirect( $title, $namespace ) {

		$this->delete( $title, $namespace );

		$hash = HashBuilder::createHashIdFromSegments(
			$title,
			$namespace
		);

		$this->cache->delete( $hash );
	}

	private function select( $title, $namespace ) {

		$connection = $this->store->getConnection( 'mw.db' );

		$row = $connection->selectRow(
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

		$connection = $this->store->getConnection( 'mw.db' );

		$connection->insert(
			self::TABLENAME,
			array(
				's_title' => $title,
				's_namespace' => $namespace,
				'o_id' => $id ),
			__METHOD__
		);
	}

	private function delete( $title, $namespace ) {

		$connection = $this->store->getConnection( 'mw.db' );

		$connection->delete(
			self::TABLENAME,
			array(
				's_title' => $title,
				's_namespace' => $namespace ),
			__METHOD__
		);
	}

}

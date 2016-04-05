<?php

namespace SMW\SQLStore;

use SMW\HashBuilder;
use SMW\InMemoryPoolCache;
use SMW\MediaWiki\Database;

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
	 * @var InMemoryPoolCache
	 */
	private $inMemoryPoolCache;

	/**
	 * @since 2.1
	 *
	 * @param Database $connection
	 */
	public function __construct( Database $connection ) {
		$this->connection = $connection;
		$this->inMemoryPoolCache = InMemoryPoolCache::getInstance();
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

		$poolCache = $this->inMemoryPoolCache->getPoolCacheFor( 'sql.store.redirect.infostore' );

		if ( $poolCache->contains( $hash ) ) {
			return $poolCache->fetch( $hash );
		}

		$id = $this->select( $title, $namespace );

		$poolCache->save( $hash, $id );

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

		$this->inMemoryPoolCache->getPoolCacheFor( 'sql.store.redirect.infostore' )->save( $hash, $id );
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

		$this->inMemoryPoolCache->getPoolCacheFor( 'sql.store.redirect.infostore' )->delete( $hash );
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

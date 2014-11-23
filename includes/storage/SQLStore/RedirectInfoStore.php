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
	private $redirectCache = null;

	/**
	 * @since 2.1
	 *
	 * @param Database $connection
	 * @param Cache|null $redirectCache
	 */
	public function __construct( Database $connection, Cache $redirectCache = null ) {
		$this->connection = $connection;
		$this->redirectCache = $redirectCache;

		if ( $this->redirectCache === null ) {
			$this->redirectCache = new FixedInMemoryCache( 500 );
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

		if ( $this->redirectCache->contains( $hash ) ) {
			return $this->redirectCache->fetch( $hash );
		}

		$id = (int)$this->find( $title, $namespace );

		$this->redirectCache->save( $hash, $id );

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

		$this->redirectCache->save( $hash, $id );
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

		$this->redirectCache->delete( $hash );
	}

	private function find( $title, $namespace ) {

		$row = $this->connection->selectRow(
			self::TABLENAME,
			'o_id',
			array(
				's_title' => $title,
				's_namespace' => $namespace
			),
			__METHOD__
		);

		return $row !== false && isset( $row->o_id ) ? $row->o_id : 0;
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

<?php

namespace SMW\SQLStore;

use SMW\Cache\InMemoryCache;
use SMW\Cache\Cache;

use SMW\DIProperty;
use SMW\DIWikiPage;

/**
 * @license GNU GPL v2+
 * @since 2.1
 *
 * @author mwjames
 */
class ItemByIdFinder {

	/**
	 * @var Database
	 */
	private $dbConnection = null;

	/**
	 * @var string
	 */
	private $tableName = '';

	/**
	 * @var Cache
	 */
	private $idCache = null;

	/**
	 * @since 2.1
	 *
	 * @param Databse $dbConnection
	 * @param string $tableName
	 * @param Cache|null $idCache
	 */
	public function __construct( $dbConnection, $tableName, Cache $idCache = null ) {
		$this->dbConnection = $dbConnection;
		$this->tableName = $tableName;
		$this->idCache = $idCache;
	}

	/**
	 * @since 2.1
	 *
	 * @return Cache
	 */
	public function getIdCache() {

		if ( $this->idCache === null ) {
			$this->idCache = new InMemoryCache( 500 );
		}

		return $this->idCache;
	}

	/**
	 * @since 2.1
	 *
	 * @param integer $id
	 *
	 * @return DIWikiPage|null
	 */
	public function getDataItemForId( $id ) {

		if ( !$this->getIdCache()->contains( $id ) ) {

			$row = $this->dbConnection->selectRow(
				$this->tableName,
				array(
					'smw_title',
					'smw_namespace',
					'smw_iw',
					'smw_subobject'
				),
				array( 'smw_id' => $id ),
				__METHOD__
			);

			if ( $row === false ) {
				return null;
			}

			$item = $this->createHashKey(
				$row->smw_title,
				$row->smw_namespace,
				$row->smw_iw,
				$row->smw_subobject
			);

			$this->getIdCache()->save( $id, $item );
		}

		list( $title, $namespace, $interwiki, $subobject ) = explode( '#', $this->getIdCache()->fetch( $id ) );

		if ( $title{0} === '_' ) {
			$title = DIProperty::findPropertyLabel( $title );
		}

		return new DIWikiPage( $title, $namespace, $interwiki, $subobject );
	}

	private function createHashKey( $title, $namespace, $interwiki, $subobject ) {
		return "$title#$namespace#$interwiki#$subobject";
	}

}

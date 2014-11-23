<?php

namespace SMW\SQLStore;

use SMW\Cache\FixedInMemoryCache;
use SMW\MediaWiki\Database;
use SMW\Cache\Cache;

use SMW\DIProperty;
use SMW\DIWikiPage;
use SMW\HashBuilder;

/**
 * @license GNU GPL v2+
 * @since 2.1
 *
 * @author mwjames
 */
class DataItemByIdFinder {

	/**
	 * @var Database
	 */
	private $connection = null;

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
	 * @param Database $connection
	 * @param string $tableName
	 * @param Cache|null $idCache
	 */
	public function __construct( Database $connection, $tableName, Cache $idCache = null ) {
		$this->connection = $connection;
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
			$this->idCache = new FixedInMemoryCache( 500 );
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

			$row = $this->connection->selectRow(
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

			$hash = HashBuilder::createHashIdFromSegments(
				$row->smw_title,
				$row->smw_namespace,
				$row->smw_iw,
				$row->smw_subobject
			);

			$this->getIdCache()->save( $id, $hash );
		}

		return HashBuilder::newDiWikiPageFromHash( $this->getIdCache()->fetch( $id ) );
	}

}

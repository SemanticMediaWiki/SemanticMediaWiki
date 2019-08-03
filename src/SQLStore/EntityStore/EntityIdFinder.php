<?php

namespace SMW\SQLStore\EntityStore;

use SMW\DIWikiPage;
use SMW\IteratorFactory;
use SMW\RequestOptions;
use SMW\SQLStore\SQLStore;
use SMW\DIProperty;
use SMW\TypesRegistry;
use SMW\PropertyRegistry;
use SMW\SQLStore\RedirectStore;
use SMW\MediaWiki\Database;
use SMW\MediaWiki\Deferred\HashFieldUpdate;
use SMW\SQLStore\propertyTable\propertyTableHashes;

/**
 * @private
 *
 * @license GNU GPL v2+
 * @since 3.1
 *
 * @author mwjames
 */
class EntityIdFinder {

	/**
	 * @var Database
	 */
	private $connection;

	/**
	 * @var PropertyTableHashes
	 */
	private $propertyTableHashes;

	/**
	 * @var IdCacheManager
	 */
	private $idCacheManager;

	/**
	 * @var boolean
	 */
	private $fetchPropertyTableHashes = false;

	/**
	 * @since 3.1
	 *
	 * @param Database $connection
	 * @param RedirectStore $redirectStore
	 * @param IdCacheManager $idCacheManager
	 */
	public function __construct( Database $connection, PropertyTableHashes $propertyTableHashes, IdCacheManager $idCacheManager ) {
		$this->connection = $connection;
		$this->propertyTableHashes = $propertyTableHashes;
		$this->idCacheManager = $idCacheManager;
	}

	/**
	 * @since 3.1
	 *
	 * @param boolean $fetchPropertyTableHashes
	 */
	public function setFetchPropertyTableHashes( $fetchPropertyTableHashes ) {
		$this->fetchPropertyTableHashes = $fetchPropertyTableHashes;
	}

	/**
	 * @since 3.1
	 *
	 * @param DIWikiPage $dataItem
	 *
	 * @return integer
	 */
	public function findIdByItem( DIWikiPage $dataItem ) {

		if ( ( $id = $this->idCacheManager->getId( $dataItem ) ) !== false ) {
			return $id;
		}

		$id = 0;

		$row = $this->connection->selectRow(
			SQLStore::ID_TABLE,
			[
				'smw_id'
			],
			[
				'smw_title' => $dataItem->getDBKey(),
				'smw_namespace' => $dataItem->getNamespace(),
				'smw_iw' => $dataItem->getInterWiki(),
				'smw_subobject' => $dataItem->getSubobjectName()
			],
			__METHOD__
		);

		if ( $row !== false ) {
			$id = $row->smw_id;

			$this->idCacheManager->setCache(
				$dataItem->getDBKey(),
				$dataItem->getNamespace(),
				$dataItem->getInterWiki(),
				$dataItem->getSubobjectName(),
				$id,
				$dataItem->getSortKey()
			);
		}

		return $id;
	}

	/**
	 * @since 3.1
	 *
	 * @param integer $id
	 * @param string $title
	 * @param string|integer $namespace
	 * @param string $iw
	 * @param string $subobjectName
	 * @param string &$sortkey
	 *
	 * @return array
	 */
	public function fetchFieldsFromTableById( $id, $title, $namespace, $iw, $subobjectName, &$sortkey ) {

		if ( $id == 0 ) {
			return [ $id, '' ];
		}

		$sha1 = IdCacheManager::computeSha1(
			[
				$title,
				(int)$namespace,
				$iw,
				$subobjectName
			]
		);

		if ( $this->fetchPropertyTableHashes ) {
			$fields = [ 'smw_sortkey', 'smw_sort', 'smw_proptable_hash', 'smw_hash' ];
		} else {
			$fields = [ 'smw_sortkey', 'smw_sort', 'smw_hash' ];
		}

		$row = $this->connection->selectRow(
			SQLStore::ID_TABLE,
			$fields,
			[
				'smw_id' => $id
			],
			__METHOD__
		);

		if ( $row !== false ) {
			// Make sure that smw_sort is being re-computed in case it is null
			$sortkey = $row->smw_sort === null ? '' : $row->smw_sortkey;

			if ( $this->fetchPropertyTableHashes ) {
				$this->propertyTableHashes->setPropertyTableHashesCache( $id, $row->smw_proptable_hash );
			}

			// Prevent any irregularities caused by a delayed, or redirect update
			if ( $row->smw_hash !== $sha1 && $iw !== SMW_SQL3_SMWREDIIW ) {
				HashFieldUpdate::addUpdate( $this->connection, $id, $sha1 );
			}
		} else { // inconsistent DB; just recover somehow
			$sortkey = str_replace( '_', ' ', $title );
		}

		$this->idCacheManager->setCache(
			$title,
			$namespace,
			$iw,
			$subobjectName,
			$id,
			$sortkey
		);

		return [ (int)$id, $sortkey ];
	}

	/**
	 * @since 3.1
	 *
	 * @param string $title
	 * @param string|integer $namespace
	 * @param string $iw
	 * @param string $subobjectName
	 * @param string &$sortkey
	 *
	 * @return array
	 */
	public function fetchFromTableByTitle( $title, $namespace, $iw, $subobjectName, &$sortkey ) {

		$sha1 = IdCacheManager::computeSha1(
			[
				$title,
				(int)$namespace,
				$iw,
				$subobjectName
			]
		);

		if ( $this->fetchPropertyTableHashes ) {
			$fields = [ 'smw_id', 'smw_sortkey', 'smw_sort', 'smw_proptable_hash', 'smw_hash' ];
		} else {
			$fields = [ 'smw_id', 'smw_sortkey', 'smw_sort', 'smw_hash' ];
		}

		// #2001
		// In cases where title components are excessively long (beyond the
		// field limit) it has been observed that at least on MySQL/MariaDB no
		// appropriate matches are found even though a row with a truncated
		// representation exists in the table.
		//
		// `postgres` has no field limit and a divergent behaviour has not
		// been observed
		if ( $subobjectName !== '' && !$this->connection->isType( 'postgres' ) ) {
			$subobjectName = mb_substr( $subobjectName, 0, 255 );
		}

		$row = $this->connection->selectRow(
			SQLStore::ID_TABLE,
			$fields,
			[
				'smw_title' => $title,
				'smw_namespace' => $namespace,
				'smw_iw' => $iw,
				'smw_subobject' => $subobjectName
			],
			__METHOD__
		);

		if ( $row !== false ) {
			$id = $row->smw_id;
			// Make sure that smw_sort is being re-computed in case it is null
			$sortkey = $row->smw_sort === null ? '' : $row->smw_sortkey;

			if ( $this->fetchPropertyTableHashes ) {
				$this->propertyTableHashes->setPropertyTableHashesCache( $id, $row->smw_proptable_hash );
			}

			// Prevent any irregularities caused by a delayed, or redirect update
			if ( $row->smw_hash !== $sha1 && $iw !== SMW_SQL3_SMWREDIIW ) {
				HashFieldUpdate::addUpdate( $this->connection, $id, $sha1 );
			}
		} else {
			$id = 0;
			$sortkey = '';
		}

		$this->idCacheManager->setCache(
			$title,
			$namespace,
			$iw,
			$subobjectName,
			$id,
			$sortkey
		);

		return [ (int)$id, $sortkey ];
	}

	/**
	 * @since 3.1
	 *
	 * @param string $title
	 * @param string|integer $namespace
	 * @param string $iw
	 * @param string $subobjectName
	 *
	 * @return array
	 */
	public function findIdsByTitle( $title, $namespace, $iw = null, $subobjectName = '' ) {

		$matches = [];

		$conditions = [
			'smw_title' => $title,
			'smw_namespace' => $namespace,
			'smw_iw' => $iw,
			'smw_subobject' => $subobjectName
		];

		// Null means select all (incl. those marked delete, redi etc.)
		if ( $iw === null ) {
			unset( $conditions['smw_iw'] );
		}

		$rows = $this->connection->select(
			// This should be necessary but somehow `SQLite` fails here
			$this->connection->tableName( SQLStore::ID_TABLE ),
			[
				'smw_id'
			],
			$conditions,
			__METHOD__
		);

		foreach ( $rows as $row ) {
			$matches[] = (int)$row->smw_id;
		}

		return $matches;
	}

}

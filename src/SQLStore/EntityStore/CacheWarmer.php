<?php

namespace SMW\SQLStore\EntityStore;

use RuntimeException;
use SMW\DIWikiPage;
use SMW\DIProperty;
use SMW\SQLStore\SQLStore;
use SMWQueryResult as QueryResult;
use Iterator;

/**
 * @license GNU GPL v2+
 * @since 3.1
 *
 * @author mwjames
 */
class CacheWarmer {

	/**
	 * @var SQLStore
	 */
	private $store;

	/**
	 * @var integer
	 */
	private $thresholdLimit = 3;

	/**
	 * @since 3.1
	 *
	 * @param SQLStore $store
	 * @param IdCacheManager $idCacheManager
	 */
	public function __construct( SQLStore $store, IdCacheManager $idCacheManager ) {
		$this->store = $store;
		$this->idCacheManager = $idCacheManager;
	}

	/**
	 * @since 3.1
	 *
	 * @param integer $thresholdLimit
	 */
	public function setThresholdLimit( $thresholdLimit ) {
		$this->thresholdLimit = $thresholdLimit;
	}

	/**
	 * @since 3.1
	 *
	 * @param array $list
	 */
	public function fillFromList( $list = [] ) {

		$hashList = [];

		if ( $list instanceof QueryResult ) {
			$list = $list->getResults();
		}

		if ( !$list instanceof Iterator && !is_array( $list ) ) {
			return;
		}

		foreach ( $list as $item ) {

			$hash = null;

			if ( $item instanceof DIWikiPage ) {
				if ( $item->getNamespace() === SMW_NS_PROPERTY ) {
					$property = DIProperty::newFromUserLabel( $item->getDBKey() );
					$hash = $item->getSha1();
				} else {
					$hash = $item->getSha1();
				}
			} elseif ( $item instanceof DIProperty ) {

				// Avoid _SKEY as it is not used during an entity lookup to
				// match an ID
				if ( $item->getKey() === '_SKEY' ) {
					continue;
				}

				$hash = $item->getSha1();
			}

			if ( $hash === null ) {
				continue;
			}

			$hashList[$hash] = true;
		}

		$this->fillFromTableByHash( array_keys( $hashList ) );
	}

	/**
	 * @since 3.1
	 *
	 * @param array $hashList
	 */
	public function fillFromTableByHash( $hashList = [] ) {

		if ( $hashList === [] ) {
			return;
		}

		foreach ( $hashList as $key => $hash ) {
			if ( $this->idCacheManager->hasCache( $hash ) ) {
				unset( $hashList[$key] );
			}
		}

		if ( count( $hashList ) < $this->thresholdLimit ) {
			return;
		}

		$connection = $this->store->getConnection( 'mw.db' );

		$rows = $connection->select(
			SQLStore::ID_TABLE,
			[
				'smw_id',
				'smw_title',
				'smw_namespace',
				'smw_iw',
				'smw_subobject',
				'smw_sortkey',
				'smw_sort'
			],
			[
				'smw_hash' => $hashList
			],
			__METHOD__
		);
		foreach ( $rows as $row ) {
			$sortkey = $row->smw_sort === null ? '' : $row->smw_sortkey;

			$this->idCacheManager->setCache(
				$row->smw_title,
				$row->smw_namespace,
				$row->smw_iw,
				$row->smw_subobject,
				$row->smw_id,
				$sortkey
			);
		}
	}

	/**
	 * @since 3.1
	 *
	 * @param array $idList
	 */
	public function fillFromTableIds( $idList = [] ) {

		if ( $idList === [] ) {
			return;
		}

		$connection = $this->store->getConnection( 'mw.db' );
		$cache = $this->idCacheManager->get( 'warmup.byid' );

		foreach ( $idList as $k => $id ) {
			if ( $cache->contains( $id ) ) {
				unset( $idList[$k] );
			}
		}

		if ( $idList === [] ) {
			return;
		}

		$rows = $connection->select(
			SQLStore::ID_TABLE,
			[
				'smw_id',
				'smw_title',
				'smw_namespace',
				'smw_iw',
				'smw_subobject',
				'smw_sortkey',
				'smw_sort'
			],
			[
				'smw_id' => $idList
			],
			__METHOD__
		);

		foreach ( $rows as $row ) {
			$sortkey = $row->smw_sort === null ? '' : $row->smw_sortkey;

			$this->idCacheManager->setCache(
				$row->smw_title,
				$row->smw_namespace,
				$row->smw_iw,
				$row->smw_subobject,
				$row->smw_id,
				$sortkey
			);

			$cache->save( $row->smw_id, true );
		}
	}

}

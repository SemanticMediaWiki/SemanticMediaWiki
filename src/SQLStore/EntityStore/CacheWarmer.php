<?php

namespace SMW\SQLStore\EntityStore;

use RuntimeException;
use SMW\DIWikiPage;
use SMW\DIProperty;
use SMW\SQLStore\SQLStore;
use SMWQueryResult as QueryResult;
use Iterator;
use SMW\MediaWiki\LinkBatch;
use SMW\DisplayTitleFinder;
use SMW\Exception\PredefinedPropertyLabelMismatchException;
use SMW\Exception\PropertyLabelNotResolvedException;

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
	 * @var IdCacheManager
	 */
	private $idCacheManager;

	/**
	 * @var DisplayTitleFinder
	 */
	private $displayTitleFinder;

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
	 * @param DisplayTitleFinder $displayTitleFinder
	 */
	public function setDisplayTitleFinder( DisplayTitleFinder $displayTitleFinder ) {
		$this->displayTitleFinder = $displayTitleFinder;
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
	public function prepareCache( $list = [] ) {

		$hashList = [];
		$linkBatch = LinkBatch::singleton();
		$linkBatch->setCaller( __METHOD__ );

		if ( $list instanceof QueryResult ) {
			$list = $list->getResults();
		}

		if ( !$list instanceof Iterator && !is_array( $list ) ) {
			return;
		}

		foreach ( $list as $item ) {

			$hash = null;

			if ( $item instanceof DIWikiPage ) {
				$linkBatch->add( $item );

				if ( $item->getNamespace() === SMW_NS_PROPERTY ) {
					try {
						$property = DIProperty::newFromUserLabel( $item->getDBKey() );
					} catch ( PredefinedPropertyLabelMismatchException $e ) {
						continue;
					} catch ( PropertyLabelNotResolvedException $e ) {
						continue;
					}
					$hash = $item->getSha1();
				} else {
					$hash = $item->getSha1();
				}
			} elseif ( $item instanceof DIProperty ) {
				$linkBatch->add( $item->getDIWikiPage() );

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

		$linkBatch->execute();
		$this->prefetchFromList( array_keys( $hashList ) );

		if ( $this->displayTitleFinder !== null ) {
			$this->displayTitleFinder->prefetchFromList( $list );
		}
	}

	/**
	 * @since 3.1
	 *
	 * @param array $hashList
	 */
	public function prefetchFromList( $hashList = [] ) {

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
	public function loadByIds( $idList = [] ) {

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

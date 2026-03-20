<?php

namespace SMW\SQLStore\EntityStore;

use Iterator;
use SMW\DataItems\Property;
use SMW\DataItems\WikiPage;
use SMW\DisplayTitleFinder;
use SMW\Exception\PredefinedPropertyLabelMismatchException;
use SMW\Exception\PropertyLabelNotResolvedException;
use SMW\MediaWiki\LinkBatch;
use SMW\Query\QueryResult;
use SMW\SQLStore\SQLStore;

/**
 * @license GPL-2.0-or-later
 * @since 3.1
 *
 * @author mwjames
 */
class CacheWarmer {

	/**
	 * @var DisplayTitleFinder
	 */
	private $displayTitleFinder;

	/**
	 * @var int
	 */
	private $thresholdLimit = 3;

	/**
	 * @since 3.1
	 */
	public function __construct(
		private readonly SQLStore $store,
		private readonly IdCacheManager $idCacheManager,
	) {
	}

	/**
	 * @since 3.1
	 *
	 * @param DisplayTitleFinder $displayTitleFinder
	 */
	public function setDisplayTitleFinder( DisplayTitleFinder $displayTitleFinder ): void {
		$this->displayTitleFinder = $displayTitleFinder;
	}

	/**
	 * @since 3.1
	 *
	 * @param int $thresholdLimit
	 */
	public function setThresholdLimit( $thresholdLimit ): void {
		$this->thresholdLimit = $thresholdLimit;
	}

	/**
	 * @since 3.1
	 *
	 * @param array $list
	 */
	public function prepareCache( $list = [] ): void {
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

			if ( $item instanceof WikiPage ) {
				$linkBatch->add( $item );

				if ( $item->getNamespace() === SMW_NS_PROPERTY ) {
					try {
						$property = Property::newFromUserLabel( $item->getDBKey() );
					} catch ( PredefinedPropertyLabelMismatchException $e ) {
						continue;
					} catch ( PropertyLabelNotResolvedException $e ) {
						continue;
					}
					$hash = $item->getSha1();
				} else {
					$hash = $item->getSha1();
				}
			} elseif ( $item instanceof Property ) {
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
	public function prefetchFromList( $hashList = [] ): void {
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
	public function loadByIds( $idList = [] ): void {
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

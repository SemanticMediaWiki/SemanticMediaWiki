<?php

namespace SMW\SQLStore\Lookup;

use SMW\DIProperty;
use SMW\DIWikiPage;
use SMW\Store;
use SMW\SQLStore\EntityStore\IdCacheManager;
use SMW\SQLStore\RedirectStore;

/**
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class RedirectTargetLookup {

	/**
	 * Only attempt to lookup from cache
	 */
	const PREPARE_CACHE = 'redirect/cache';

	/**
	 * Only attempt to lookup from cache
	 */
	const CACHE_ONLY = 'cache/only';

	/**
	 * @var Store
	 */
	private $store;

	/**
	 * @var InMemoryCacheManager
	 */
	private $inMemoryCacheManager;

	/**
	 * @since 2.5
	 *
	 * @param Store $store
	 * @param IdCacheManager $inMemoryCacheManager
	 */
	public function __construct( Store $store, IdCacheManager $inMemoryCacheManager ) {
		$this->store = $store;
		$this->inMemoryCacheManager = $inMemoryCacheManager;
	}

	/**
	 * @since 3.2
	 *
	 * @param array $list
	 */
	public function prepareCache( array $list ) {

		$ids = array_keys( $list );

		if ( $ids === [] ) {
			return;
		}

		$source = $this->inMemoryCacheManager->get( IdCacheManager::REDIRECT_SOURCE );
		$target = $this->inMemoryCacheManager->get( IdCacheManager::REDIRECT_TARGET );

		$connection = $this->store->getConnection( 'mw.db' );

		$rows = $connection->select(
			$connection->tableName( RedirectStore::TABLE_NAME ),
			[
				'o_id',
				's_title',
				's_namespace',
			],
			[
				'o_id' => $ids
			],
			__METHOD__
		);

		foreach ( $rows as $row ) {

			// Matching target to source
			[ $title, $namespace, $interwiki, $subobject ] = explode( "#", $list[$row->o_id] );

			$hash = IdCacheManager::computeSha1(
				[ $title, (int)$namespace, $interwiki, $subobject ]
			);

			$source->save( $hash, "$row->s_title#$row->s_namespace##" );

			// Matching source to target
			$hash = IdCacheManager::computeSha1(
				[ $row->s_title, (int)$row->s_namespace, '', '' ]
			);

			$target->save( $hash, "$title#$namespace##" );
		}
	}

	/**
	 * @since 3.2
	 *
	 * @param DIWikiPage $target
	 * @param string $flag
	 *
	 * @return DIWikiPage|false
	 */
	public function findRedirectSource( DIWikiPage $target, ?string $flag = null ) {

		$cache = $this->inMemoryCacheManager->get(
			IdCacheManager::REDIRECT_SOURCE
		);

		if ( $flag === self::CACHE_ONLY && $cache->fetch( $target->getSha1() ) !== false ) {
			return DIWikiPage::doUnserialize( $cache->fetch( $target->getSha1() ) );
		}

		return false;
	}

}

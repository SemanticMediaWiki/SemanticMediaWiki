<?php

namespace SMW\SQLStore\QueryDependency;

use Onoi\Cache\Cache;
use SMW\DIWikiPage;
use SMW\ApplicationFactory;
use SMW\Updater\DeferredCallableUpdate;
use Psr\Log\LoggerAwareTrait;
use Title;

/**
 * Temporary storage of entities that are expected to be refreshed (or updated)
 * during an article view due to being a dependency of an altered query.
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class DependencyLinksUpdateJournal {

	use LoggerAwareTrait;

	/**
	 * @var string
	 */
	const VERSION = '0.1';

	/**
	 * Namespace for the cache instance
	 */
	const CACHE_NAMESPACE = 'smw:update:qdep';

	/**
	 * @var Cache
	 */
	private $cache;

	/**
	 * @var DeferredCallableUpdate
	 */
	private $deferredCallableUpdate;

	/**
	 * @since 3.0
	 *
	 * @param Cache $cache
	 * @param DeferredCallableUpdate $deferredCallableUpdate
	 */
	public function __construct( Cache $cache, DeferredCallableUpdate $deferredCallableUpdate ) {
		$this->cache = $cache;
		$this->deferredCallableUpdate = $deferredCallableUpdate;
	}

	/**
	 * @since 3.0
	 *
	 * @param DIWikiPage $subject
	 *
	 * @return string
	 */
	public static function makeKey( $subject ) {

		$segments = [];

		if ( $subject instanceof DIWikiPage ) {
			$segments = [
				$subject->getDBKey(),
				$subject->getNamespace(),
				$subject->getInterwiki(),
				''
			];
		}

		if ( $subject instanceof Title ) {
			$segments = [
				$subject->getDBKey(),
				$subject->getNamespace(),
				$subject->getInterwiki(),
				''
			];
		}

		$hash = implode( '#', $segments );

		return smwfCacheKey(
			self::CACHE_NAMESPACE,
			[
				$hash,
				self::VERSION
			]
		);
	}

	/**
	 * @since 3.0
	 *
	 * @param array $hashList
	 */
	public function updateFromList( array $hashList ) {

		foreach ( $hashList as $hash ) {

			$key = smwfCacheKey(
				self::CACHE_NAMESPACE,
				[
					$hash,
					self::VERSION
				]
			);

			$this->cache->save( $key, true );
		}
	}

	/**
	 * @since 3.0
	 *
	 * @param DIWikiPage|Title $subject
	 */
	public function update( $subject ) {
		$this->cache->save( self::makeKey( $subject ), true );
	}

	/**
	 * @since 3.0
	 *
	 * @param DIWikiPage|Title $subject
	 */
	public function has( $subject ) {
		return $this->cache->contains( self::makeKey( $subject ) ) === true;
	}

	/**
	 * @since 3.0
	 *
	 * @param DIWikiPage $subject
	 */
	public function delete( $subject ) {

		if ( !$subject instanceof Title && !$subject instanceof DIWikiPage ) {
			throw new RuntimeException( "Invalid subject instance" );
		}

		// Avoid interference with any other process during a preOutputCommit
		// stage especially when CACHE_DB is used as instance
		$this->deferredCallableUpdate->setCallback( function() use( $subject ) {
			$this->cache->delete( self::makeKey( $subject ) );
		} );

		$this->deferredCallableUpdate->setOrigin(
			[
				__METHOD__,
				$subject->getDBKey() . '#' . $subject->getNamespace() . '#' . $subject->getInterwiki()
			]
		);

		$this->deferredCallableUpdate->pushUpdate();
	}

}

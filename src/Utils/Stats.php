<?php

namespace SMW\Utils;

use Onoi\Cache\Cache;
use SMW\ApplicationFactory;

/**
 * Collect statistics in a provisional schema-free storage that depends on the
 * availability of the cache back-end.
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class Stats {

	/**
	 * Update this version number when the serialization format
	 * changes.
	 */
	const VERSION = '0.2';

	/**
	 * Available operations
	 */
	const STATS_INIT = 'init';
	const STATS_INCR = 'incr';
	const STATS_SET = 'set';
	const STATS_MEDIAN = 'median';

	/**
	 * Namespace occupied by the Cache
	 */
	const CACHE_NAMESPACE = 'smw:stats';

	/**
	 * @var Cache
	 */
	private $cache;

	/**
	 * @var string|integer
	 */
	private $id;

	/**
	 * @var boolean
	 */
	private $shouldRecord = true;

	/**
	 * @var array
	 */
	private $stats = [];

	/**
	 * Identifies an update fingerprint to compare invoked deferred updates
	 * against each other and filter those with the same print to avoid recording
	 * duplicate stats.
	 *
	 * @var string
	 */
	private $fingerprint = null;

	/**
	 * @var array
	 */
	private $operations = [];

	/**
	 * @since 2.5
	 *
	 * @param Cache $cache
	 * @param string $id
	 */
	public function __construct( Cache $cache, $id ) {
		$this->cache = $cache;
		$this->id = $id;
		$this->initRecord();
	}

	/**
	 * @since 3.1
	 */
	public function makeCacheKey( $id ) {
		return smwfCacheKey( self::CACHE_NAMESPACE, $id, self::VERSION );
	}

	/**
	 * @since 3.0
	 */
	public function initRecord() {
		$this->fingerprint = $this->id . uniqid();
	}

	/**
	 * @since 2.5
	 *
	 * @param boolean $shouldRecord
	 */
	public function shouldRecord( $shouldRecord ) {
		$this->shouldRecord = (bool)$shouldRecord;
	}

	/**
	 * @since 2.5
	 *
	 * @return array
	 */
	public function getStats() {

		if ( ( $stats = $this->cache->fetch( $this->makeCacheKey( $this->id ) ) ) === false ) {
			return [];
		}

		return StatsFormatter::getStatsFromFlatKey( $stats, '.' );
	}

	/**
	 * @since 2.5
	 *
	 * @param string|array $key
	 */
	public function incr( $key ) {

		if ( !isset( $this->stats[$key] ) ) {
			$this->stats[$key] = 0;
		}

		$this->stats[$key]++;
		$this->operations[$key] = self::STATS_INCR;
	}

	/**
	 * @since 2.5
	 *
	 * @param string|array $key
	 * @param string|integer $default
	 */
	public function init( $key, $default ) {
		$this->stats[$key] = $default;
		$this->operations[$key] = self::STATS_INIT;
	}

	/**
	 * @since 2.5
	 *
	 * @param string|array $key
	 * @param string|integer $value
	 */
	public function set( $key, $value ) {
		$this->stats[$key] = $value;
		$this->operations[$key] = self::STATS_SET;
	}

	/**
	 * @since 2.5
	 *
	 * @param string|array $key
	 * @param integer $value
	 */
	public function calcMedian( $key, $value ) {

		if ( !isset( $this->stats[$key] ) ) {
			$this->stats[$key] = $value;
		} else {
			$this->stats[$key] = ( $this->stats[$key] + $value ) / 2;
		}

		$this->operations[$key] = self::STATS_MEDIAN;
	}

	/**
	 * @since 2.5
	 */
	public function saveStats() {

		if ( $this->stats === [] ) {
			return;
		}

		$container = $this->cache->fetch( $this->makeCacheKey( $this->id ) );

		if ( $container === false || $container === null ) {
			$container = [];
		}

		foreach ( $this->stats as $key => $value ) {

			if ( isset( $container[$key] ) ) {
				$old = $container[$key];
			} else {
				$old = 0;
			}

			if ( $this->operations[$key] === self::STATS_INIT && $old != 0 ) {
				$value = $old;
			}

			if ( $this->operations[$key] === self::STATS_INCR ) {
				$value = $old + $value;
			}

			// Use as-is
			// $this->operations[$key] === self::STATS_SET

			if ( $this->operations[$key] === self::STATS_MEDIAN ) {
				$value = $old > 0 ? ( $old + $value ) / 2 : $value;
			}

			$container[$key] = $value;
		}

		$this->cache->save( $this->makeCacheKey( $this->id ), $container );
		$this->stats = [];
	}

	/**
	 * @since 2.5
	 *
	 * @param boolean $asPending
	 */
	public function recordStats( $asPending = false ) {

		if ( $this->shouldRecord === false ) {
			return $this->stats = [];
		}

		// #2046
		// __destruct as event trigger has shown to be unreliable in a MediaWiki
		// environment therefore rely on the deferred update and any caller
		// that invokes the recordStats method

		$deferredUpdate = ApplicationFactory::getInstance()->newDeferredTransactionalCallableUpdate(
			[ $this, 'saveStats' ]
		);

		// "static::class" to get the name of the called class
		$fname = static::class . '::recordStats';

		$deferredUpdate->setOrigin( $fname );
		$deferredUpdate->waitOnTransactionIdle();

		$deferredUpdate->setFingerprint(
			$fname . $this->fingerprint
		);

		$deferredUpdate->markAsPending( $asPending );
		$deferredUpdate->pushUpdate();
	}

}

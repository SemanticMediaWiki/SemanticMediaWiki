<?php

namespace SMW\Utils;

use Onoi\BlobStore\BlobStore;
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
class BufferedStatsdCollector {

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
	 * Namespace occupied by the BlobStore
	 */
	const CACHE_NAMESPACE = 'smw:stats:store';

	/**
	 * @var BlobStore
	 */
	private $blobStore;

	/**
	 * @var string|integer
	 */
	private $statsdId;

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
	 * @param BlobStore $blobStore
	 * @param string $statsdId
	 */
	public function __construct( BlobStore $blobStore, $statsdId ) {
		$this->blobStore = $blobStore;
		$this->statsdId = $statsdId;
		$this->fingerprint = $statsdId . uniqid();
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

		$container = $this->blobStore->read(
			md5( $this->statsdId . self::VERSION )
		);

		return StatsFormatter::getStatsFromFlatKey( $container->getData(), '.' );
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

		$container = $this->blobStore->read(
			md5( $this->statsdId . self::VERSION )
		);

		foreach ( $this->stats as $key => $value ) {

			$old = $container->has( $key ) ? $container->get( $key ) : 0;

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

			$container->set( $key, $value );
		}

		$this->blobStore->save(
			$container
		);

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

		$deferredTransactionalUpdate = ApplicationFactory::getInstance()->newDeferredTransactionalCallableUpdate(
			function() { $this->saveStats();
			}
		);

		$deferredTransactionalUpdate->setOrigin( __METHOD__ );
		$deferredTransactionalUpdate->waitOnTransactionIdle();

		$deferredTransactionalUpdate->setFingerprint(
			__METHOD__ . $this->fingerprint
		);

		$deferredTransactionalUpdate->markAsPending( $asPending );
		$deferredTransactionalUpdate->pushUpdate();
	}

}

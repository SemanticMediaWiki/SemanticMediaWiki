<?php

namespace SMW;

use Onoi\BlobStore\BlobStore;
use RuntimeException;

/**
 * Collect statistics in a provisional schema-free storage that depends on the
 * availability of the cache back-end.
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class TransientStatsdCollector {

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
	private $stats = array();

	/**
	 * @var array
	 */
	private $operations = array();

	/**
	 * @since 2.5
	 *
	 * @param BlobStore $blobStore
	 * @param string $statsdId
	 */
	public function __construct( BlobStore $blobStore, $statsdId ) {
		$this->blobStore = $blobStore;
		$this->statsdId = $statsdId;
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

		$data = $container->getData();
		$stats = array();

		foreach ( $data as $key => $value ) {
			if ( strpos( $key, '.' ) !== false ) {
				$stats = array_merge_recursive( $stats, $this->stringToArray( $key, $value ) );
			} else {
				$stats[$key] = $value;
			}
		}

		return $stats;
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

	private function recordStats() {

		return function() {

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
		};
	}

	// http://stackoverflow.com/questions/10123604/multstatsdIdimensional-array-from-string
	private function stringToArray( $path, $value ) {
		$separator = '.';
		$pos = strpos( $path, $separator );

		if ( $pos === false ) {
			return array( $path => $value );
		}

		$key = substr( $path, 0, $pos );
		$path = substr( $path, $pos + 1 );

		$result = array(
			$key => $this->stringToArray( $path, $value )
		);

		return $result;
	}

	/**
	 * Trigger the storage when the class leaves scope
	 */
	function __destruct() {
		if ( $this->shouldRecord ) {
			call_user_func( $this->recordStats() );
		}
	}

}

<?php

namespace SMW\Maintenance;

use Onoi\Cache\Cache;
use Onoi\MessageReporter\MessageReporterAwareTrait;
use SMW\SQLStore\PropertyTableIdReferenceDisposer;
use SMW\SQLStore\SQLStore;
use SMW\Store;

/**
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class DuplicateEntitiesDisposer {

	use MessageReporterAwareTrait;

	/**
	 * @var Store
	 */
	private $store;

	/**
	 * @var Store
	 */
	private $cache;

	/**
	 * @since 3.0
	 *
	 * @param Store $store
	 * @param Cache|null $cache
	 */
	public function __construct( Store $store, Cache $cache = null ) {
		$this->store = $store;
		$this->cache = $cache;
	}

	/**
	 * @since 3.0
	 */
	public function findDuplicates() {
		return $this->store->getObjectIds()->findDuplicates();
	}

	/**
	 * @since 3.0
	 *
	 * @param Iterator|array $duplicates
	 */
	public function verifyAndDispose( $duplicates ) {

		if ( !$this->is_iterable( $duplicates ) ) {
			return;
		}

		$count = count( $duplicates );
		$this->messageReporter->reportMessage( "Found: $count duplicates\n" );

		if ( $count > 0 ) {
			$this->doDispose( $duplicates );
		}

		if ( $this->cache !== null ) {
			$this->cache->delete( \SMW\MediaWiki\Api\Task::makeCacheKey( 'duplookup' ) );
		}
	}

	private function doDispose( $duplicates ) {

		$propertyTableIdReferenceDisposer = new PropertyTableIdReferenceDisposer(
			$this->store
		);

		$propertyTableIdReferenceDisposer->setRedirectRemoval( true );
		$connection = $this->store->getConnection( 'mw.db' );

		$log = [
			'disposed' => [],
			'untouched' => []
		];

		$i = 0;
		foreach ( $duplicates as $duplicate ) {
			unset( $duplicate['count'] );

			if ( ( $i ) % 60 === 0 ) {
				$this->messageReporter->reportMessage( "\n" );
			}

			$this->messageReporter->reportMessage( '.' );

			$res = $connection->select(
				SQLStore::ID_TABLE,
				[
					'smw_id',
				],
				[
					'smw_title'=> $duplicate['smw_title'],
					'smw_namespace'=> $duplicate['smw_namespace'],
					'smw_iw'=> $duplicate['smw_iw'],
					'smw_subobject'=> $duplicate['smw_subobject']
				],
				__METHOD__
			);

			foreach ( $res as $row ) {
				if ( $propertyTableIdReferenceDisposer->isDisposable( $row->smw_id ) ) {
					$propertyTableIdReferenceDisposer->cleanUpTableEntriesById( $row->smw_id );
					$log['disposed'][$row->smw_id] = $duplicate;
				} else {
					$log['untouched'][$row->smw_id] = $duplicate;
				}
			}

			$i++;
		}

		$this->messageReporter->reportMessage(
			"\n\nLog\n\n" . json_encode( $log, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . "\n"
		);
	}

	/**
	 * Polyfill for PHP 7.0-
	 *
	 * @see http://php.net/manual/en/function.is-iterable.php
	 *
	 * @since 3.0
	 */
	private function is_iterable( $obj ) {
		return is_array( $obj ) || ( is_object( $obj ) && ( $obj instanceof \Traversable ) );
	}

}

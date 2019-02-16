<?php

namespace SMW\Maintenance;

use Onoi\Cache\Cache;
use Onoi\MessageReporter\MessageReporterAwareTrait;
use SMW\SQLStore\PropertyTableIdReferenceDisposer;
use SMW\SQLStore\PropertyTableInfoFetcher;
use SMW\SQLStore\RedirectStore;
use SMW\SQLStore\SQLStore;
use SMW\Store;
use SMWDataItem as DataItem;

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

		$this->messageReporter->reportMessage( "   ... done.\n" );
		$this->messageReporter->reportMessage( "\nInspecting $count table(s) ...\n" );

		if ( $count > 0 ) {
			$this->doDispose( $duplicates );
		}

		if ( $this->cache !== null ) {
			$this->cache->delete( \SMW\MediaWiki\Api\Tasks\Task::makeCacheKey( 'duplicate-lookup' ) );
		}
	}

	private function doDispose( $duplicates ) {

		$log = [];

		foreach ( $duplicates as $table => $duplicate ) {
			$this->messageReporter->reportMessage( '   ... ' . $table . ' found ' . count( $duplicate ) . ' record(s) ...' );

			if ( $table === SQLStore::ID_TABLE ) {
				$this->id_table( $table, $duplicate, $log );
			} elseif ( $table === PropertyTableInfoFetcher::findTableIdForDataItemTypeId( DataItem::TYPE_WIKIPAGE ) ) {
				$this->wikipage_table( $table, $duplicate, $log );
			} elseif( $table === RedirectStore::TABLE_NAME ) {
				$this->redi_table( $table, $duplicate, $log );
			}

			$this->messageReporter->reportMessage( "\n" );
		}

		$this->messageReporter->reportMessage( "   ... done.\n" );

		$this->messageReporter->reportMessage( "\nReported entries marked with 'untouched' require manual intervention as\n" );
		$this->messageReporter->reportMessage( "those entities have unresolved references that cannot be removed using\n" );
		$this->messageReporter->reportMessage( "this script.\n" );

		$this->messageReporter->reportMessage( "\nReporting on ..." );
		$this->messageReporter->reportMessage( "\n" . implode( "\n", $log ) . "\n" );
		$this->messageReporter->reportMessage( "   ... done.\n" );
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

	private function wikipage_table( $table, $duplicates, &$log ) {

		$connection = $this->store->getConnection( 'mw.db' );
		$log[] = "   ... $table ...";
		$i = 0;

		foreach ( $duplicates as $duplicate ) {

			if ( ( $i ) % 60 === 0 ) {
				$this->messageReporter->reportMessage( "\n       " );
			}

			$this->messageReporter->reportMessage( '.' );
			$log[] = "       ... disposed: " . $duplicate['s_id'] . "|" . $duplicate['p_id'] . '|' . $duplicate['o_id'];

			$connection->delete(
				$table,
				[
					's_id' => $duplicate['s_id'],
					'p_id' => $duplicate['p_id'],
					'o_id' => $duplicate['o_id'],
				],
				__METHOD__
			);

			$connection->insert(
				$table,
				[
					's_id' => $duplicate['s_id'],
					'p_id' => $duplicate['p_id'],
					'o_id' => $duplicate['o_id'],
				],
				__METHOD__
			);

			$i++;
		}
	}

	private function redi_table( $table, $duplicates, &$log ) {

		$connection = $this->store->getConnection( 'mw.db' );
		$log[] = "   ... $table ...";
		$i = 0;

		foreach ( $duplicates as $duplicate ) {

			if ( ( $i ) % 60 === 0 ) {
				$this->messageReporter->reportMessage( "\n       " );
			}

			$this->messageReporter->reportMessage( '.' );
			$log[] = "       ... disposed: " . $duplicate['o_id'] . " (" . $duplicate['s_title'] . '#' . $duplicate['s_namespace'] . ")";

			$connection->delete(
				$table,
				[
					's_title' => $duplicate['s_title'],
					's_namespace' => $duplicate['s_namespace'],
					'o_id' => $duplicate['o_id'],
				],
				__METHOD__
			);

			if ( $duplicate['s_title'] === '' ) {
				continue;
			}

			$connection->insert(
				$table,
				[
					's_title' => $duplicate['s_title'],
					's_namespace' => $duplicate['s_namespace'],
					'o_id' => $duplicate['o_id'],
				],
				__METHOD__
			);

			$i++;
		}
	}

	private function id_table( $table, $duplicates, &$log ) {

		$propertyTableIdReferenceDisposer = new PropertyTableIdReferenceDisposer(
			$this->store
		);

		$propertyTableIdReferenceDisposer->setRedirectRemoval( true );

		$connection = $this->store->getConnection( 'mw.db' );
		$log[] = "   ... $table ...";

		$i = 0;

		foreach ( $duplicates as $duplicate ) {

			if ( ( $i ) % 60 === 0 ) {
				$this->messageReporter->reportMessage( "\n       " );
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

			$hash = $duplicate['smw_title'] . '#' . $duplicate['smw_namespace'] . '#' . $duplicate['smw_iw'] . '#' . $duplicate['smw_subobject'];

			foreach ( $res as $row ) {
				if ( $propertyTableIdReferenceDisposer->isDisposable( $row->smw_id ) ) {
					$propertyTableIdReferenceDisposer->cleanUpTableEntriesById( $row->smw_id );
					$log[] = "       ... disposed: " . $row->smw_id . " ($hash)";
				} else {
					$log[] = "       ... untouched: " . $row->smw_id . " ($hash)";
				}
			}

			$i++;
		}
	}

}

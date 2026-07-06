<?php

namespace SMW\Maintenance;

use Onoi\MessageReporter\MessageReporterAwareTrait;
use SMW\DataItems\DataItem;
use SMW\MediaWiki\Api\Tasks\Task;
use SMW\SQLStore\PropertyTableInfoFetcher;
use SMW\SQLStore\RedirectStore;
use SMW\SQLStore\SQLStore;
use SMW\Store;
use SMW\Utils\CliMsgFormatter;
use Traversable;
use Wikimedia\ObjectCache\BagOStuff;

/**
 * @license GPL-2.0-or-later
 * @since 3.0
 *
 * @author mwjames
 */
class DuplicateEntitiesDisposer {

	use MessageReporterAwareTrait;

	/**
	 * @since 3.0
	 */
	public function __construct(
		private Store $store,
		private ?BagOStuff $cache = null,
	) {
	}

	/**
	 * @since 3.0
	 */
	public function findDuplicates() {
		return $this->store->getObjectIds()->findDuplicates();
	}

	/**
	 * @since 3.0
	 */
	public function verifyAndDispose( mixed $duplicates ): void {
		if ( !$this->is_iterable( $duplicates ) ) {
			return;
		}

		$count = count( $duplicates );

		$this->messageReporter->reportMessage( "\nInspecting $count table(s) ...\n" );

		if ( $count > 0 ) {
			$this->doDispose( $duplicates );
		}

		if ( $this->cache !== null ) {
			$this->cache->delete( Task::makeCacheKey( 'duplicate-lookup' ) );
		}
	}

	private function doDispose( $duplicates ): void {
		$cliMsgFormatter = new CliMsgFormatter();
		$logs = [];

		foreach ( $duplicates as $table => $duplicate ) {

			$count = count( $duplicate );

			$this->messageReporter->reportMessage(
					$cliMsgFormatter->twoCols( "... $table ...", "$count (records)", 3
				)
			);

			if ( $table === SQLStore::ID_TABLE ) {
				$this->id_table( $table, $duplicate, $logs );
			} elseif ( $table === PropertyTableInfoFetcher::findTableIdForDataItemTypeId( DataItem::TYPE_WIKIPAGE ) ) {
				$this->wikipage_table( $table, $duplicate, $logs );
			} elseif ( $table === RedirectStore::TABLE_NAME ) {
				$this->redi_table( $table, $duplicate, $logs );
			}

			if ( $count > 0 ) {
				$this->messageReporter->reportMessage( "\n" );
			}
		}

		$this->messageReporter->reportMessage( "   ... done.\n" );

		$this->messageReporter->reportMessage(
			$cliMsgFormatter->section( 'Report(s)' )
		);

		$text = [
			"Reported entries marked with 'RETAIN' require manual intervention as",
			"those entities have unresolved references or represent the original record",
			"that cannot be removed using this script."
		];

		$this->messageReporter->reportMessage(
			"\n" . $cliMsgFormatter->wordwrap( $text ) . "\n"
		);

		$this->messageReporter->reportMessage( "\nDisposal log(s) ...\n" );

		foreach ( $logs as $log ) {

			if ( is_string( $log ) ) {
				$this->messageReporter->reportMessage( $log . "\n" );
			} elseif ( is_array( $log ) ) {
				foreach ( $log as $key => $value ) {
					$this->messageReporter->reportMessage(
						$cliMsgFormatter->twoCols( "- $value", "[$key]", 7 )
					);
				}
			}
		}

		$this->messageReporter->reportMessage( "   ... done.\n" );
	}

	/**
	 * Polyfill for PHP 7.0-
	 *
	 * @see http://php.net/manual/en/function.is-iterable.php
	 *
	 * @since 3.0
	 */
	private function is_iterable( mixed $obj ): bool {
		return is_array( $obj ) || ( is_object( $obj ) && ( $obj instanceof Traversable ) );
	}

	private function wikipage_table( string $table, $duplicates, &$log ): void {
		$connection = $this->store->getConnection( 'mw.db' );
		$log[] = "   ... $table ...";
		$i = 0;

		// Each duplicate-tuple needs its own DELETE (composite WHERE varies
		// per row), but the canonical re-INSERTs accumulate into one shared
		// builder so they execute as a single statement at the end.
		$insertBuilder = $connection->newInsertQueryBuilder()
			->insertInto( $table )
			->caller( __METHOD__ );

		foreach ( $duplicates as $duplicate ) {

			if ( $i > 0 && ( $i ) % CliMsgFormatter::MAX_LEN === 0 ) {
				$this->messageReporter->reportMessage( "\n       " );
			} elseif ( $i == 0 ) {
				$this->messageReporter->reportMessage( "       " );
			}

			$this->messageReporter->reportMessage( '.' );
			$log[] = [ 'DELETE' => $duplicate['s_id'] . ", " . $duplicate['p_id'] . ', ' . $duplicate['o_id'] ];

			$connection->newDeleteQueryBuilder()
				->deleteFrom( $table )
				->where( [
					's_id' => $duplicate['s_id'],
					'p_id' => $duplicate['p_id'],
					'o_id' => $duplicate['o_id'],
				] )
				->caller( __METHOD__ )
				->execute();

			$insertBuilder->row( [
				's_id' => $duplicate['s_id'],
				'p_id' => $duplicate['p_id'],
				'o_id' => $duplicate['o_id'],
			] );

			$i++;
		}

		if ( $i > 0 ) {
			$insertBuilder->execute();
		}
	}

	private function redi_table( string $table, $duplicates, &$log ): void {
		$connection = $this->store->getConnection( 'mw.db' );
		$log[] = "   ... $table ...";
		$i = 0;

		// Each duplicate-tuple needs its own DELETE (composite WHERE varies
		// per row), but the canonical re-INSERTs accumulate into one shared
		// builder so they execute as a single statement at the end.
		$insertBuilder = $connection->newInsertQueryBuilder()
			->insertInto( $table )
			->caller( __METHOD__ );

		foreach ( $duplicates as $duplicate ) {

			if ( $i > 0 && ( $i ) % CliMsgFormatter::MAX_LEN === 0 ) {
				$this->messageReporter->reportMessage( "\n       " );
			} elseif ( $i == 0 ) {
				$this->messageReporter->reportMessage( "       " );
			}

			$this->messageReporter->reportMessage( '.' );
			$log[] = [ 'DELETE' => $duplicate['o_id'] . " (" . $duplicate['s_title'] . '#' . $duplicate['s_namespace'] . ")" ];

			$connection->newDeleteQueryBuilder()
				->deleteFrom( $table )
				->where( [
					's_title' => $duplicate['s_title'],
					's_namespace' => $duplicate['s_namespace'],
					'o_id' => $duplicate['o_id'],
				] )
				->caller( __METHOD__ )
				->execute();

			if ( $duplicate['s_title'] === '' ) {
				continue;
			}

			$insertBuilder->row( [
				's_title' => $duplicate['s_title'],
				's_namespace' => $duplicate['s_namespace'],
				'o_id' => $duplicate['o_id'],
			] );

			$i++;
		}

		if ( $i > 0 ) {
			$insertBuilder->execute();
		}
	}

	private function id_table( string $table, $duplicates, &$log ): void {
		$propertyTableIdReferenceDisposer = $this->store->service( 'PropertyTableIdReferenceDisposer' );
		$propertyTableIdReferenceDisposer->setRedirectRemoval( true );

		$connection = $this->store->getConnection( 'mw.db' );
		$log[] = "   ... $table ...";

		$i = 0;

		foreach ( $duplicates as $duplicate ) {

			if ( $i > 0 && ( $i ) % CliMsgFormatter::MAX_LEN === 0 ) {
				$this->messageReporter->reportMessage( "\n       " );
			} elseif ( $i == 0 ) {
				$this->messageReporter->reportMessage( "       " );
			}

			$this->messageReporter->reportMessage( '.' );
			$res = $connection->newSelectQueryBuilder()
				->select( [ 'smw_id' ] )
				->from( SQLStore::ID_TABLE )
				->where( [
					'smw_title' => $duplicate['smw_title'],
					'smw_namespace' => $duplicate['smw_namespace'],
					'smw_iw' => $duplicate['smw_iw'],
					'smw_subobject' => $duplicate['smw_subobject']
				] )
				->caller( __METHOD__ )
				->fetchResultSet();

			$hash = $duplicate['smw_title'] . '#' . $duplicate['smw_namespace'] . '#' . $duplicate['smw_iw'] . '#' . $duplicate['smw_subobject'];

			foreach ( $res as $row ) {
				if ( $propertyTableIdReferenceDisposer->isDisposable( $row->smw_id ) ) {
					$propertyTableIdReferenceDisposer->cleanUpTableEntriesById( $row->smw_id );
					$log[] = [ 'DELETE' => $row->smw_id . " ($hash)" ];
				} else {
					$log[] = [ 'RETAIN' => $row->smw_id . " ($hash)" ];
				}
			}

			$i++;
		}
	}

}

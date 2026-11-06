<?php

namespace SMW\Maintenance;

use Onoi\MessageReporter\MessageReporterAwareTrait;
use SMW\DataItems\DataItem;
use SMW\MediaWiki\Api\Tasks\Task;
use SMW\MediaWiki\Connection\Database;
use SMW\SQLStore\PropertyTableInfoFetcher;
use SMW\SQLStore\RedirectStore;
use SMW\SQLStore\SQLStore;
use SMW\Store;
use SMW\Utils\CliMsgFormatter;
use SMW\Utils\PeriodicStatsFlusher;
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

	private const BATCH_SIZE = 1000;

	private ?PeriodicStatsFlusher $statsFlusher = null;

	/**
	 * @since 3.0
	 */
	public function __construct(
		private Store $store,
		private ?BagOStuff $cache = null,
	) {
	}

	/**
	 * @since 7.2.0
	 */
	public function setStatsFlusher( PeriodicStatsFlusher $statsFlusher ): void {
		$this->statsFlusher = $statsFlusher;
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

	private function wikipage_table( string $table, array $duplicates, array &$log ): void {
		$this->replaceDuplicates(
			$table,
			$duplicates,
			[ 's_id', 'p_id', 'o_id' ],
			static fn ( array $duplicate ): string => "{$duplicate['s_id']}, {$duplicate['p_id']}, {$duplicate['o_id']}",
			$log
		);
	}

	private function redi_table( string $table, array $duplicates, array &$log ): void {
		$this->replaceDuplicates(
			$table,
			$duplicates,
			[ 's_title', 's_namespace', 'o_id' ],
			static fn ( array $duplicate ): string => "{$duplicate['o_id']} ({$duplicate['s_title']}#{$duplicate['s_namespace']})",
			$log
		);
	}

	/**
	 * Replaces every copy of each duplicated tuple with a single one, in batches that each commit
	 * as a whole, so that a run that stops partway cannot leave a tuple without a copy
	 *
	 * @param string $table
	 * @param array[] $duplicates
	 * @param string[] $fields The fields that identify a tuple
	 * @param callable(array): string $describe
	 * @param array &$log
	 */
	private function replaceDuplicates(
		string $table,
		array $duplicates,
		array $fields,
		callable $describe,
		array &$log
	): void {
		$connection = $this->store->getConnection( 'mw.db' );
		$log[] = "   ... $table ...";
		$position = 0;

		foreach ( array_chunk( $duplicates, self::BATCH_SIZE ) as $batch ) {
			foreach ( $batch as $duplicate ) {
				$this->reportProgress( $position++ );
				$log[] = [ 'DELETE' => $describe( $duplicate ) ];
			}

			$connection->doSectionTransaction(
				__METHOD__,
				fn () => $this->replaceBatch( $connection, $table, $batch, $fields )
			);
		}
	}

	/**
	 * @param Database $connection
	 * @param string $table
	 * @param array[] $batch
	 * @param string[] $fields
	 */
	private function replaceBatch( Database $connection, string $table, array $batch, array $fields ): void {
		// Each tuple needs its own DELETE, as its WHERE differs, but their copies go into one INSERT
		$insertBuilder = $connection->newInsertQueryBuilder()
			->insertInto( $table )
			->caller( __METHOD__ );

		$copies = 0;

		foreach ( $batch as $duplicate ) {
			if ( $this->statsFlusher !== null ) {
				$this->statsFlusher->tick();
			}

			$tuple = array_intersect_key( $duplicate, array_flip( $fields ) );

			$connection->newDeleteQueryBuilder()
				->deleteFrom( $table )
				->where( $tuple )
				->caller( __METHOD__ )
				->execute();

			// A tuple with an empty value, such as a redirect without a title, gets no copy
			if ( in_array( '', $tuple, true ) ) {
				continue;
			}

			$insertBuilder->row( $tuple );
			$copies++;
		}

		if ( $copies > 0 ) {
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

			if ( $this->statsFlusher !== null ) {
				$this->statsFlusher->tick();
			}

			$this->reportProgress( $i );

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

	private function reportProgress( int $position ): void {
		if ( $position === 0 ) {
			$this->messageReporter->reportMessage( "       " );
		} elseif ( $position % CliMsgFormatter::MAX_LEN === 0 ) {
			$this->messageReporter->reportMessage( "\n       " );
		}

		$this->messageReporter->reportMessage( '.' );
	}

}

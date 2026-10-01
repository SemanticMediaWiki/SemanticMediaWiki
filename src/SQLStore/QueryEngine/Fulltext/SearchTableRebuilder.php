<?php

namespace SMW\SQLStore\QueryEngine\Fulltext;

use Onoi\MessageReporter\MessageReporter;
use Onoi\MessageReporter\MessageReporterFactory;
use SMW\DataItems\DataItem;
use SMW\DataItems\Property;
use SMW\MediaWiki\Connection\Database;
use SMW\SQLStore\PropertyTableDefinition;
use SMW\SQLStore\SQLStore;
use SMW\Utils\CliMsgFormatter;
use SMW\Utils\PeriodicStatsFlusher;
use Throwable;

/**
 * @license GPL-2.0-or-later
 * @since 2.5
 *
 * @author mwjames
 */
class SearchTableRebuilder {

	/**
	 * @var MessageReporter
	 */
	private $messageReporter;

	private bool $reportVerbose = false;

	private bool $optimization = false;

	private array $skippedTables = [];

	private ?PeriodicStatsFlusher $statsFlusher = null;

	/**
	 * Default number of subject IDs (`s_id`) covered by one chunk.
	 * See rebuildChunk().
	 *
	 * @since 7.3.2
	 */
	public const DEFAULT_BATCH_SIZE = 500;

	public const MAX_BATCH_SIZE = 2000;

	public const MAX_BYTES = 10485760; // 10 * 1024 * 1024

	/**
	 * Property tables that take part in a chunked rebuild,
	 * resolved once per instance. See getChunkTables()
	 */
	private ?array $chunkTables = null;

	/**
	 * @since 2.5
	 */
	public function __construct(
		private readonly Database $connection,
		private readonly SearchTableUpdater $searchTableUpdater,
	) {
		$this->messageReporter = MessageReporterFactory::getInstance()->newNullMessageReporter();
	}

	/**
	 * @since 2.5
	 *
	 * @return SearchTable
	 */
	public function getSearchTable(): SearchTable {
		return $this->searchTableUpdater->getSearchTable();
	}

	/**
	 * @since 2.5
	 *
	 * @param MessageReporter $messageReporter
	 */
	public function setMessageReporter( MessageReporter $messageReporter ): void {
		$this->messageReporter = $messageReporter;
	}

	/**
	 * @since 7.2.0
	 */
	public function setStatsFlusher( PeriodicStatsFlusher $statsFlusher ): void {
		$this->statsFlusher = $statsFlusher;
	}

	/**
	 * @since 2.5
	 *
	 * @param bool $reportVerbose
	 */
	public function reportVerbose( $reportVerbose ): void {
		$this->reportVerbose = (bool)$reportVerbose;
	}

	/**
	 * @since 2.5
	 *
	 * @param bool $optimization
	 */
	public function requestOptimization( $optimization ): void {
		$this->optimization = (bool)$optimization;
	}

	/**
	 * @since 3.2
	 *
	 * @return bool
	 */
	public function canRebuild(): bool {
		return $this->searchTableUpdater->isEnabled();
	}

	/**
	 * @see RebuildFulltextSearchTable::execute
	 *
	 * @since 2.5
	 *
	 * @return void|bool
	 */
	public function rebuild(): ?bool {
		if ( !$this->canRebuild() ) {
			return null;
		}

		if ( $this->optimization ) {
			return $this->doOptimize();
		}

		$this->doRebuild();

		return true;
	}

	/**
	 * Repeatedly runs rebuildChunk(). Optionally, accepts a
	 * maximum time for starting new batches.
	 *
	 * @since 7.3.2
	 * @see RebuildFulltextSearchTable::execute
	 *
	 * @param int $fromSid First subject ID to process (inclusive)
	 * @param int $batchSize Number of subject IDs per chunk
	 * @param int $maxTime Maximum time in seconds, 0 for no limit
	 *     (default), during which further chunks are allowed to be
	 *     started. Because the check is done between batches, a run
	 *     can exceed the runtime by the duration of one chunk/batch.
	 * @return array [<int|null>, <string>] The first item is the
	 *     `$fromSid` with which to resume, or null (if the rebuild has
	 *     been completed or the table updater is disabled); the second
	 *     reports on the status of the rebuild: 'success', 'failure' or
	 *     'cannot rebuild'
	 */
	public function rebuildInChunks(
		int $fromSid = 0,
		int $batchSize = self::DEFAULT_BATCH_SIZE,
		int $maxTime = 0
	): array {
		if ( !$this->canRebuild() ) {
			return [ null, 'cannot rebuild' ];
		}

		// Rebuild
		$status = 'success';
		$batchSize = min( $batchSize, self::MAX_BATCH_SIZE );
		$start = microtime( true );
		$cursor = $fromSid;

		while ( $cursor !== null ) {
			try {
				$cursor = $this->rebuildChunk( $cursor, $batchSize );
			} catch ( Throwable ) {
				// Don't throw
				$status = 'failure';
				break;
			}
			// maxTime
			if ( $cursor !== null && $maxTime > 0 && ( microtime( true ) - $start ) >= $maxTime ) {
				break;
			}
		}

		// Report
		$cliMsgFormatter = new CliMsgFormatter();
		// Ends the line that rebuildChunk keeps overwriting with
		// its progress
		$this->reportMessage( "\n" );
		$this->reportMessage( $cliMsgFormatter->section( "Unindexed table(s)", 3, '-', true ), $this->reportVerbose );

		foreach ( $this->skippedTables as $tableName => $reason ) {
			$this->reportMessage(
				$cliMsgFormatter->twoCols( "... $tableName", $reason, 3, '.' ),
				$this->reportVerbose
			);
		}

		return [ $cursor, $status ];
	}

	/**
	 * Rebuilds the index for a single chunk of subject IDs
	 * across all indexable property tables.
	 *
	 * Unlike rebuild(), does not flush the index. Instead a
	 * chunk's entries are collected from the property tables
	 * first and then replaced in one run. As a result:
	 * - The rest of the index remains searchable while the rebuild is running.
	 * - A chunk can safely be repeated, e.g. after a timeout, because
	 * old entries are deleted before the new ones are written.
	 *
	 * If indexable content happens to exceed `$max_bytes`, the ID range
	 * is shortened and the next cursor lowered accordingly.
	 *
	 * A caller is expected to keep calling this method with the
	 * returned `$cursor` until it returns null.
	 *
	 * @since 7.3.2
	 *
	 * @param int $fromSid First subject ID of the chunk (inclusive)
	 * @param int $batchSize Number of subject IDs in the chunk
	 *
	 * @throws Throwable
	 * @return int|null The `$fromSid` for the next chunk, or null if the last
	 *     chunk has been processed or the index cannot be rebuilt
	 */
	public function rebuildChunk( int $fromSid = 0, int $batchSize = self::DEFAULT_BATCH_SIZE ): ?int {
		if ( !$this->canRebuild() ) {
			return null;
		}

		$batchSize = min( $batchSize, self::MAX_BATCH_SIZE );
		$fromSid = max( 0, $fromSid );
		// The next subject ID to start from
		$toSid = $fromSid + max( 1, $batchSize );
		$maxSid = $this->getMaxSid();
		$maxBytes = self::MAX_BYTES;

		// Read first, then write

		$textsData = $this->collectTextsBySidRangeWithSize( $fromSid, $toSid );
		$texts = $textsData['texts'];
		$bytesBySid = $textsData['bytesBySid'];
		$totalBytes = $textsData['totalBytes'];

		if ( $totalBytes <= $maxBytes ) {
			$nextCursor = $toSid > $maxSid ? null : $toSid;
		} else {
			// Too large: cut range and lower the next cursor.
			// Because collectTextsBySidRangeWithSize() will be
			// invoked for the next run, some overhead is expected.
			$splitSid = $this->getByteSplitSid( $toSid, $bytesBySid, $maxBytes );
			$texts = $this->filterTextsBySidRange( $texts, $fromSid, $splitSid );
			$toSid = $nextCursor = $splitSid;
		}

		// Using MW's section transaction methods to prevent possible
		// outages from causing empty or incomplete ranges
		$this->connection->beginSectionTransaction( __METHOD__ );
		try {
			// Period between removing entries from the chunk and
			// repopulation must be kept as short as possible!
			$this->replaceBySidRange( $texts, $fromSid, $toSid );
			$this->connection->endSectionTransaction( __METHOD__ );
		} catch ( Throwable $e ) {
			$this->connection->cancelSectionTransaction( __METHOD__ );
			$this->messageReporter->reportMessage(
				"\nChunk starting at -s $fromSid failed: " . $e->getMessage() . "\n"
			);
			throw $e;
		}

		if ( $maxSid > 0 ) {
			$cliMsgFormatter = new CliMsgFormatter();
			$done = min( $toSid - 1, $maxSid );

			$this->reportMessage(
				$cliMsgFormatter->twoColsOverride(
					"... s_id " . $fromSid . " - " . max( $fromSid, $done ) . " ...",
					$cliMsgFormatter->progressCompact( $done, $maxSid ),
					3
				)
			);
		}

		return $nextCursor;
	}

	/**
	 * Runs the table updater to replace (delete, insert, update)
	 * indexable content in the specified range of subject IDs.
	 *
	 * @since 7.3.2
	 *
	 * @param array $texts See collectTextsBySidRangeWithSize()
	 * @param int $fromSid (inclusive)
	 * @param int $toSid (exclusive)
	 * @return void
	 */
	private function replaceBySidRange( array $texts, int $fromSid, int $toSid ): void {
		// Period between removing entries from the chunk and
		// repopulation must be kept as short as possible!
		$this->searchTableUpdater->deleteBySidRange( $fromSid, $toSid );

		foreach ( $texts as $sid => $props ) {
			foreach ( $props as $pid => $parts ) {
				$this->searchTableUpdater->insert( $sid, $pid );
				$this->searchTableUpdater->update( $sid, $pid, implode( ' ', $parts ) );
			}
		}
	}

	/**
	 * @since 3.0
	 */
	public function flushTable(): void {
		if ( $this->searchTableUpdater->isEnabled() ) {
			$this->searchTableUpdater->flushTable();
		}
	}

	/**
	 * @since 2.5
	 *
	 * @return array
	 */
	public function getQualifiedTableList(): array {
		$tableList = [];

		if ( !$this->searchTableUpdater->isEnabled() ) {
			return $tableList;
		}

		foreach ( $this->searchTableUpdater->getPropertyTables() as $proptable ) {

			if ( !$this->getSearchTable()->isValidByType( $proptable->getDiType() ) ) {
				continue;
			}

			$tableList[] = $proptable->getName();
		}

		return $tableList;
	}

	/**
	 * @since 2.5
	 *
	 * @param string $tableName
	 */
	public function rebuildByTable( $tableName ): void {
		foreach ( $this->searchTableUpdater->getPropertyTables() as $proptable ) {
			if ( $proptable->getName() === $tableName && $this->getSearchTable()->isValidByType( $proptable->getDiType() ) ) {
				$this->doRebuildByPropertyTable( $proptable );
			}
		}
	}

	/**
	 * Runs table optimization and reports to the CLI.
	 *
	 * @return bool
	 */
	private function doOptimize(): bool {
		$cliMsgFormatter = new CliMsgFormatter();

		$this->reportMessage(
			$cliMsgFormatter->section( 'optimization', 3, '-', true )
		);

		$text = [
			"Running table optimization (Depending on the SQL back-end",
			"this operation may lock the table and suspend any inserts or",
			"deletes during the process.)"
		];

		$this->reportMessage(
			"\n" . $cliMsgFormatter->wordwrap( $text ) . "\n"
		);

		if ( $this->searchTableUpdater->optimize() ) {
			$this->reportMessage( "\n   ... optimization has finished.\n" );
		} else {
			$this->reportMessage( "\nThe SQL back-end does not support this operation.\n" );
		}

		return true;
	}

	private function doRebuild(): void {
		$cliMsgFormatter = new CliMsgFormatter();
		$propertyTables = [];

		$this->reportMessage( "\nProcessing table(s) ..." );

		$this->reportMessage(
			"\n" . $cliMsgFormatter->firstCol( "... purging the index table ...", 3 )
		);

		$this->searchTableUpdater->flushTable();

		$this->reportMessage(
			$cliMsgFormatter->secondCol( CliMsgFormatter::OK )
		);

		$this->reportMessage(
			$cliMsgFormatter->firstCol( "... counting suitable table(s) ...", 3 )
		);

		foreach ( $this->searchTableUpdater->getPropertyTables() as $proptable ) {

			// Only care for Blob/Uri tables
			if ( !$this->getSearchTable()->isValidByType( $proptable->getDiType() ) ) {
				$this->skippedTables[$proptable->getName() ?? ''] = '[INVALID]';
				continue;
			}

			$propertyTables[] = $proptable;
		}

		$this->reportMessage(
			$cliMsgFormatter->secondCol( (string)count( $propertyTables ) )
		);

		foreach ( $propertyTables as $propertyTable ) {
			$this->doRebuildByPropertyTable( $propertyTable );
		}

		$this->reportMessage( "   ... done.\n" );

		$this->reportMessage(
			$cliMsgFormatter->section( "Unindexed table(s)", 3, '-', true ),
			$this->reportVerbose
		);

		$text = [
			"[INVALID] refers to an invalid `DataItem` type, [EMPTY] describes",
			"a table that contains no data, [EXEMPT] is exempted from processing"
		];

		$this->reportMessage(
			"\n" . $cliMsgFormatter->wordwrap( $text ) . "\n",
			$this->reportVerbose
		);

		$this->reportMessage(
			"\nList unprocessed table(s) ...\n",
			$this->reportVerbose
		);

		foreach ( $this->skippedTables as $tableName => $reason ) {
			$this->reportMessage(
				$cliMsgFormatter->twoCols( "... $tableName", $reason, 3, '.' ),
				$this->reportVerbose
			);
		}
	}

	private function doRebuildByPropertyTable( $proptable ) {
		$searchTable = $this->getSearchTable();
		$fetchFields = $this->getFetchFields( $proptable );

		$table = $proptable->getName();
		$pid = '';

		// Fixed tables don't have a p_id column therefore get it
		// from the ID TABLE
		if ( $proptable->isFixedPropertyTable() ) {
			$property = new Property( $proptable->getFixedProperty() );

			if ( $property->getLabel() === '' ) {
				$this->skippedTables[$table] = '[FIXED]';
				return $this->skippedTables[$table];
			}

			$pid = $searchTable->getIdByProperty(
				$property
			);

			if ( $searchTable->isExemptedPropertyById( $pid ) ) {
				$this->skippedTables[$table] = '[EXEMPT]';
				return $this->skippedTables[$table];
			}
		}

		$rows = $this->connection->newSelectQueryBuilder()
			->select( $fetchFields )
			->from( $table )
			->caller( __METHOD__ )
			->fetchResultSet();

		if ( !$rows->numRows() ) {
			$this->skippedTables[$table] = '[EMPTY]';
			return $this->skippedTables[$table];
		}

		$this->doRebuildFromRows( $searchTable, $table, $pid, $rows );
	}

	/**
	 * Fetches the columns that hold the indexable text,
	 * depending on the DataItem type.
	 *
	 * @since 7.3.2
	 *
	 * @return string[]
	 */
	private function getFetchFields( PropertyTableDefinition $proptable ): array {
		if ( $proptable->getDiType() === DataItem::TYPE_URI ) {
			$fetchFields = [ 's_id', 'p_id', 'o_blob', 'o_serialized' ];
		} elseif ( $proptable->getDiType() === DataItem::TYPE_WIKIPAGE ) {
			$fetchFields = [ 's_id', 'p_id', 'o_id' ];
		} else {
			$fetchFields = [ 's_id', 'p_id', 'o_blob', 'o_hash' ];
		}

		// Fixed property tables have no `p_id` column
		if ( $proptable->isFixedPropertyTable() ) {
			unset( $fetchFields[1] ); // p_id
		}

		return array_values( $fetchFields );
	}

	/**
	 * Helper function to retrieve the highest subject ID that
	 * can have index entries, i.e. the upper bound for the chunk
	 * cursor. Checks the ID table and the index itself so that
	 * stale index entries beyond the last known ID are cleaned up.
	 *
	 * @since 7.3.2
	 */
	private function getMaxSid(): int {
		$maxIdTable = (int)$this->connection->newSelectQueryBuilder()
			->select( 'MAX(smw_id)' )
			->from( SQLStore::ID_TABLE )
			->caller( __METHOD__ )
			->fetchField();

		$maxIndex = (int)$this->connection->newSelectQueryBuilder()
			->select( 'MAX(s_id)' )
			->from( $this->getSearchTable()->getTableName() )
			->caller( __METHOD__ )
			->fetchField();

		return max( $maxIdTable, $maxIndex );
	}

	private function doRebuildFromRows( SearchTable $searchTable, $table, $pid, $rows ) {
		$cliMsgFormatter = new CliMsgFormatter();

		$i = 0;
		$expected = $rows->numRows();

		if ( $expected == 0 ) {
			$this->skippedTables[$table] = '[EMPTY]';
			return $this->skippedTables[$table];
		}

		foreach ( $rows as $row ) {

			if ( $this->statsFlusher !== null ) {
				$this->statsFlusher->tick();
			}

			$sid = $row->s_id;
			$pid = !isset( $row->p_id ) ? $pid : $row->p_id;

			$indexableText = $this->getIndexableTextFromRow(
				$searchTable,
				$row
			);

			if ( $searchTable->isExemptedPropertyById( $pid ) ||
				!$searchTable->hasMinTokenLength( $indexableText ) ) {
				continue;
			}

			$progress = $cliMsgFormatter->progressCompact( ++$i, $expected );

			$this->reportMessage(
				$cliMsgFormatter->twoColsOverride( "... {$table} ...", $progress, 7 )
			);

			$text = $this->searchTableUpdater->read( $sid, $pid );

			// Unknown, so let's create the row
			if ( $text === false ) {
				$this->searchTableUpdater->insert( $sid, $pid );
			}

			$this->searchTableUpdater->update( $sid, $pid, trim( $text ?? '' ) . ' ' . $indexableText );
		}

		$this->reportMessage( "\n" );
	}

	private function reportMessage( string $message, bool $verbose = true ): void {
		if ( $verbose ) {
			$this->messageReporter->reportMessage( $message );
		}
	}

	/**
	 * Collects the indexable content of (eligible) property tables
	 * for subject IDs in the range from $fromSid to, but excluding,
	 * $toSid. Returns an array of texts as well as byte size info
	 *
	 * @since 7.3.2
	 *
	 * @param int $fromSid
	 * @param int $toSid
	 * @return array{
	 *     texts: array<int, array<int, list<string>>>,
	 *     bytesBySid: array<int, int>,
	 *     totalBytes: int
	 * } The `texts` array is keyed by `s_id`, each sub-array by `p_id`.
	 */
	private function collectTextsBySidRangeWithSize( int $fromSid, int $toSid ): array {
		$texts = [];
		$bytesBySid = [];
		$totalBytes = 0;
		$searchTable = $this->getSearchTable();

		foreach ( $this->getRowsForSidRange( $fromSid, $toSid ) as $k => $row ) {
			// pid must be derived from the key
			[ $sid, $pid ] = explode( ':', $k );
			$sid = (int)$sid;
			$pid = (int)$pid;

			$indexableText = $this->getIndexableTextFromRow( $searchTable, $row );

			// Exclude rows from exempted properties and those
			// with text below the minimum token length
			if ( $searchTable->isExemptedPropertyById( $pid ) ||
				!$searchTable->hasMinTokenLength( $indexableText ) ) {
				continue;
			}

			$texts[$sid][$pid][] = $indexableText;

			$bytes = strlen( $indexableText );
			$bytesBySid[$sid] = ( $bytesBySid[$sid] ?? 0 ) + $bytes;
			$totalBytes += $bytes;
		}

		return [
			'texts' => $texts,
			'bytesBySid' => $bytesBySid,
			'totalBytes' => $totalBytes,
		];
	}

	/**
	 * Helper function
	 *
	 * @since 7.3.2
	 *
	 * @param int $fromSid Subject ID to start from (inclusive)
	 * @param int $toSid Subject ID to stop at (exclusive)
	 * @return array Rows keyed by a concatenation of `s_id:p_id`
	 */
	private function getRowsForSidRange( $fromSid, $toSid ): array {
		$allRows = [];

		foreach ( $this->getChunkTables() as $spec ) {
			$rows = $this->connection->newSelectQueryBuilder()
				->select( $spec['fields'] )
				->from( $spec['table'] )
				->where( [
					$this->connection->expr( 's_id', '>=', $fromSid ),
					$this->connection->expr( 's_id', '<', $toSid ),
				] )
				->caller( __METHOD__ )
				->fetchResultSet();

			foreach ( $rows as $row ) {
				if ( $this->statsFlusher !== null ) {
					$this->statsFlusher->tick();
				}
				$sid = $row->s_id;
				// Fixed tables don't have a p_id column
				$pid = $row->p_id ?? $spec['pid'];
				$allRows[$sid . ':' . $pid] = $row;
			}
		}
		return $allRows;
	}

	/**
	 * Get the specifications of all property tables that are
	 * eligible for a chunked rebuild.
	 * Any table that's not eligible is recorded in `$skippedTables`
	 * (same as when a full rebuild is done).
	 *
	 * @since 7.3.2
	 *
	 * @return array[] Each entry has the keys `table`, `fields` and
	 *     `pid`, where `pid` is the fixed property's ID or an empty
	 *     string if the table has a `p_id` column
	 */
	private function getChunkTables(): array {
		if ( $this->chunkTables !== null ) {
			return $this->chunkTables;
		}

		$this->chunkTables = [];
		$searchTable = $this->getSearchTable();

		foreach ( $this->searchTableUpdater->getPropertyTables() as $proptable ) {
			$table = $proptable->getName() ?? '';

			if ( !$searchTable->isValidByType( $proptable->getDiType() ) ) {
				$this->skippedTables[$table] = '[INVALID]';
				continue;
			}

			$pid = '';

			if ( $proptable->isFixedPropertyTable() ) {
				$property = new Property( $proptable->getFixedProperty() );

				if ( $property->getLabel() === '' ) {
					$this->skippedTables[$table] = '[FIXED]';
					continue;
				}

				$pid = $searchTable->getIdByProperty( $property );

				if ( $searchTable->isExemptedPropertyById( $pid ) ) {
					$this->skippedTables[$table] = '[EXEMPT]';
					continue;
				}
			}

			$this->chunkTables[] = [
				'table' => $table,
				'fields' => $this->getFetchFields( $proptable ),
				'pid' => $pid
			];
		}

		return $this->chunkTables;
	}

	/**
	 * @param SearchTable $searchTable
	 * @param mixed $row
	 * @return string
	 */
	private function getIndexableTextFromRow( SearchTable $searchTable, $row ): string {
		$indexableText = '';

		// Page, Uri, or blob?
		if ( isset( $row->o_id ) ) {
			$dataItem = $searchTable->getDataItemById( $row->o_id );
			$indexableText = $dataItem instanceof DataItem ? $dataItem->getSortKey() : '';
		} elseif ( isset( $row->o_serialized ) ) {
			$indexableText = $row->o_blob === null ? $row->o_serialized : $row->o_blob;
		} elseif ( isset( $row->o_blob ) ) {
			$indexableText = $row->o_blob;
		} elseif ( isset( $row->o_hash ) ) {
			$indexableText = $row->o_hash;
		}

		return trim( $indexableText );
	}

	/**
	 * Returns a subject ID as the upper limit (exclusive) for a
	 * range whose indexable content does not exceed `$maxBytes`.
	 *
	 * @since 7.3.2
	 *
	 * @param int $toSid
	 * @param array<int, int> $bytesBySid See collectTextsBySidRangeWithSize()
	 * @param int $maxBytes
	 * @return int The subject ID to split on
	 */
	private function getByteSplitSid( int $toSid, array $bytesBySid, int $maxBytes ): int {
		ksort( $bytesBySid, SORT_NUMERIC );

		$bytes = 0;
		foreach ( $bytesBySid as $sid => $subjectBytes ) {
			if ( $bytes > 0 &&
				( $bytes + $subjectBytes > $maxBytes )
			) {
				return (int)$sid;
			}
			$bytes += $subjectBytes;
		}

		return $toSid;
	}

	/**
	 * @since 7.3.2
	 *
	 * @param array $texts See collectTextsBySidRangeWithSize()
	 * @param int $fromSid (inclusive)
	 * @param int $toSid (exclusive)
	 * @return array texts
	 */
	private function filterTextsBySidRange( array $texts, int $fromSid, int $toSid ): array {
		return array_filter(
			$texts,
			static fn ( $sid ) => $sid >= $fromSid && $sid < $toSid,
			ARRAY_FILTER_USE_KEY
		);
	}

}

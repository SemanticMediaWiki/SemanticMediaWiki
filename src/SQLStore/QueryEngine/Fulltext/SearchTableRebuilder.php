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
	 * @since 7.3.1
	 */
	public const DEFAULT_BATCH_SIZE = 2000;

	/**
	 * Property tables that take part in a chunked rebuild, resolved once per
	 * instance, see getChunkTables()
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
	 * A caller is expected to keep calling this method with the
	 * returned `$cursor` until it returns null.
	 *
	 * @since 7.3.1
	 *
	 * @param int $fromSid First subject ID of the chunk (inclusive)
	 * @param int $batchSize Number of subject IDs in the chunk
	 *
	 * @return int|null The `$fromSid` for the next chunk, or null if
	 * the last chunk has been processed or the index cannot be rebuilt
	 */
	public function rebuildChunk( int $fromSid = 0, int $batchSize = self::DEFAULT_BATCH_SIZE ): ?int {
		if ( !$this->canRebuild() ) {
			return null;
		}

		$fromSid = max( 0, $fromSid );
		// The next subject ID to start from
		$toSid = $fromSid + max( 1, $batchSize );
		$maxSid = $this->getMaxSid();

		// Read first, then write
		$texts = $this->collectTextsBySidRange( $fromSid, $toSid );

		// Remove index entries from the chunk. The period between
		// this and repopulation must be kept as short as possible!
		$this->searchTableUpdater->deleteBySidRange( $fromSid, $toSid );

		foreach ( $texts as $key => $parts ) {
			[ $sid, $pid ] = explode( ':', $key, 2 );

			$this->searchTableUpdater->insert( $sid, $pid );
			$this->searchTableUpdater->update( $sid, $pid, implode( ' ', $parts ) );
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

		return $toSid > $maxSid ? null : $toSid;
	}

	/**
	 * Repeatedly runs rebuildChunk(). Optionally, accepts a
	 * maximum runtime, which is checked between batches. The
	 * timing of this check means a run can exceed the runtime
	 * by the duration of one chunk/batch.
	 *
	 * @since 7.3.1
	 * @see RebuildFulltextSearchTable::execute
	 *
	 * @param int $fromSid First subject ID to process (inclusive)
	 * @param int $batchSize Number of subject IDs per chunk
	 * @param int $maxRuntime Maximum runtime in seconds, after which
	 * no further chunk is started; 0 for no limit (default)
	 *
	 * @return int|null the `$fromSid` with which to resume, or null if
	 * the rebuild has been completed
	 */
	public function rebuildInChunks(
		int $fromSid = 0,
		int $batchSize = self::DEFAULT_BATCH_SIZE,
		int $maxRuntime = 0
	): ?int {
		if ( !$this->canRebuild() ) {
			return null;
		}

		// Rebuild
		$start = microtime( true );
		$cursor = $fromSid;
		while ( $cursor !== null ) {
			$cursor = $this->rebuildChunk( $cursor, $batchSize );
			if ( $cursor !== null && $maxRuntime > 0 && ( microtime( true ) - $start ) >= $maxRuntime ) {
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

		return $cursor;
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
			"a table to contain no data, [EXEMPT] is exempted from processing"
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
	 * @since 7.3.1
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
	 * Get the specifications of all property tables that are
	 * eligible for a chunked rebuild.
	 * Any table that's not eligible is recorded in `$skippedTables`
	 * (same as when a full rebuild is done).
	 *
	 * @since 7.3.1
	 *
	 * @return array[] Each entry has the keys `table`, `fields` and
	 * `pid`, where `pid` is the fixed property's ID or an empty string
	 * if the table has a `p_id` column
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
	 * Collects the indexable content of (eligible) property tables
	 * for subject IDs in the range from $fromSid to, but excluding,
	 * $toSid, joined per `s_id:p_id`.
	 *
	 * @since 7.3.1
	 *
	 * @param int $fromSid Subject ID to start from (inclusive)
	 * @param int $toSid Subject ID to stop at (exclusive)
	 *
	 * @return array<string, string[]>
	 */
	private function collectTextsBySidRange( int $fromSid, int $toSid ): array {
		$searchTable = $this->getSearchTable();
		$texts = [];

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

				// Exclude rows from exempted properties and those
				// with text below the minimum token length
				$pid = $row->p_id ?? $spec['pid'];
				$indexableText = $this->getIndexableTextFromRow( $searchTable, $row );
				if ( $searchTable->isExemptedPropertyById( $pid ) ||
					!$searchTable->hasMinTokenLength( $indexableText ) ) {
					continue;
				}

				$texts[(int)$row->s_id . ':' . $pid][] = $indexableText;
			}
		}

		return $texts;
	}

	/**
	 * Helper function to retrieve the highest subject ID that
	 * can have index entries, i.e. the upper bound for the chunk
	 * cursor. Checks the ID table and the index itself so that
	 * stale index entries beyond the last known ID are cleaned up.
	 *
	 * @since 7.3.1
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

			if (
				$searchTable->isExemptedPropertyById( $pid ) ||
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

}

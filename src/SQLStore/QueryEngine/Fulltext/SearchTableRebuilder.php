<?php

namespace SMW\SQLStore\QueryEngine\Fulltext;

use Onoi\MessageReporter\MessageReporter;
use Onoi\MessageReporter\MessageReporterFactory;
use SMW\DataItems\DataItem;
use SMW\DataItems\Property;
use SMW\MediaWiki\Connection\Database;
use SMW\Utils\CliMsgFormatter;

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
				$this->skippedTables[$proptable->getName()] = '[INVALID]';
				continue;
			}

			$propertyTables[] = $proptable;
		}

		$this->reportMessage(
			$cliMsgFormatter->secondCol( count( $propertyTables ) )
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

		if ( $proptable->getDiType() === DataItem::TYPE_URI ) {
			$fetchFields = [ 's_id', 'p_id', 'o_blob', 'o_serialized' ];
		} elseif ( $proptable->getDiType() === DataItem::TYPE_WIKIPAGE ) {
			$fetchFields = [ 's_id', 'p_id', 'o_id' ];
		} else {
			$fetchFields = [ 's_id', 'p_id', 'o_blob', 'o_hash' ];
		}

		$table = $proptable->getName();
		$pid = '';

		// Fixed tables don't have a p_id column therefore get it
		// from the ID TABLE
		if ( $proptable->isFixedPropertyTable() ) {
			unset( $fetchFields[1] ); // p_id

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

		$rows = $this->connection->select(
			$table,
			$fetchFields,
			[],
			__METHOD__
		);

		if ( $rows === false || $rows === null ) {
			$this->skippedTables[$table] = '[EMPTY]';
			return $this->skippedTables[$table];
		}

		$this->doRebuildFromRows( $searchTable, $table, $pid, $rows );
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

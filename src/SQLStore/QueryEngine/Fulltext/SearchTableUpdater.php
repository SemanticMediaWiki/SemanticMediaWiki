<?php

namespace SMW\SQLStore\QueryEngine\Fulltext;

use SMW\MediaWiki\Connection\Database;
use Wikimedia\Rdbms\IDatabase;
use Wikimedia\Rdbms\Platform\ISQLPlatform;

/**
 * @license GPL-2.0-or-later
 * @since 2.5
 *
 * @author mwjames
 */
class SearchTableUpdater {

	/**
	 * @since 2.5
	 */
	public function __construct(
		private readonly Database $connection,
		private readonly SearchTable $searchTable,
		private readonly TextSanitizer $textSanitizer,
	) {
	}

	/**
	 * @since 2.5
	 *
	 * @return SearchTable
	 */
	public function getSearchTable(): SearchTable {
		return $this->searchTable;
	}

	/**
	 * @since 2.5
	 *
	 * @return bool
	 */
	public function isEnabled(): bool {
		return $this->searchTable->isEnabled();
	}

	/**
	 * @since 2.5
	 *
	 * @return array
	 */
	public function getPropertyTables() {
		return $this->searchTable->getPropertyTables();
	}

	/**
	 * @see http://dev.mysql.com/doc/refman/5.7/en/fulltext-fine-tuning.html
	 * @see http://dev.mysql.com/doc/refman/5.7/en/optimize-table.html
	 *
	 * "Running OPTIMIZE TABLE on a table with a full-text index rebuilds the
	 * full-text index, removing deleted Document IDs and consolidating multiple
	 * entries for the same word, where possible."
	 *
	 * @since 2.5
	 *
	 * @return bool
	 */
	public function optimize(): bool {
		if ( !$this->connection->isType( 'mysql' ) ) {
			return false;
		}

		$this->connection->query(
			"OPTIMIZE TABLE " . $this->searchTable->getTableName(),
			__METHOD__,
			ISQLPlatform::QUERY_CHANGE_SCHEMA
		);

		return true;
	}

	/**
	 * @since 2.5
	 *
	 * @param int|string $sid
	 * @param int|string $pid
	 *
	 * @return bool
	 */
	public function exists( $sid, $pid ): bool {
		$row = $this->connection->newSelectQueryBuilder()
			->select( [ 's_id' ] )
			->from( $this->searchTable->getTableName() )
			->where( [ 's_id' => (int)$sid, 'p_id' => (int)$pid ] )
			->limit( 1 )
			->caller( __METHOD__ )
			->fetchRow();

		return $row !== false;
	}

	/**
	 * @since 2.5
	 *
	 * @param int|string $sid
	 * @param int|string $pid
	 *
	 * @return false|string
	 */
	public function read( $sid, $pid ): false|string {
		$row = $this->connection->newSelectQueryBuilder()
			->select( [ 'o_text' ] )
			->from( $this->searchTable->getTableName() )
			->where( [ 's_id' => (int)$sid, 'p_id' => (int)$pid ] )
			->limit( 1 )
			->caller( __METHOD__ )
			->fetchRow();

		if ( $row === false ) {
			return false;
		}

		return $this->textSanitizer->sanitize( $row->o_text );
	}

	/**
	 * @since 2.5
	 *
	 * @param int|string $sid
	 * @param int|string $pid
	 * @param string $text
	 */
	public function update( $sid, $pid, $text ): void {
		$indexableText = $this->textSanitizer->sanitize( $text );
		if ( trim( $text ) === '' || $indexableText === '' ) {
			$this->delete( $sid, $pid );
			return;
		}

		$this->connection->newUpdateQueryBuilder()
			->update( $this->searchTable->getTableName() )
			->set( [
				'o_text' => $indexableText,
				'o_sort' => mb_substr( $text, 0, 32 ),
			] )
			->where( [ 's_id' => (int)$sid, 'p_id' => (int)$pid ] )
			->caller( __METHOD__ )
			->execute();
	}

	/**
	 * @since 2.5
	 *
	 * @param int|string $sid
	 * @param int|string $pid
	 */
	public function insert( $sid, $pid ): void {
		$this->connection->newInsertQueryBuilder()
			->insertInto( $this->searchTable->getTableName() )
			->row( [
				's_id'   => (int)$sid,
				'p_id'   => (int)$pid,
				'o_text' => '',
			] )
			->caller( __METHOD__ )
			->execute();
	}

	/**
	 * @since 2.5
	 *
	 * @param int|string $sid
	 * @param int|string $pid
	 */
	public function delete( $sid, $pid ): void {
		$this->connection->newDeleteQueryBuilder()
			->deleteFrom( $this->searchTable->getTableName() )
			->where( [ 's_id' => (int)$sid, 'p_id' => (int)$pid ] )
			->caller( __METHOD__ )
			->execute();
	}

	/**
	 * @since 2.5
	 */
	public function flushTable(): void {
		$this->connection->newDeleteQueryBuilder()
			->deleteFrom( $this->searchTable->getTableName() )
			->where( IDatabase::ALL_ROWS )
			->caller( __METHOD__ )
			->execute();
	}

}

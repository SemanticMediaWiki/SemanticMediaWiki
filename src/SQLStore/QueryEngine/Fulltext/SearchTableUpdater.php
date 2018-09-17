<?php

namespace SMW\SQLStore\QueryEngine\Fulltext;

use SMW\MediaWiki\Database;

/**
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class SearchTableUpdater {

	/**
	 * @var Database
	 */
	private $connection;

	/**
	 * @var SearchTable
	 */
	private $searchTable;

	/**
	 * @var TextSanitizer
	 */
	private $textSanitizer;

	/**
	 * @since 2.5
	 *
	 * @param Database $connection
	 * @param SearchTable $searchTable
	 * @param TextSanitizer $textSanitizer
	 */
	public function __construct( Database $connection, SearchTable $searchTable, TextSanitizer $textSanitizer ) {
		$this->connection = $connection;
		$this->searchTable = $searchTable;
		$this->textSanitizer = $textSanitizer;
	}

	/**
	 * @since 2.5
	 *
	 * @return SearchTable
	 */
	public function getSearchTable() {
		return $this->searchTable;
	}

	/**
	 * @since 2.5
	 *
	 * @return boolean
	 */
	public function isEnabled() {
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
	 * @return boolean
	 */
	public function optimize() {

		if ( !$this->connection->isType( 'mysql' ) ) {
			return false;
		}

		$this->connection->query(
			"OPTIMIZE TABLE " . $this->searchTable->getTableName(),
			__METHOD__
		);

		return true;
	}

	/**
	 * @since 2.5
	 *
	 * @param integer $sid
	 * @param integer $pid
	 *
	 * @return boolean
	 */
	public function exists( $sid, $pid ) {

		$row = $this->connection->selectRow(
			$this->searchTable->getTableName(),
			[ 's_id' ],
			[
				's_id' => (int)$sid,
				'p_id' => (int)$pid
			],
			__METHOD__
		);

		return $row !== false;
	}

	/**
	 * @since 2.5
	 *
	 * @param integer $sid
	 * @param integer $pid
	 *
	 * @return false|string
	 */
	public function read( $sid, $pid ) {
		$row = $this->connection->selectRow(
			$this->searchTable->getTableName(),
			[ 'o_text' ],
			[
				's_id' => (int)$sid,
				'p_id' => (int)$pid
			],
			__METHOD__
		);

		if ( $row === false ) {
			return false;
		}

		return $this->textSanitizer->sanitize( $row->o_text );
	}

	/**
	 * @since 2.5
	 *
	 * @param integer $sid
	 * @param integer $pid
	 * @param string $text
	 */
	public function update( $sid, $pid, $text ) {

		if ( trim( $text ) === '' || ( $indexableText = $this->textSanitizer->sanitize( $text ) ) === '' ) {
			return $this->delete( $sid, $pid );
		}

		$this->connection->update(
			$this->searchTable->getTableName(),
			[
				'o_text' => $indexableText,
				'o_sort' => mb_substr( $text, 0, 32 )
			],
			[
				's_id' => (int)$sid,
				'p_id' => (int)$pid
			],
			__METHOD__
		);
	}

	/**
	 * @since 2.5
	 *
	 * @param integer $sid
	 * @param integer $pid
	 */
	public function insert( $sid, $pid ) {
		$this->connection->insert(
			$this->searchTable->getTableName(),
			[
				's_id' => (int)$sid,
				'p_id' => (int)$pid,
				'o_text' => ''
			],
			__METHOD__
		);
	}

	/**
	 * @since 2.5
	 *
	 * @param integer $sid
	 * @param integer $pid
	 */
	public function delete( $sid, $pid ) {
		$this->connection->delete(
			$this->searchTable->getTableName(),
			[
				's_id' => (int)$sid,
				'p_id' => (int)$pid
			],
			__METHOD__
		);
	}

	/**
	 * @since 2.5
	 */
	public function flushTable() {
		$this->connection->delete(
			$this->searchTable->getTableName(),
			'*',
			__METHOD__
		);
	}

}

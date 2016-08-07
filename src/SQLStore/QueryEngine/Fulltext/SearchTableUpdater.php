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
	 * @var SearchTable
	 */
	private $searchTable;

	/**
	 * @var Database
	 */
	private $connection;

	/**
	 * @since 2.5
	 *
	 * @param SearchTable $searchTable
	 * @param Database $connection
	 */
	public function __construct( SearchTable $searchTable, Database $connection ) {
		$this->searchTable = $searchTable;
		$this->connection = $connection;
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
			array( 'o_text' ),
			array(
				's_id' => (int)$sid,
				'p_id' => (int)$pid
			),
			__METHOD__
		);

		if ( $row === false ) {
			return false;
		}

		return $this->searchTable->getTextSanitizer()->sanitize( $row->o_text );
	}

	/**
	 * @since 2.5
	 *
	 * @param integer $sid
	 * @param integer $pid
	 * @param string $text
	 */
	public function update( $sid, $pid, $text ) {

		if ( trim( $text ) === '' ) {
			return $this->delete( $sid, $pid );
		}

		$this->connection->update(
			$this->searchTable->getTableName(),
			array(
				'o_text' => $this->searchTable->getTextSanitizer()->sanitize( $text ),
				'o_sort' => mb_substr( $text, 0, 32 )
			),
			array(
				's_id' => (int)$sid,
				'p_id' => (int)$pid
			),
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
			array(
				's_id' => (int)$sid,
				'p_id' => (int)$pid,
				'o_text' => ''
			),
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
			array(
				's_id' => (int)$sid,
				'p_id' => (int)$pid
			),
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

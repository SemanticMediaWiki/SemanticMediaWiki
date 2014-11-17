<?php

namespace SMW\MediaWiki;

use SMW\DBConnectionProvider;

use DBError;
use ResultWrapper;
use UnexpectedValueException;
use RuntimeException;

/**
 * This adapter class covers MW DB specific operations. Changes to the
 * interface are likely therefore this class should not be used other than by
 * SMW itself.
 *
 * @license GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class Database {

	/**
	 * @var DBConnectionProvider
	 */
	private $readDBConnection = null;

	/**
	 * @var DBConnectionProvider
	 */
	private $writeDBConnection = null;

	/**
	 * @var boolean
	 */
	private $useWriteConnection = false;

	public function __construct( DBConnectionProvider $readDBConnection, DBConnectionProvider $writeDBConnection = null ) {
		$this->readDBConnection = $readDBConnection;
		$this->writeDBConnection = $writeDBConnection;
	}

	/**
	 * @see DatabaseBase::getType
	 *
	 * @since 1.9.1
	 *
	 * @return string
	 */
	public function getType() {
		return $this->readConnection()->getType();
	}

	/**
	 * @see DatabaseBase::tableName
	 *
	 * @since 1.9.0.2
	 *
	 * @param string $tableName
	 *
	 * @return string
	 */
	public function tableName( $tableName ) {

		if ( $this->getType() === 'sqlite' ) {
			return $GLOBALS['wgDBprefix'] . $tableName;
		}

		return $this->readConnection()->tableName( $tableName );
	}

	/**
	 * @see DatabaseBase::addQuotes
	 *
	 * @since 1.9.0.2
	 *
	 * @param string $tableName
	 *
	 * @return string
	 */
	public function addQuotes( $value ) {
		return $this->readConnection()->addQuotes( $value );
	}

	/**
	 * @see DatabaseBase::fetchObject
	 *
	 * @since 1.9.1
	 *
	 * @param ResultWrapper $res
	 *
	 * @return string
	 */
	public function fetchObject( $res ) {
		return $this->readConnection()->fetchObject( $res );
	}

	/**
	 * @see DatabaseBase::numRows
	 *
	 * @since 1.9.0.2
	 *
	 * @param mixed $results
	 *
	 * @return integer
	 */
	public function numRows( $results ) {
		return $this->readConnection()->numRows( $results );
	}

	/**
	 * @see DatabaseBase::freeResult
	 *
	 * @since 1.9.0.2
	 *
	 * @param ResultWrapper $res
	 */
	public function freeResult( $res ) {
		$this->readConnection()->freeResult( $res );
	}

	/**
	 * @see DatabaseBase::select
	 *
	 * @since 1.9.0.2
	 *
	 * @param string $tableName
	 * @param $fields
	 * @param $conditions
	 * @param array $options
	 *
	 * @return ResultWrapper
	 * @throws UnexpectedValueException
	 */
	public function select( $tableName, $fields, $conditions = '', $fname, array $options = array() ) {

		$tablePrefix = null;

		// MW's SQLite implementation adds an auto prefix to the tableName but
		// not to the conditions and since ::tableName will handle prefixing
		// consistently ensure that the select doesn't add an extra prefix
		if ( $this->getType() === 'sqlite' ) {
			$tablePrefix = $this->readConnection()->tablePrefix( '' );
		}

		try {
			$results = $this->readConnection()->select(
				$tableName,
				$fields,
				$conditions,
				$fname,
				$options
			);
		} catch  ( DBError $e ) {
			throw new RuntimeException (
				$e->getMessage() . "\n" .
				$e->getTraceAsString()
			);
		}

		if ( $tablePrefix !== null ) {
			$this->readConnection()->tablePrefix( $tablePrefix );
		}

		if ( $results instanceof ResultWrapper ) {
			return $results;
		}

		throw new UnexpectedValueException (
			'Expected a ResultWrapper for ' . "\n" .
			$tableName . "\n" .
			$fields . "\n" .
			$conditions
		);
	}

	/**
	 * @see DatabaseBase::query
	 *
	 * @since 1.9.1
	 *
	 * @param string $sql
	 * @param $fname
	 * @param $ignoreException
	 *
	 * @return ResultWrapper
	 * @throws RuntimeException
	 */
	public function query( $sql, $fname = __METHOD__, $ignoreException = false ) {

		if ( $this->getType() == 'postgres' ) {
			$sql = str_replace( 'IGNORE', '', $sql );
			$sql = str_replace( 'DROP TEMPORARY TABLE', 'DROP TABLE IF EXISTS', $sql );
		}

		if ( $this->getType() == 'sqlite' ) {
			$sql = str_replace( 'IGNORE', '', $sql );
			$sql = str_replace( 'TEMPORARY', 'TEMP', $sql );
			$sql = str_replace( 'ENGINE=MEMORY', '', $sql );
			$sql = str_replace( 'DROP TEMP', 'DROP', $sql );
			$sql = str_replace( 'TRUNCATE TABLE', 'DELETE FROM', $sql );
		}

		try {
			$results = $this->writeConnection()->query(
				$sql,
				$fname,
				$ignoreException
			);
		} catch ( DBError $e ) {
			throw new RuntimeException (
				$e->getMessage() . "\n" .
				$e->getTraceAsString()
			);
		}

		return $results;
	}

	/**
	 * @see DatabaseBase::selectRow
	 *
	 * @since 1.9.1
	 */
	public function selectRow( $table, $vars, $conds, $fname = __METHOD__,
		$options = array(), $joinConditions = array() ) {

		return $this->readConnection()->selectRow(
			$table,
			$vars,
			$conds,
			$fname,
			$options,
			$joinConditions
		);
	}

	/**
	 * @see DatabaseBase::affectedRows
	 *
	 * @since 1.9.1
	 *
	 * @return int
	 */
	function affectedRows() {
		return $this->readConnection()->affectedRows();
	}

	/**
	 * @see DatabaseBase::makeSelectOptions
	 *
	 * @since 1.9.1
	 *
	 * @param array $options
	 *
	 * @return array
	 */
	public function makeSelectOptions( $options ) {
		return $this->readConnection()->makeSelectOptions( $options );
	}

	/**
	 * @see DatabaseBase::nextSequenceValue
	 *
	 * @since 1.9.1
	 *
	 * @param string $seqName
	 *
	 * @return int|null
	 */
	public function nextSequenceValue( $seqName ) {
		return $this->writeConnection()->nextSequenceValue( $seqName );
	}

	/**
	 * @see DatabaseBase::insertId
	 *
	 * @since 1.9.1
	 *
	 * @return int
	 */
	function insertId() {
		return (int)$this->writeConnection()->insertId();
	}

	/**
	 * @see DatabaseBase::insert
	 *
	 * @since 1.9.1
	 */
	public function insert( $table, $rows, $fname = __METHOD__, $options = array() ) {
		return $this->writeConnection()->insert( $table, $rows, $fname, $options );
	}

	/**
	 * @see DatabaseBase::update
	 *
	 * @since 1.9.1
	 */
	function update( $table, $values, $conds, $fname = __METHOD__, $options = array() ) {
		return $this->writeConnection()->update( $table, $values, $conds, $fname, $options );
	}

	/**
	 * @see DatabaseBase::delete
	 *
	 * @since 1.9.1
	 */
	public function delete( $table, $conds, $fname = __METHOD__ ) {
		return $this->writeConnection()->delete( $table, $conds, $fname );
	}

	/**
	 * @see DatabaseBase::makeList
	 *
	 * @since 1.9.1
	 */
	public function makeList( $data, $mode ) {
		return $this->writeConnection()->makeList( $data, $mode );
	}

	/**
	 * @see DatabaseBase::tableExists
	 *
	 * @since 1.9.1
	 *
	 * @param string $table
	 * @param string $fname
	 *
	 * @return bool
	 */
	public function tableExists( $table, $fname = __METHOD__ ) {
		return $this->writeConnection()->tableExists( $table, $fname );
	}

	/**
	 * @see DatabaseBase::selectField
	 *
	 * @since 1.9.2
	 */
	public function selectField( $table, $fieldName, $conditions = '', $fname = __METHOD__, $options = array() ) {
		return $this->readConnection()->selectField( $table, $fieldName, $conditions, $fname, $options );
	}

	/**
	 * @see DatabaseBase::estimateRowCount
	 *
	 * @since 2.1
	 */
	public function estimateRowCount( $table, $vars = '*', $conditions = '', $fname = __METHOD__, $options = array() ) {
		return $this->readConnection()->estimateRowCount(
			$table,
			$vars,
			$conditions,
			$fname,
			$options
		);
	}

	private function readConnection() {
		return $this->readDBConnection->getConnection();
	}

	private function writeConnection() {

		if ( $this->writeDBConnection instanceof DBConnectionProvider ) {
			return $this->writeDBConnection->getConnection();
		}

		throw new RuntimeException( 'Expected a DBConnectionProvider instance' );
	}

}

<?php

namespace SMW\MediaWiki;

use DBError;
use Exception;
use ResultWrapper;
use RuntimeException;
use SMW\DBConnectionProvider;
use SMW\ApplicationFactory;
use UnexpectedValueException;

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
	private $readConnectionProvider = null;

	/**
	 * @var DBConnectionProvider
	 */
	private $writeConnectionProvider = null;

	/**
	 * @var Database
	 */
	private $readConnection;

	/**
	 * @var Database
	 */
	private $writeConnection;

	/**
	 * @var ILBFactory
	 */
	private $loadBalancerFactory;

	/**
	 * @var string
	 */
	private $dbPrefix = '';

	/**
	 * @var boolean
	 */
	private $resetTransactionProfiler = false;

	/**
	 * @since 1.9
	 *
	 * @param DBConnectionProvider $readConnectionProvider
	 * @param DBConnectionProvider|null $writeConnectionProvider
	 * @param ILBFactory|null $loadBalancerFactory
	 */
	public function __construct( DBConnectionProvider $readConnectionProvider, DBConnectionProvider $writeConnectionProvider = null, $loadBalancerFactory = null ) {
		$this->readConnectionProvider = $readConnectionProvider;
		$this->writeConnectionProvider = $writeConnectionProvider;
		$this->loadBalancerFactory = $loadBalancerFactory;

		if ( $this->loadBalancerFactory === null ) {
			$this->loadBalancerFactory = ApplicationFactory::getInstance()->create( 'DBLoadBalancerFactory' );
		}
	}

	/**
	 * @since 2.5
	 *
	 * @param string $type
	 *
	 * @return boolean
	 */
	public function isType( $type ) {
		return $this->readConnection()->getType() === $type;
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
	 * @since 2.1
	 *
	 * @param string $dbPrefix
	 */
	public function setDBPrefix( $dbPrefix ) {
		$this->dbPrefix = $dbPrefix;
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
			return $this->dbPrefix . $tableName;
		}

		return $this->readConnection()->tableName( $tableName );
	}

	/**
	 * @see DatabaseBase::timestamp
	 *
	 * @since 3.0
	 *
	 * @param integer $ts
	 *
	 * @return string
	 */
	public function timestamp( $ts = 0 ) {
		return $this->readConnection()->timestamp( $ts );
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
	 * @param array $joinConditions
	 *
	 * @return ResultWrapper
	 * @throws UnexpectedValueException
	 */
	public function select( $tableName, $fields, $conditions = '', $fname, array $options = array(), $joinConditions = array() ) {

		$tablePrefix = null;

		// MW's SQLite implementation adds an auto prefix to the tableName but
		// not to the conditions and since ::tableName will handle prefixing
		// consistently ensure that the select doesn't add an extra prefix
		if ( $this->getType() === 'sqlite' ) {
			$tablePrefix = $this->readConnection()->tablePrefix( '' );

			if ( isset( $options['ORDER BY'] ) ) {
				$options['ORDER BY'] = str_replace( 'RAND', 'RANDOM', $options['ORDER BY'] );
			}
		}

		try {
			$results = $this->readConnection()->select(
				$tableName,
				$fields,
				$conditions,
				$fname,
				$options,
				$joinConditions
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
	 * @note WithAutoCommit (#1605
	 * "... creating temporary tables in a transaction is not replication-safe
	 * and causes errors in MySQL 5.6. ...")
	 *
	 * @since 1.9.1
	 *
	 * @param string $sql
	 * @param string $fname
	 * @param boolean $ignoreException
	 * @param boolean $withAutoCommit
	 *
	 * @return ResultWrapper
	 * @throws RuntimeException
	 */
	public function query( $sql, $fname = __METHOD__, $ignoreException = false, $withAutoCommit = false ) {

		if ( !$this->isType( 'postgres' ) ) {
			$sql = str_replace( '@INT', '', $sql );
		}

		if ( $this->isType( 'postgres' ) ) {
			$sql = str_replace( '@INT', '::integer', $sql );
			$sql = str_replace( 'IGNORE', '', $sql );
			$sql = str_replace( 'DROP TEMPORARY TABLE', 'DROP TABLE IF EXISTS', $sql );
			$sql = str_replace( 'RAND()', ( strpos( $sql, 'DISTINCT' ) !== false ? '' : 'RANDOM()' ), $sql );
		}

		if ( $this->isType( 'sqlite' ) ) {
			$sql = str_replace( 'IGNORE', '', $sql );
			$sql = str_replace( 'TEMPORARY', 'TEMP', $sql );
			$sql = str_replace( 'ENGINE=MEMORY', '', $sql );
			$sql = str_replace( 'DROP TEMP', 'DROP', $sql );
			$sql = str_replace( 'TRUNCATE TABLE', 'DELETE FROM', $sql );
			$sql = str_replace( 'RAND', 'RANDOM', $sql );
		}

		$writeConnection = $this->writeConnection();

		// https://github.com/wikimedia/mediawiki/blob/42d5e6f43a00eb8bedc3532876125f74e3188343/includes/deferred/AutoCommitUpdate.php
		// https://github.com/wikimedia/mediawiki/blob/f7dad57c64db3eb1296894c2d3ae97b9f7f27c4c/includes/installer/DatabaseInstaller.php#L157
		if ( $withAutoCommit ) {
			$autoTrx = $writeConnection->getFlag( DBO_TRX );
			$writeConnection->clearFlag( DBO_TRX );

			if ( $autoTrx && $writeConnection->trxLevel() ) {
				$writeConnection->commit( __METHOD__ );
			}
		}

		try {
			$exception = null;
			$results = $writeConnection->query(
				$sql,
				$fname,
				$ignoreException
			);
		} catch ( Exception $exception ) {
		}

		if ( $withAutoCommit && $autoTrx ) {
			$writeConnection->setFlag( DBO_TRX );
		}

		if ( $exception ) {
			throw $exception;
		}

		return $results;
	}

	/**
	 * @see DatabaseBase::selectRow
	 *
	 * @since 1.9.1
	 */
	public function selectRow( $table, $vars, $conds, $fname = __METHOD__, $options = array(), $joinConditions = array() ) {
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
	 * @note Method was made protected in 1.28, hence the need
	 * for the DatabaseHelper that copies the functionality.
	 *
	 * @see DatabaseBase::makeSelectOptions
	 *
	 * @since 1.9.1
	 *
	 * @param array $options
	 *
	 * @return array
	 */
	public function makeSelectOptions( $options ) {
		return DatabaseHelper::makeSelectOptions( $this, $options );
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
	 * @note Use a blank trx profiler to ignore exceptions
	 *
	 * @since 2.4
	 */
	function resetTransactionProfiler( $resetTransactionProfiler ) {
		$this->resetTransactionProfiler = $resetTransactionProfiler;
	}

	/**
	 * @see DatabaseBase::clearFlag
	 *
	 * @since 2.4
	 */
	function clearFlag( $flag ) {
		$this->writeConnection()->clearFlag( $flag );
	}

	/**
	 * @see DatabaseBase::getFlag
	 *
	 * @since 2.4
	 */
	function getFlag( $flag ) {
		$this->writeConnection()->getFlag( $flag );
	}

	/**
	 * @see DatabaseBase::setFlag
	 *
	 * @since 2.4
	 */
	function setFlag( $flag ) {
		$this->writeConnection()->setFlag( $flag );
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
	 * @see DatabaseBase::replace
	 *
	 * @since 2.5
	 */
	public function replace( $table, $uniqueIndexes, $rows, $fname = __METHOD__ ) {
		return $this->writeConnection()->replace( $table, $uniqueIndexes, $rows, $fname );
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

	/**
	 * @note Only supported with 1.28+
	 * @since 3.0
	 *
	 * @param string $fname Caller name (e.g. __METHOD__)
	 *
	 * @return mixed A value to pass to commitAndWaitForReplication
	 */
	public function getEmptyTransactionTicket( $fname = __METHOD__ ) {

		if ( method_exists( $this->loadBalancerFactory, 'getEmptyTransactionTicket' ) ) {
			return $this->loadBalancerFactory->getEmptyTransactionTicket( $fname );
		}

		return null;
	}

	/**
	 * Convenience method for safely running commitMasterChanges/waitForReplication
	 * where it will allow to commit and wait for whena TransactionTicket is
	 * available.
	 *
	 * @note Only supported with 1.28+
	 *
	 * @since 3.0
	 *
	 * @param string $fname Caller name (e.g. __METHOD__)
	 * @param mixed $ticket Result of Database::getEmptyTransactionTicket
	 * @param array $opts Options to waitForReplication
	 */
	public function commitAndWaitForReplication( $fname, $ticket, array $opts = [] ) {

		if ( !is_int( $ticket ) || !method_exists( $this->loadBalancerFactory, 'commitAndWaitForReplication' ) ) {
			return;
		}

		return $this->loadBalancerFactory->commitAndWaitForReplication( $fname, $ticket, $opts );
	}

	/**
	 * @since 2.3
	 *
	 * @param string $fname
	 */
	public function beginAtomicTransaction( $fname = __METHOD__ ) {

		// MW 1.23
		if ( !method_exists( $this->writeConnection(), 'startAtomic' ) ) {
			return null;
		}

		$this->writeConnection()->startAtomic( $fname );
	}

	/**
	 * @since 2.3
	 *
	 * @param string $fname
	 */
	public function endAtomicTransaction( $fname = __METHOD__ ) {

		// MW 1.23
		if ( !method_exists( $this->writeConnection(), 'endAtomic' ) ) {
			return null;
		}

		$this->writeConnection()->endAtomic( $fname );
	}

	/**
	 * @since 2.3
	 *
	 * @param callable $callback
	 */
	public function onTransactionIdle( $callback ) {

		// FIXME For 1.19 it is an unknown method hence execute without idle
		if ( !method_exists( $this->readConnection(), 'onTransactionIdle' ) ) {
			return call_user_func( $callback );
		}

		$this->writeConnection()->onTransactionIdle( $callback );
	}

	private function readConnection() {

		if ( $this->readConnection !== null ) {
			return $this->readConnection;
		}

		return $this->readConnection = $this->readConnectionProvider->getConnection();
	}

	private function writeConnection() {

		if ( $this->writeConnection !== null ) {
			return $this->writeConnection;
		}

		if ( !$this->writeConnectionProvider instanceof DBConnectionProvider ) {
			throw new RuntimeException( 'Expected a DBConnectionProvider instance' );
		}

		$this->writeConnection = $this->writeConnectionProvider->getConnection();

		// MW 1.27 (only)
		if ( $this->resetTransactionProfiler && method_exists( $this->writeConnection, 'setTransactionProfiler' ) ) {
			$this->writeConnection->setTransactionProfiler( new \TransactionProfiler() );
		}

		return $this->writeConnection;
	}

}

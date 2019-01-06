<?php

namespace SMW\MediaWiki\Connection;

use DBError;
use Exception;
use ResultWrapper;
use RuntimeException;
use SMW\ApplicationFactory;
use SMW\Connection\ConnectionProviderRef;
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
	 * Identifies a request to be executed using an auto commit state
	 *
	 * @note (#1605 "... creating temporary tables in a transaction is not
	 * replication-safe and causes errors in MySQL 5.6. ...")
	 */
	const AUTO_COMMIT = 'auto.commit';

	/**
	 * @see IDatabase::TRIGGER_ROLLBACK
	 */
	const TRIGGER_ROLLBACK = 3;

	/**
	 * @var ConnectionProviderRef
	 */
	private $connectionProviderRef;

	/**
	 * @var ILBFactory
	 */
	private $loadBalancerFactory;

	/**
	 * @var Database
	 */
	private $readConnection;

	/**
	 * @var Database
	 */
	private $writeConnection;

	/**
	 * @var string
	 */
	private $dbPrefix = '';

	/**
	 * @var TransactionProfiler
	 */
	private $transactionProfiler;

	/**
	 * @var boolean
	 */
	private $initConnection = false;

	/**
	 * @var boolean
	 */
	private $autoCommit = false;

	/**
	 * @var integer
	 */
	private $insertId = null;

	/**
	 * @since 1.9
	 *
	 * @param ConnectionProviderRef $connectionProviderRef
	 * @param ILBFactory|null $loadBalancerFactory
	 */
	public function __construct( ConnectionProviderRef $connectionProviderRef, $loadBalancerFactory = null ) {
		$this->connectionProviderRef = $connectionProviderRef;
		$this->loadBalancerFactory = $loadBalancerFactory;

		if ( $this->loadBalancerFactory === null ) {
			$this->loadBalancerFactory = ApplicationFactory::getInstance()->create( 'DBLoadBalancerFactory' );
		}
	}

	/**
	 * @since 3.0
	 *
	 * @param TransactionProfiler $transactionProfiler
	 */
	public function setTransactionProfiler( TransactionProfiler $transactionProfiler ) {
		$this->transactionProfiler = $transactionProfiler;
	}

	/**
	 * @since 2.5
	 *
	 * @param string $type
	 *
	 * @return boolean
	 */
	public function releaseConnection() {
		$this->connectionProviderRef->releaseConnection();
	}

	/**
	 * @since 3.0
	 *
	 * @return boolean
	 */
	public function ping() {
		return true;
	}

	/**
	 * @since 3.0
	 *
	 * @return Query
	 */
	public function newQuery() {
		return new Query( $this );
	}

	/**
	 * @since 2.5
	 *
	 * @param string $type
	 *
	 * @return boolean
	 */
	public function isType( $type ) {

		if ( $this->initConnection === false ) {
			$this->initConnection();
		}

		return $this->readConnection->getType() === $type;
	}

	/**
	 * @see DatabaseBase::getServerInfo
	 *
	 * @since 3.0
	 *
	 * @return array
	 */
	public function getInfo() {

		if ( $this->initConnection === false ) {
			$this->initConnection();
		}

		return [ $this->getType() => $this->readConnection->getServerInfo() ];
	}

	/**
	 * @see DatabaseBase::getType
	 *
	 * @since 1.9
	 *
	 * @return string
	 */
	public function getType() {

		if ( $this->initConnection === false ) {
			$this->initConnection();
		}

		return $this->readConnection->getType();
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
	 * @since 1.9
	 *
	 * @param string $tableName
	 *
	 * @return string
	 */
	public function tableName( $tableName ) {

		if ( $this->initConnection === false ) {
			$this->initConnection();
		}

		if ( $this->getType() === 'sqlite' ) {
			return $this->dbPrefix . $tableName;
		}

		return $this->readConnection->tableName( $tableName );
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

		if ( $this->initConnection === false ) {
			$this->initConnection();
		}

		return $this->readConnection->timestamp( $ts );
	}

	/**
	 * @see DatabaseBase::tablePrefix
	 *
	 * @since 3.0
	 *
	 * @param string $prefix
	 *
	 * @return string
	 */
	public function tablePrefix( $prefix = null  ) {

		if ( $this->initConnection === false ) {
			$this->initConnection();
		}

		return $this->readConnection->tablePrefix( $prefix );
	}

	/**
	 * @see DatabaseBase::addQuotes
	 *
	 * @since 1.9
	 *
	 * @param string $tableName
	 *
	 * @return string
	 */
	public function addQuotes( $value ) {

		if ( $this->initConnection === false ) {
			$this->initConnection();
		}

		return $this->readConnection->addQuotes( $value );
	}

	/**
	 * @see DatabaseBase::fetchObject
	 *
	 * @since 1.9
	 *
	 * @param ResultWrapper $res
	 *
	 * @return string
	 */
	public function fetchObject( $res ) {

		if ( $this->initConnection === false ) {
			$this->initConnection();
		}

		return $this->readConnection->fetchObject( $res );
	}

	/**
	 * @see DatabaseBase::numRows
	 *
	 * @since 1.9
	 *
	 * @param mixed $results
	 *
	 * @return integer
	 */
	public function numRows( $results ) {

		if ( $this->initConnection === false ) {
			$this->initConnection();
		}

		return $this->readConnection->numRows( $results );
	}

	/**
	 * @see DatabaseBase::freeResult
	 *
	 * @since 1.9
	 *
	 * @param ResultWrapper $res
	 */
	public function freeResult( $res ) {

		if ( $this->initConnection === false ) {
			$this->initConnection();
		}

		$this->readConnection->freeResult( $res );
	}

	/**
	 * @see DatabaseBase::select
	 *
	 * @since 1.9
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
	public function select( $tableName, $fields, $conditions = '', $fname, array $options = [], $joinConditions = [] ) {

		$tablePrefix = null;

		if ( $this->initConnection === false ) {
			$this->initConnection();
		}

		if ( $this->getType() === 'sqlite' ) {

			// MW's SQLite implementation adds an auto prefix to the tableName but
			// not to the conditions and since ::tableName will handle prefixing
			// consistently ensure that the select doesn't add an extra prefix
			$tablePrefix = $this->readConnection->tablePrefix( '' );

			if ( isset( $options['ORDER BY'] ) ) {
				$options['ORDER BY'] = str_replace( 'RAND', 'RANDOM', $options['ORDER BY'] );
			}
		}

		try {
			$results = $this->readConnection->select(
				$tableName,
				$fields,
				$conditions,
				$fname,
				$options,
				$joinConditions
			);
		} catch ( DBError $e ) {
			throw new RuntimeException ( $e->getMessage() . "\n" . $e->getTraceAsString() );
		}

		if ( $tablePrefix !== null ) {
			$this->readConnection->tablePrefix( $tablePrefix );
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
	 * @since 1.9
	 *
	 * @param Query|string $sql
	 * @param string $fname
	 * @param boolean $ignoreException
	 *
	 * @return ResultWrapper
	 * @throws RuntimeException
	 */
	public function query( $sql, $fname = __METHOD__, $ignoreException = false ) {

		if ( $this->initConnection === false ) {
			$this->initConnection();
		}

		if ( $sql instanceof Query ) {
			$sql = $sql->build();
		}

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

		// https://github.com/wikimedia/mediawiki/blob/42d5e6f43a00eb8bedc3532876125f74e3188343/includes/deferred/AutoCommitUpdate.php
		// https://github.com/wikimedia/mediawiki/blob/f7dad57c64db3eb1296894c2d3ae97b9f7f27c4c/includes/installer/DatabaseInstaller.php#L157
		if ( $this->autoCommit ) {
			$autoTrx = $this->writeConnection->getFlag( DBO_TRX );
			$this->writeConnection->clearFlag( DBO_TRX );

			if ( $autoTrx && $this->writeConnection->trxLevel() ) {
				$this->writeConnection->commit( __METHOD__ );
			}
		}

		try {
			$exception = null;
			$results = $this->writeConnection->query(
				$sql,
				$fname,
				$ignoreException
			);
		} catch ( Exception $exception ) {
		}

		if ( $this->autoCommit && $autoTrx ) {
			$this->writeConnection->setFlag( DBO_TRX );
		}

		// State is only valid for a single transaction
		$this->autoCommit = false;

		if ( $exception ) {
			throw $exception;
		}

		return $results;
	}

	/**
	 * @see DatabaseBase::selectRow
	 *
	 * @since 1.9
	 */
	public function selectRow( $table, $vars, $conds, $fname = __METHOD__, $options = [], $joinConditions = [] ) {

		if ( $this->initConnection === false ) {
			$this->initConnection();
		}

		return $this->readConnection->selectRow(
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
	 * @since 1.9
	 *
	 * @return int
	 */
	function affectedRows() {

		if ( $this->initConnection === false ) {
			$this->initConnection();
		}

		return $this->readConnection->affectedRows();
	}

	/**
	 * @note Method was made protected in 1.28, hence the need
	 * for the DatabaseHelper that copies the functionality.
	 *
	 * @see DatabaseBase::makeSelectOptions
	 *
	 * @since 1.9
	 *
	 * @param array $options
	 *
	 * @return array
	 */
	public function makeSelectOptions( $options ) {
		return OptionsBuilder::makeSelectOptions( $this, $options );
	}

	/**
	 * @see DatabaseBase::nextSequenceValue
	 *
	 * @since 1.9
	 *
	 * @param string $seqName
	 *
	 * @return int|null
	 */
	public function nextSequenceValue( $seqName ) {
		$this->insertId = null;

		if ( $this->initConnection === false ) {
			$this->initConnection();
		}

		if ( !$this->isType( 'postgres' ) ) {
			return null;
		}

		// #3101, #2903
		// MW 1.31+
		// https://github.com/wikimedia/mediawiki/commit/0a9c55bfd39e22828f2d152ab71789cef3b0897c#diff-278465351b7c14bbcadac82036080e9f
		$safeseq = str_replace( "'", "''", $seqName );
		$res = $this->writeConnection->query( "SELECT nextval('$safeseq')" );
		$row = $this->readConnection->fetchRow( $res );

		return $this->insertId = is_null( $row[0] ) ? null : (int)$row[0];
	}

	/**
	 * @see DatabaseBase::insertId
	 *
	 * @since 1.9
	 *
	 * @return int
	 */
	function insertId() {

		if ( $this->initConnection === false ) {
			$this->initConnection();
		}

		if ( $this->insertId !== null ) {
			return $this->insertId;
		}

		return (int)$this->writeConnection->insertId();
	}

	/**
	 * @see DatabaseBase::clearFlag
	 *
	 * @since 2.4
	 */
	function clearFlag( $flag ) {

		if ( $this->initConnection === false ) {
			$this->initConnection();
		}

		$this->writeConnection->clearFlag( $flag );
	}

	/**
	 * @see DatabaseBase::getFlag
	 *
	 * @since 2.4
	 */
	function getFlag( $flag ) {

		if ( $this->initConnection === false ) {
			$this->initConnection();
		}

		return $this->writeConnection->getFlag( $flag );
	}

	/**
	 * @see DatabaseBase::setFlag
	 *
	 * @since 2.4
	 */
	function setFlag( $flag ) {

		if ( $this->initConnection === false ) {
			$this->initConnection();
		}

		if ( $flag === self::AUTO_COMMIT ) {
			return $this->autoCommit = true;
		}

		$this->writeConnection->setFlag( $flag );
	}

	/**
	 * @see DatabaseBase::insert
	 *
	 * @since 1.9
	 */
	public function insert( $table, $rows, $fname = __METHOD__, $options = [] ) {

		if ( $this->initConnection === false ) {
			$this->initConnection();
		}

		$oldSilenced = $this->transactionProfiler->setSilenced(
			true
		);

		$res = $this->writeConnection->insert( $table, $rows, $fname, $options );

		$this->transactionProfiler->setSilenced(
			$oldSilenced
		);

		return $res;
	}

	/**
	 * @see DatabaseBase::update
	 *
	 * @since 1.9
	 */
	function update( $table, $values, $conds, $fname = __METHOD__, $options = [] ) {

		if ( $this->initConnection === false ) {
			$this->initConnection();
		}

		$oldSilenced = $this->transactionProfiler->setSilenced(
			true
		);

		$res = $this->writeConnection->update( $table, $values, $conds, $fname, $options );

		$this->transactionProfiler->setSilenced(
			$oldSilenced
		);

		return $res;
	}

	/**
	 * @see DatabaseBase::delete
	 *
	 * @since 1.9
	 */
	public function delete( $table, $conds, $fname = __METHOD__ ) {

		if ( $this->initConnection === false ) {
			$this->initConnection();
		}

		$oldSilenced = $this->transactionProfiler->setSilenced(
			true
		);

		$res = $this->writeConnection->delete( $table, $conds, $fname );

		$this->transactionProfiler->setSilenced(
			$oldSilenced
		);

		return $res;
	}

	/**
	 * @see DatabaseBase::replace
	 *
	 * @since 2.5
	 */
	public function replace( $table, $uniqueIndexes, $rows, $fname = __METHOD__ ) {

		if ( $this->initConnection === false ) {
			$this->initConnection();
		}

		$oldSilenced = $this->transactionProfiler->setSilenced(
			true
		);

		$res = $this->writeConnection->replace( $table, $uniqueIndexes, $rows, $fname );

		$this->transactionProfiler->setSilenced(
			$oldSilenced
		);

		return $res;
	}

	/**
	 * @see DatabaseBase::makeList
	 *
	 * @since 1.9
	 */
	public function makeList( $data, $mode ) {

		if ( $this->initConnection === false ) {
			$this->initConnection();
		}

		return $this->writeConnection->makeList( $data, $mode );
	}

	/**
	 * @see DatabaseBase::tableExists
	 *
	 * @since 1.9
	 *
	 * @param string $table
	 * @param string $fname
	 *
	 * @return bool
	 */
	public function tableExists( $table, $fname = __METHOD__ ) {

		if ( $this->initConnection === false ) {
			$this->initConnection();
		}

		return $this->readConnection->tableExists( $table, $fname );
	}

	/**
	 * @see DatabaseBase::selectField
	 *
	 * @since 1.9.2
	 */
	public function selectField( $table, $fieldName, $conditions = '', $fname = __METHOD__, $options = [] ) {

		if ( $this->initConnection === false ) {
			$this->initConnection();
		}

		return $this->readConnection->selectField( $table, $fieldName, $conditions, $fname, $options );
	}

	/**
	 * @see DatabaseBase::estimateRowCount
	 *
	 * @since 2.1
	 */
	public function estimateRowCount( $table, $vars = '*', $conditions = '', $fname = __METHOD__, $options = [] ) {

		if ( $this->initConnection === false ) {
			$this->initConnection();
		}

		return $this->readConnection->estimateRowCount(
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

		$ticket = null;

		if ( !method_exists( $this->loadBalancerFactory, 'getEmptyTransactionTicket' ) ) {
			return $ticket;
		}

		// @see LBFactory::getEmptyTransactionTicket
		// We don't try very hard at this point and will continue without a ticket
		// if the check fails and hereby avoid a "... does not have outer scope" error
		if ( !$this->loadBalancerFactory->hasMasterChanges() ) {
			$ticket = $this->loadBalancerFactory->getEmptyTransactionTicket( $fname );
		}

		return $ticket;
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

		if ( $this->initConnection === false ) {
			$this->initConnection();
		}

		// MW 1.23
		if ( !method_exists( $this->writeConnection, 'startAtomic' ) ) {
			return null;
		}

		$this->writeConnection->startAtomic( $fname );
	}

	/**
	 * @since 2.3
	 *
	 * @param string $fname
	 */
	public function endAtomicTransaction( $fname = __METHOD__ ) {

		if ( $this->initConnection === false ) {
			$this->initConnection();
		}

		// MW 1.23
		if ( !method_exists( $this->writeConnection, 'endAtomic' ) ) {
			return null;
		}

		$this->writeConnection->endAtomic( $fname );
	}

	/**
	 * @since 3.0
	 *
	 * @param callable $callback
	 */
	public function onTransactionResolution( callable $callback, $fname = __METHOD__ ) {

		if ( $this->initConnection === false ) {
			$this->initConnection();
		}

		if ( method_exists( $this->writeConnection, 'onTransactionResolution' ) && $this->writeConnection->trxLevel() ) {
			$this->writeConnection->onTransactionResolution( $callback, $fname );
		}
	}

	/**
	 * @since 2.3
	 *
	 * @param callable $callback
	 */
	public function onTransactionIdle( $callback ) {

		if ( $this->initConnection === false ) {
			$this->initConnection();
		}

		// FIXME For 1.19 it is an unknown method hence execute without idle
		if ( !method_exists( $this->writeConnection, 'onTransactionIdle' ) ) {
			return call_user_func( $callback );
		}

		$this->writeConnection->onTransactionIdle( $callback );
	}

	private function initConnection() {

		if ( $this->readConnection === null ) {
			$this->readConnection = $this->connectionProviderRef->getConnection( 'read' );
		}

		if ( $this->writeConnection === null && $this->connectionProviderRef->hasConnection( 'write' ) ) {
			$this->writeConnection = $this->connectionProviderRef->getConnection( 'write' );
		}

		$this->initConnection = true;
	}

}

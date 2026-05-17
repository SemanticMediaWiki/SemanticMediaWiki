<?php

namespace SMW\MediaWiki\Connection;

use Exception;
use RuntimeException;
use SMW\Connection\ConnRef;
use Wikimedia\Rdbms\Blob;
use Wikimedia\Rdbms\DBConnRef;
use Wikimedia\Rdbms\DeleteQueryBuilder;
use Wikimedia\Rdbms\IDatabase;
use Wikimedia\Rdbms\IExpression;
use Wikimedia\Rdbms\InsertQueryBuilder;
use Wikimedia\Rdbms\IResultWrapper;
use Wikimedia\Rdbms\Platform\ISQLPlatform;
use Wikimedia\Rdbms\RawSQLValue;
use Wikimedia\Rdbms\ReplaceQueryBuilder;
use Wikimedia\Rdbms\SelectQueryBuilder;
use Wikimedia\Rdbms\UpdateQueryBuilder;

/**
 * This adapter class covers MW DB specific operations. Changes to the
 * interface are likely therefore this class should not be used other than by
 * SMW itself.
 *
 * **Façade guardrail.** As of 7.0.0 this class is a deliberately slim façade
 * over MW core's IDatabase. It may only expose:
 *
 * 1. QueryBuilder factories (`new*QueryBuilder()`) and the structured
 *    `insertSelect()` wrapper (no raw SQL — takes column maps and conds, MW
 *    core emits platform-correct INSERT...SELECT with the IGNORE option).
 * 2. Connection-routing helpers (`getType()`, `tableName()`, `tablePrefix()`,
 *    `tableExists()`, `listTables()`, `addQuotes()`, `expr()`, `conditional()`,
 *    `makeList()`, `timestamp()`, etc.).
 * 3. Transaction-handler plumbing (`getEmptyTransactionTicket()`,
 *    `commitAndWaitForReplication()`, atomic / section transaction helpers,
 *    `onTransactionCommitOrIdle()`).
 * 4. Platform-quirk escape hatches (`query()` / `readQuery()` for DDL and the
 *    residual planner-side raw SQL, `setFlag()` / `clearFlag()` / `getFlag()` /
 *    `nextSequenceValue()` / `insertId()` / `affectedRows()` / `ping()` for
 *    the few callers that genuinely need them).
 *
 * **No new SQL-passing methods, ever.** New consumers must use the
 * QueryBuilder factories. The Phase-2 follow-up drops the wrapper entirely;
 * adding methods here makes that landing harder.
 *
 * @license GPL-2.0-or-later
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
	const TRIGGER_ROLLBACK = IDatabase::TRIGGER_ROLLBACK;

	/**
	 * @see ISQLPlatform::LIST_COMMA
	 */
	const LIST_COMMA = ISQLPlatform::LIST_COMMA;

	private int|string|false $flags = 0;

	private ?int $insertId = null;

	/**
	 * @var string
	 */
	private $type = '';

	/**
	 * @since 1.9
	 */
	public function __construct(
		private readonly ConnRef $connRef,
		private readonly TransactionHandler $transactionHandler,
	) {
	}

	/**
	 * @since 2.5
	 */
	public function releaseConnection(): void {
		$this->connRef->releaseConnections();
	}

	/**
	 * @since 3.0
	 *
	 * @return bool
	 */
	public function ping(): bool {
		return true;
	}

	public function newSelectQueryBuilder(): SelectQueryBuilder {
		return $this->connRef->getConnection( 'read' )->newSelectQueryBuilder();
	}

	/**
	 * @since 7.0.0
	 */
	public function newInsertQueryBuilder(): InsertQueryBuilder {
		return $this->connRef->getConnection( 'write' )->newInsertQueryBuilder();
	}

	/**
	 * @since 7.0.0
	 */
	public function newUpdateQueryBuilder(): UpdateQueryBuilder {
		return $this->connRef->getConnection( 'write' )->newUpdateQueryBuilder();
	}

	/**
	 * @since 7.0.0
	 */
	public function newDeleteQueryBuilder(): DeleteQueryBuilder {
		return $this->connRef->getConnection( 'write' )->newDeleteQueryBuilder();
	}

	/**
	 * @since 7.0.0
	 */
	public function newReplaceQueryBuilder(): ReplaceQueryBuilder {
		return $this->connRef->getConnection( 'write' )->newReplaceQueryBuilder();
	}

	/**
	 * @since 2.5
	 *
	 * @param string $type
	 *
	 * @return bool
	 */
	public function isType( $type ): bool {
		if ( $this->type === '' ) {
			$this->type = $this->connRef->getConnection( 'read' )->getType();
		}

		return $this->type === $type;
	}

	/**
	 * @see IDatabase::getServerInfo
	 *
	 * @since 3.0
	 *
	 * @return array
	 */
	public function getInfo(): array {
		return [
			$this->getType() => $this->connRef->getConnection( 'read' )->getServerInfo()
		];
	}

	/**
	 * @see IDatabase::getType
	 *
	 * @since 1.9
	 *
	 * @return string
	 */
	public function getType(): string {
		if ( $this->type === '' ) {
			$this->type = $this->connRef->getConnection( 'read' )->getType();
		}

		return $this->type;
	}

	/**
	 * @see IDatabase::tableName
	 *
	 * @since 1.9
	 *
	 * @param string $tableName
	 * @param string $format
	 *
	 * @return string
	 */
	public function tableName( $tableName, $format = 'quoted' ) {
		return $this->connRef->getConnection( 'read' )->tableName( $tableName, $format );
	}

	/**
	 * @see IDatabase::timestamp
	 *
	 * @since 3.0
	 *
	 * @param string|int $ts
	 *
	 * @return string
	 */
	public function timestamp( $ts = 0 ) {
		return $this->connRef->getConnection( 'read' )->timestamp( $ts );
	}

	/**
	 * @see IDatabase::tablePrefix
	 *
	 * @since 3.0
	 *
	 * @param string|null $prefix
	 *
	 * @return string
	 */
	public function tablePrefix( $prefix = null ) {
		$connection = $this->connRef->getConnection( 'read' );

		// https://github.com/wikimedia/mediawiki/commit/6ab57b9c2424d9cc01b29908658b273a6ce75489
		// Avoid "DBUnexpectedError ... DBConnRef.php: Database selection is
		// disallowed to enable reuse ..."
		if ( $connection instanceof DBConnRef ) {
			return $connection->__call( __FUNCTION__, [ $prefix ] );
		}

		return $connection->tablePrefix( $prefix );
	}

	/**
	 * @see IDatabase::addQuotes
	 *
	 * @since 1.9
	 *
	 * @param ?scalar|RawSQLValue|Blob $value
	 * @param-taint $value escapes_sql
	 * @return string
	 * @return-taint none
	 */
	public function addQuotes( $value ) {
		return $this->connRef->getConnection( 'read' )->addQuotes( $value );
	}

	/**
	 * @see IDatabase::insertSelect
	 *
	 * @since 7.0.0
	 */
	public function insertSelect(
		string $destTable,
		string|array $srcTable,
		array $varMap,
		string|IExpression|array $conds,
		string $fname = __METHOD__,
		array $insertOptions = [],
		array $selectOptions = [],
		array $selectJoinConds = []
	): bool {
		return $this->connRef->getConnection( 'write' )->insertSelect(
			$destTable,
			$srcTable,
			$varMap,
			$conds,
			$fname,
			$insertOptions,
			$selectOptions,
			$selectJoinConds
		);
	}

	/**
	 * Execute a given SQL query on the primary DB.
	 *
	 * @see IDatabase::query
	 *
	 * @since 1.9
	 *
	 * @throws RuntimeException
	 */
	public function query( string $sql, string $fname = __METHOD__, int $flags = 0 ): bool|IResultWrapper {
		return $this->executeQuery(
			$this->connRef->getConnection( 'write' ),
			$sql,
			$fname,
			$flags
		);
	}

	/**
	 * Execute a given SQL query on a read-only replica DB.
	 *
	 * @see IDatabase::query()
	 * @since 4.0.0
	 *
	 * @throws Exception
	 */
	public function readQuery( string $sql, string $fname = __METHOD__, $flags = 0 ): bool|IResultWrapper {
		return $this->executeQuery(
			$this->connRef->getConnection( 'read' ),
			$sql,
			$fname,
			$flags | ISQLPlatform::QUERY_CHANGE_NONE
		);
	}

	/**
	 * Execute a SQL query using the given DB connection handle. Wraps the
	 * passthrough call in `Database::AUTO_COMMIT` flag handling so that a
	 * caller that requested `AUTO_COMMIT` (e.g. temp-table DDL) gets the
	 * underlying `DBO_TRX` flag flipped off for the duration of the query
	 * and restored afterward.
	 *
	 * @see IDatabase::query()
	 *
	 * @throws Exception
	 */
	private function executeQuery( IDatabase $connection, string $sql, ?string $fname, int $flags ): bool|IResultWrapper {
		// https://github.com/wikimedia/mediawiki/blob/42d5e6f43a00eb8bedc3532876125f74e3188343/includes/deferred/AutoCommitUpdate.php
		// https://github.com/wikimedia/mediawiki/blob/f7dad57c64db3eb1296894c2d3ae97b9f7f27c4c/includes/installer/DatabaseInstaller.php#L157
		$autoTrx = null;
		if ( $this->flags === self::AUTO_COMMIT ) {
			$autoTrx = $connection->getFlag( DBO_TRX );
			$connection->clearFlag( DBO_TRX );

			if ( $autoTrx && $connection->trxLevel() ) {
				$connection->commit( __METHOD__ );
			}
		}

		$exception = null;
		try {
			$results = $connection->query(
				$sql,
				$fname,
				$flags
			);
		} catch ( Exception $exception ) {
		}

		if ( $this->flags === self::AUTO_COMMIT && $autoTrx ) {
			$connection->setFlag( DBO_TRX );
		}

		// State is only valid for a single transaction
		$this->flags = false;

		if ( $exception ) {
			throw $exception;
		}

		// @phan-suppress-next-line PhanPossiblyUndeclaredVariable
		return $results;
	}

	/**
	 * @see IDatabase::conditional
	 *
	 * @since 5.0
	 */
	public function conditional( $cond, $caseTrueExpression, $caseFalseExpression ) {
		return $this->connRef->getConnection( 'read' )->conditional( $cond, $caseTrueExpression, $caseFalseExpression );
	}

	/**
	 * @see IDatabase::expr
	 *
	 * @since 5.0
	 */
	public function expr( string $field, string $op, $value ) {
		return $this->connRef->getConnection( 'read' )->expr( $field, $op, $value );
	}

	/**
	 * @see IDatabase::affectedRows
	 *
	 * @since 1.9
	 *
	 * @return int
	 */
	public function affectedRows() {
		return $this->connRef->getConnection( 'read' )->affectedRows();
	}

	/**
	 * @see removed method IDatabase::nextSequenceValue
	 *
	 * @since 1.9
	 */
	public function nextSequenceValue( string $seqName ): ?int {
		$this->insertId = null;

		if ( !$this->isType( 'postgres' ) ) {
			return null;
		}

		// #3101, #2903 — Postgres-only sequence advance.
		// `nextval()` has no portable Rdbms abstraction, so route a raw
		// expression through newSelectQueryBuilder()->fetchField(). The
		// FROMless SELECT is emitted natively by SQLPlatform::selectSQLText
		// when `tables` is empty.
		$safeseq = str_replace( "'", "''", $seqName );
		$value = $this->connRef->getConnection( 'write' )->newSelectQueryBuilder()
			->select( "nextval('$safeseq')" )
			->caller( __METHOD__ )
			->fetchField();

		$this->insertId = $value === false || $value === null ? null : (int)$value;
		return $this->insertId;
	}

	/**
	 * @see IDatabase::insertId
	 *
	 * @since 1.9
	 *
	 * @return int
	 */
	public function insertId(): int {
		if ( $this->insertId !== null ) {
			return $this->insertId;
		}

		return (int)$this->connRef->getConnection( 'write' )->insertId();
	}

	/**
	 * @see MWDatabase::clearFlag
	 *
	 * @since 2.4
	 */
	public function clearFlag( $flag ): void {
		$this->connRef->getConnection( 'write' )->clearFlag( $flag );
	}

	/**
	 * @see MWDatabase::getFlag
	 *
	 * @since 2.4
	 */
	public function getFlag( $flag ) {
		return $this->connRef->getConnection( 'write' )->getFlag( $flag );
	}

	/**
	 * @see MWDatabase::setFlag
	 *
	 * @since 2.4
	 */
	public function setFlag( $flag ): void {
		if ( $flag === self::AUTO_COMMIT ) {
			$this->flags = self::AUTO_COMMIT;
			return;
		}

		$this->connRef->getConnection( 'write' )->setFlag( $flag );
	}

	/**
	 * @see IDatabase::makeList
	 *
	 * @since 1.9
	 */
	public function makeList( $data, $mode = self::LIST_COMMA ) {
		return $this->connRef->getConnection( 'read' )->makeList( $data, $mode );
	}

	/**
	 * @see IDatabase::tableExists
	 *
	 * @since 1.9
	 *
	 * @param string $table
	 * @param string $fname
	 *
	 * @return bool
	 */
	public function tableExists( $table, $fname = __METHOD__ ) {
		return $this->connRef->getConnection( 'read' )->tableExists( $table, $fname );
	}

	/**
	 * @see IDatabase::listTables
	 *
	 * @since 3.1
	 */
	public function listTables( ?string $prefix = null, string $fname = __METHOD__ ): array {
		return $this->connRef->getConnection( 'read' )->listTables( $prefix, $fname );
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
		return $this->transactionHandler->getEmptyTransactionTicket( $fname );
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
		return $this->transactionHandler->commitAndWaitForReplication( $fname, $ticket, $opts );
	}

	/**
	 * @since 3.1
	 *
	 * @param string $fname
	 *
	 * @throws RuntimeException
	 */
	public function beginSectionTransaction( $fname = __METHOD__ ): void {
		$this->transactionHandler->markSectionTransaction(
			$fname
		);

		$this->connRef->getConnection( 'write' )->startAtomic( $fname );
	}

	/**
	 * @since 3.1
	 *
	 * @param string $fname
	 */
	public function endSectionTransaction( $fname = __METHOD__ ): void {
		$this->transactionHandler->detachSectionTransaction(
			$fname
		);

		$this->connRef->getConnection( 'write' )->endAtomic( $fname );
	}

	/**
	 * @since 3.1
	 *
	 * @param string $fname
	 *
	 * @return bool
	 */
	public function inSectionTransaction( $fname = __METHOD__ ): bool {
		return $this->transactionHandler->inSectionTransaction( $fname );
	}

	/**
	 * @since 2.3
	 *
	 * @param string $fname
	 */
	public function beginAtomicTransaction( $fname = __METHOD__ ): void {
		// Disable all individual atomic transactions as long as a section
		// transaction is registered.
		if ( $this->transactionHandler->hasActiveSectionTransaction() ) {
			return;
		}

		$this->connRef->getConnection( 'write' )->startAtomic( $fname );
	}

	/**
	 * @since 2.3
	 *
	 * @param string $fname
	 *
	 * @return void
	 */
	public function endAtomicTransaction( $fname = __METHOD__ ): void {
		// Disable all individual atomic transactions as long as a section
		// transaction is registered.
		if ( $this->transactionHandler->hasActiveSectionTransaction() ) {
			return;
		}

		$this->connRef->getConnection( 'write' )->endAtomic( $fname );
	}

	/**
	 * @since 3.0
	 *
	 * @param callable $callback
	 */
	public function onTransactionResolution( callable $callback, $fname = __METHOD__ ): void {
		$connection = $this->connRef->getConnection( 'write' );

		if ( $connection->trxLevel() ) {
			$connection->onTransactionResolution( $callback, $fname );
		}
	}

	/**
	 * @since 2.3
	 *
	 * @param callable $callback
	 */
	public function onTransactionCommitOrIdle( callable $callback ): void {
		$connection = $this->connRef->getConnection( 'write' );
		$connection->onTransactionCommitOrIdle( $callback );
	}

	/**
	 * @since 3.1
	 *
	 * @param string $text
	 *
	 * @return string
	 */
	public function escape_bytea( $text ): string {
		if ( $this->isType( 'postgres' ) ) {
			// normally one uses pg_escape_bytea PHP function to do this
			// unfortunately pg_escape_bytea requires a PgSql\Connection as of PHP 8.1+
			// this seems to be quite hard to get so here is a PHP alternative
			// Emit the bytea-hex input format instead (uniform since PostgreSQL 9.0+)
			$text = (string)( $text ?? '' );
			return '\\x' . bin2hex( $text );
		}

		return $text;
	}

	/**
	 * @since 3.1
	 */
	public function unescape_bytea( ?string $text ): ?string {
		if ( $this->isType( 'postgres' ) ) {
			$text = pg_unescape_bytea( $text ?? '' );
		}

		return $text;
	}
}

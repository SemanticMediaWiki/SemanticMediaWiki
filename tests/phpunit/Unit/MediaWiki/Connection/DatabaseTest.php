<?php

namespace SMW\Tests\Unit\MediaWiki\Connection;

use PHPUnit\Framework\TestCase;
use SMW\Connection\ConnectionProvider;
use SMW\Connection\ConnRef;
use SMW\MediaWiki\Connection\Database;
use SMW\MediaWiki\Connection\TransactionHandler;
use Wikimedia\Rdbms\DBError;
use Wikimedia\Rdbms\DeleteQueryBuilder;
use Wikimedia\Rdbms\FakeResultWrapper;
use Wikimedia\Rdbms\IDatabase;
use Wikimedia\Rdbms\ILBFactory;
use Wikimedia\Rdbms\InsertQueryBuilder;
use Wikimedia\Rdbms\IResultWrapper;
use Wikimedia\Rdbms\ReplaceQueryBuilder;
use Wikimedia\Rdbms\ResultWrapper;
use Wikimedia\Rdbms\UpdateQueryBuilder;

/**
 * @covers \SMW\MediaWiki\Connection\Database
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 1.9
 *
 * @author mwjames
 */
class DatabaseTest extends TestCase {

	private $connRef;
	private $transactionHandler;

	protected function setUp(): void {
		parent::setUp();

		$this->connRef = $this->getMockBuilder( ConnRef::class )
			->disableOriginalConstructor()
			->getMock();

		$this->transactionHandler = $this->getMockBuilder( TransactionHandler::class )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			Database::class,
			new Database( $this->connRef, $this->transactionHandler )
		);
	}

	public function testAddQuotesMethod() {
		$database = $this->getMockBuilder( '\Wikimedia\Rdbms\Database' )
			->disableOriginalConstructor()
			->setMethods( [ 'addQuotes' ] )
			->getMockForAbstractClass();

		$database->expects( $this->once() )
			->method( 'addQuotes' )
			->with( 'Fan' )
			->willReturn( 'Fan' );

		$connectionProvider = $this->getMockBuilder( ConnectionProvider::class )
			->disableOriginalConstructor()
			->getMock();

		$connectionProvider->expects( $this->atLeastOnce() )
			->method( 'getConnection' )
			->willReturn( $database );

		$instance = new Database(
			new ConnRef(
				[
					'read' => $connectionProvider
				]
			),
			$this->transactionHandler
		);

		$this->assertEquals(
			'Fan',
			$instance->addQuotes( 'Fan' )
		);
	}

	/**
	 * @dataProvider dbTypeProvider
	 */
	public function testTableNameMethod( $type ) {
		$database = $this->getMockBuilder( '\Wikimedia\Rdbms\Database' )
			->disableOriginalConstructor()
			->setMethods( [ 'tableName' ] )
			->getMockForAbstractClass();

		$database->expects( $this->any() )
			->method( 'tableName' )
			->willReturnArgument( 0 );

		$connectionProvider = $this->getMockBuilder( ConnectionProvider::class )
			->disableOriginalConstructor()
			->getMock();

		$connectionProvider->expects( $this->atLeastOnce() )
			->method( 'getConnection' )
			->willReturn( $database );

		$instance = new Database(
			new ConnRef(
				[
					'read' => $connectionProvider
				]
			),
			$this->transactionHandler
		);

		$this->assertEquals(
			'Foo',
			$instance->tableName( 'Foo' )
		);
	}

	public function testQueryPassesSqlThroughToUnderlyingConnection() {
		$resultWrapper = $this->getMockBuilder( ResultWrapper::class )
			->disableOriginalConstructor()
			->getMock();

		$readConnectionProvider = $this->getMockBuilder( ConnectionProvider::class )
			->disableOriginalConstructor()
			->getMock();

		$write = $this->getMockBuilder( '\Wikimedia\Rdbms\Database' )
			->disableOriginalConstructor()
			->setMethods( [ 'query' ] )
			->getMockForAbstractClass();

		$write->expects( $this->once() )
			->method( 'query' )
			->with( 'SELECT 1' )
			->willReturn( $resultWrapper );

		$writeConnectionProvider = $this->getMockBuilder( ConnectionProvider::class )
			->disableOriginalConstructor()
			->getMock();

		$writeConnectionProvider->expects( $this->atLeastOnce() )
			->method( 'getConnection' )
			->willReturn( $write );

		$instance = new Database(
			new ConnRef(
				[
					'read'  => $readConnectionProvider,
					'write' => $writeConnectionProvider
				]
			),
			$this->transactionHandler
		);

		$this->assertInstanceOf(
			ResultWrapper::class,
			$instance->query( 'SELECT 1' )
		);
	}

	public function testQueryThrowsException() {
		$database = $this->getMockBuilder( '\Wikimedia\Rdbms\Database' )
			->disableOriginalConstructor()
			->setMethods( [ 'query' ] )
			->getMockForAbstractClass();

		$databaseException = new DBError( $database, 'foo' );

		$database->expects( $this->once() )
			->method( 'query' )
			->willThrowException( $databaseException );

		$connectionProvider = $this->getMockBuilder( ConnectionProvider::class )
			->disableOriginalConstructor()
			->getMock();

		$connectionProvider->expects( $this->any() )
			->method( 'getConnection' )
			->willReturn( $database );

		$instance = new Database(
			new ConnRef(
				[
					'read'  => $connectionProvider,
					'write' => $connectionProvider
				]
			),
			$this->transactionHandler
		);

		$this->expectException( 'Exception' );
		$instance->query( 'Foo', __METHOD__ );
	}

	public function testGetEmptyTransactionTicket() {
		$readConnectionProvider = $this->getMockBuilder( ConnectionProvider::class )
			->disableOriginalConstructor()
			->getMock();

		$writeConnectionProvider = $this->getMockBuilder( ConnectionProvider::class )
			->disableOriginalConstructor()
			->getMock();

		$this->transactionHandler->expects( $this->once() )
			->method( 'getEmptyTransactionTicket' );

		$instance = new Database(
			new ConnRef(
				[
					'read'  => $readConnectionProvider,
					'write' => $writeConnectionProvider
				]
			),
			$this->transactionHandler
		);

		$instance->getEmptyTransactionTicket( __METHOD__ );
	}

	public function testCommitAndWaitForReplication() {
		$readConnectionProvider = $this->getMockBuilder( ConnectionProvider::class )
			->disableOriginalConstructor()
			->getMock();

		$writeConnectionProvider = $this->getMockBuilder( ConnectionProvider::class )
			->disableOriginalConstructor()
			->getMock();

		$this->transactionHandler->expects( $this->once() )
			->method( 'commitAndWaitForReplication' );

		$instance = new Database(
			new ConnRef(
				[
					'read'  => $readConnectionProvider,
					'write' => $writeConnectionProvider
				]
			),
			$this->transactionHandler
		);

		$instance->commitAndWaitForReplication( __METHOD__, 123 );
	}

	public function testBeginSectionTransaction() {
		$readConnectionProvider = $this->getMockBuilder( ConnectionProvider::class )
			->disableOriginalConstructor()
			->getMock();

		$read = $this->getMockBuilder( IDatabase::class )
			->disableOriginalConstructor()
			->getMock();

		$readConnectionProvider->expects( $this->any() )
			->method( 'getConnection' )
			->willReturn( $read );

		$write = $this->getMockBuilder( IDatabase::class )
			->disableOriginalConstructor()
			->getMock();

		$write->expects( $this->once() )
			->method( 'startAtomic' )
			->with( $this->anything(), IDatabase::ATOMIC_CANCELABLE );

		$write->expects( $this->never() )
			->method( 'endAtomic' );

		$writeConnectionProvider = $this->getMockBuilder( ConnectionProvider::class )
			->disableOriginalConstructor()
			->getMock();

		$writeConnectionProvider->expects( $this->atLeastOnce() )
			->method( 'getConnection' )
			->willReturn( $write );

		$this->transactionHandler->expects( $this->atLeastOnce() )
			->method( 'markSectionTransaction' );

		$this->transactionHandler->expects( $this->atLeastOnce() )
			->method( 'hasActiveSectionTransaction' )
			->willReturn( true );

		$instance = new Database(
			new ConnRef(
				[
					'read'  => $readConnectionProvider,
					'write' => $writeConnectionProvider
				]
			),
			$this->transactionHandler
		);

		$instance->beginSectionTransaction( __METHOD__ );

		// Other atomic requests are disabled
		$instance->beginAtomicTransaction( 'Foo' );
		$instance->endAtomicTransaction( 'Foo' );
	}

	public function testBeginEndSectionTransaction() {
		$readConnectionProvider = $this->getMockBuilder( ConnectionProvider::class )
			->disableOriginalConstructor()
			->getMock();

		$read = $this->getMockBuilder( IDatabase::class )
			->disableOriginalConstructor()
			->getMock();

		$readConnectionProvider->expects( $this->any() )
			->method( 'getConnection' )
			->willReturn( $read );

		$write = $this->getMockBuilder( IDatabase::class )
			->disableOriginalConstructor()
			->getMock();

		$write->expects( $this->once() )
			->method( 'startAtomic' )
			->with( $this->anything(), IDatabase::ATOMIC_CANCELABLE );

		$write->expects( $this->once() )
			->method( 'endAtomic' );

		$writeConnectionProvider = $this->getMockBuilder( ConnectionProvider::class )
			->disableOriginalConstructor()
			->getMock();

		$writeConnectionProvider->expects( $this->atLeastOnce() )
			->method( 'getConnection' )
			->willReturn( $write );

		$this->transactionHandler->expects( $this->atLeastOnce() )
			->method( 'markSectionTransaction' );

		$this->transactionHandler->expects( $this->atLeastOnce() )
			->method( 'detachSectionTransaction' );

		$this->transactionHandler->expects( $this->atLeastOnce() )
			->method( 'inSectionTransaction' )
			->willReturn( true );

		$instance = new Database(
			new ConnRef(
				[
					'read'  => $readConnectionProvider,
					'write' => $writeConnectionProvider
				]
			),
			$this->transactionHandler
		);

		$instance->beginSectionTransaction( __METHOD__ );

		$this->assertTrue(
			$instance->inSectionTransaction( __METHOD__ )
		);

		$instance->endSectionTransaction( __METHOD__ );
	}

	public function testCancelSectionTransaction() {
		$readConnectionProvider = $this->getMockBuilder( ConnectionProvider::class )
			->disableOriginalConstructor()
			->getMock();

		$read = $this->getMockBuilder( IDatabase::class )
			->disableOriginalConstructor()
			->getMock();

		$readConnectionProvider->expects( $this->any() )
			->method( 'getConnection' )
			->willReturn( $read );

		$write = $this->getMockBuilder( IDatabase::class )
			->disableOriginalConstructor()
			->getMock();

		$write->expects( $this->once() )
			->method( 'startAtomic' )
			->with( $this->anything(), IDatabase::ATOMIC_CANCELABLE );

		// A cancelled section rolls back via cancelAtomic, never endAtomic.
		$write->expects( $this->once() )
			->method( 'cancelAtomic' );

		$write->expects( $this->never() )
			->method( 'endAtomic' );

		$writeConnectionProvider = $this->getMockBuilder( ConnectionProvider::class )
			->disableOriginalConstructor()
			->getMock();

		$writeConnectionProvider->expects( $this->atLeastOnce() )
			->method( 'getConnection' )
			->willReturn( $write );

		$this->transactionHandler->expects( $this->atLeastOnce() )
			->method( 'markSectionTransaction' );

		$this->transactionHandler->expects( $this->atLeastOnce() )
			->method( 'inSectionTransaction' )
			->willReturn( true );

		$this->transactionHandler->expects( $this->atLeastOnce() )
			->method( 'detachSectionTransaction' );

		$instance = new Database(
			new ConnRef(
				[
					'read'  => $readConnectionProvider,
					'write' => $writeConnectionProvider
				]
			),
			$this->transactionHandler
		);

		$instance->beginSectionTransaction( __METHOD__ );
		$instance->cancelSectionTransaction( __METHOD__ );
	}

	public function testCancelSectionTransactionToleratesAlreadyClearedSection() {
		// Mirrors endSectionTransaction() having cleared the section flag just
		// before its endAtomic() threw: the rollback path must still cancel the
		// atomic without raising a secondary "invalid section" error that would
		// mask the original failure and leave the atomic dangling.
		$readConnectionProvider = $this->getMockBuilder( ConnectionProvider::class )
			->disableOriginalConstructor()
			->getMock();

		$write = $this->getMockBuilder( IDatabase::class )
			->disableOriginalConstructor()
			->getMock();

		$write->expects( $this->once() )
			->method( 'cancelAtomic' );

		$writeConnectionProvider = $this->getMockBuilder( ConnectionProvider::class )
			->disableOriginalConstructor()
			->getMock();

		$writeConnectionProvider->expects( $this->atLeastOnce() )
			->method( 'getConnection' )
			->willReturn( $write );

		// A real handler whose section flag was never set for this name.
		$transactionHandler = new TransactionHandler(
			$this->createMock( ILBFactory::class )
		);

		$instance = new Database(
			new ConnRef(
				[
					'read'  => $readConnectionProvider,
					'write' => $writeConnectionProvider
				]
			),
			$transactionHandler
		);

		$instance->cancelSectionTransaction( __METHOD__ );
	}

	public function testCancelSectionTransactionAllowsANewSectionToStart() {
		// After a failed entity rolls its section back, the next entity must be
		// able to start a fresh section instead of hitting "section transaction
		// still active" (the #6975 cascade).
		$readConnectionProvider = $this->getMockBuilder( ConnectionProvider::class )
			->disableOriginalConstructor()
			->getMock();

		$write = $this->getMockBuilder( IDatabase::class )
			->disableOriginalConstructor()
			->getMock();

		$writeConnectionProvider = $this->getMockBuilder( ConnectionProvider::class )
			->disableOriginalConstructor()
			->getMock();

		$writeConnectionProvider->expects( $this->atLeastOnce() )
			->method( 'getConnection' )
			->willReturn( $write );

		// A real handler so the "section still active" guard is genuinely run.
		$transactionHandler = new TransactionHandler(
			$this->createMock( ILBFactory::class )
		);

		$instance = new Database(
			new ConnRef(
				[
					'read'  => $readConnectionProvider,
					'write' => $writeConnectionProvider
				]
			),
			$transactionHandler
		);

		$instance->beginSectionTransaction( 'first-entity' );
		$instance->cancelSectionTransaction( 'first-entity' );

		// Would throw "section transaction still active" if cancel had not
		// cleared the section flag.
		$instance->beginSectionTransaction( 'second-entity' );

		$this->assertTrue(
			$instance->inSectionTransaction( 'second-entity' )
		);

		$instance->endSectionTransaction( 'second-entity' );
	}

	public function testListTables() {
		$readConnectionProvider = $this->getMockBuilder( ConnectionProvider::class )
			->disableOriginalConstructor()
			->getMock();

		$read = $this->getMockBuilder( '\Wikimedia\Rdbms\Database' )
			->disableOriginalConstructor()
			->getMock();

		$read->expects( $this->once() )
			->method( 'listTables' )
			->willReturn( [] );

		$readConnectionProvider->expects( $this->atLeastOnce() )
			->method( 'getConnection' )
			->willReturn( $read );

		$instance = new Database(
			new ConnRef(
				[
					'read'  => $readConnectionProvider
				]
			),
			$this->transactionHandler
		);

		$instance->listTables( __METHOD__ );
	}

	public function testDoQueryWithAutoCommit() {
		$database = $this->getMockBuilder( '\Wikimedia\Rdbms\Database' )
			->disableOriginalConstructor()
			->setMethods( [ 'getFlag', 'clearFlag', 'setFlag', 'query' ] )
			->getMockForAbstractClass();

		$database->expects( $this->any() )
			->method( 'getFlag' )
			->willReturn( true );

		$database->expects( $this->once() )
			->method( 'clearFlag' );

		$database->expects( $this->once() )
			->method( 'setFlag' );

		$database->expects( $this->once() )
			->method( 'query' )
			->willReturn( new FakeResultWrapper( [] ) );

		$readConnectionProvider = $this->getMockBuilder( ConnectionProvider::class )
			->disableOriginalConstructor()
			->getMock();

		$writeConnectionProvider = $this->getMockBuilder( ConnectionProvider::class )
			->disableOriginalConstructor()
			->getMock();

		$writeConnectionProvider->expects( $this->atLeastOnce() )
			->method( 'getConnection' )
			->willReturn( $database );

		$instance = new Database(
			new ConnRef(
				[
					'read'  => $readConnectionProvider,
					'write' => $writeConnectionProvider
				]
			),
			$this->transactionHandler
		);

		$instance->setFlag( Database::AUTO_COMMIT );
		$instance->query( 'foo', __METHOD__, false );
	}

	public function testReadQueryUsesReadConnection() {
		$database = $this->createMock( IDatabase::class );
		$database->expects( $this->any() )
			->method( 'query' )
			->willReturn( new FakeResultWrapper( [] ) );

		$readConnectionProvider = $this->createMock( ConnectionProvider::class );

		$readConnectionProvider->expects( $this->atLeastOnce() )
			->method( 'getConnection' )
			->willReturn( $database );

		$writeConnectionProvider = $this->createMock( ConnectionProvider::class );

		$writeConnectionProvider->expects( $this->never() )
			->method( 'getConnection' );

		$instance = new Database(
			new ConnRef(
				[
					'read'  => $readConnectionProvider,
					'write' => $writeConnectionProvider
				]
			),
			$this->transactionHandler
		);

		$res = $instance->readQuery( 'foo', __METHOD__ );

		$this->assertInstanceOf( IResultWrapper::class, $res );
	}

	public function dbTypeProvider() {
		return [
			[ 'mysql' ],
			[ 'sqlite' ],
			[ 'postgres' ]
		];
	}

	public function testNewInsertQueryBuilderDelegatesToWriteConnection(): void {
		$writeDb = $this->createMock( IDatabase::class );
		$builder = $this->createMock( InsertQueryBuilder::class );
		$writeDb->expects( $this->once() )
			->method( 'newInsertQueryBuilder' )
			->willReturn( $builder );

		$this->connRef->expects( $this->once() )
			->method( 'getConnection' )
			->with( 'write' )
			->willReturn( $writeDb );

		$instance = new Database( $this->connRef, $this->transactionHandler );

		$this->assertSame( $builder, $instance->newInsertQueryBuilder() );
	}

	public function testNewUpdateQueryBuilderDelegatesToWriteConnection(): void {
		$writeDb = $this->createMock( IDatabase::class );
		$builder = $this->createMock( UpdateQueryBuilder::class );
		$writeDb->expects( $this->once() )
			->method( 'newUpdateQueryBuilder' )
			->willReturn( $builder );

		$this->connRef->expects( $this->once() )
			->method( 'getConnection' )
			->with( 'write' )
			->willReturn( $writeDb );

		$instance = new Database( $this->connRef, $this->transactionHandler );

		$this->assertSame( $builder, $instance->newUpdateQueryBuilder() );
	}

	public function testNewDeleteQueryBuilderDelegatesToWriteConnection(): void {
		$writeDb = $this->createMock( IDatabase::class );
		$builder = $this->createMock( DeleteQueryBuilder::class );
		$writeDb->expects( $this->once() )
			->method( 'newDeleteQueryBuilder' )
			->willReturn( $builder );

		$this->connRef->expects( $this->once() )
			->method( 'getConnection' )
			->with( 'write' )
			->willReturn( $writeDb );

		$instance = new Database( $this->connRef, $this->transactionHandler );

		$this->assertSame( $builder, $instance->newDeleteQueryBuilder() );
	}

	public function testNewReplaceQueryBuilderDelegatesToWriteConnection(): void {
		$writeDb = $this->createMock( IDatabase::class );
		$builder = $this->createMock( ReplaceQueryBuilder::class );
		$writeDb->expects( $this->once() )
			->method( 'newReplaceQueryBuilder' )
			->willReturn( $builder );

		$this->connRef->expects( $this->once() )
			->method( 'getConnection' )
			->with( 'write' )
			->willReturn( $writeDb );

		$instance = new Database( $this->connRef, $this->transactionHandler );

		$this->assertSame( $builder, $instance->newReplaceQueryBuilder() );
	}
}

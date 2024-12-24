<?php

namespace SMW\Tests\MediaWiki\Connection;

use RuntimeException;
use SMW\Connection\ConnectionProvider;
use SMW\Connection\ConnRef;
use SMW\Tests\PHPUnitCompat;
use SMW\MediaWiki\Connection\Database;
use Wikimedia\Rdbms\DBError;
use Wikimedia\Rdbms\FakeResultWrapper;
use Wikimedia\Rdbms\IDatabase;
use Wikimedia\Rdbms\IResultWrapper;

/**
 * @covers \SMW\MediaWiki\Connection\Database
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class DatabaseTest extends \PHPUnit\Framework\TestCase {

	use PHPUnitCompat;

	private $connRef;
	private $transactionHandler;

	protected function setUp(): void {
		parent::setUp();

		$this->connRef = $this->getMockBuilder( '\SMW\Connection\ConnRef' )
			->disableOriginalConstructor()
			->getMock();

		$this->transactionHandler = $this->getMockBuilder( '\SMW\MediaWiki\Connection\TransactionHandler' )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			Database::class,
			new Database( $this->connRef, $this->transactionHandler )
		);
	}

	public function testNewQuery() {
		$instance = new Database(
			$this->connRef,
			$this->transactionHandler
		);

		$this->assertInstanceOf(
			'\SMW\MediaWiki\Connection\Query',
			$instance->newQuery()
		);
	}

	public function testAddQuotesMethod() {
		$database = $this->getMockBuilder( '\Wikimedia\Rdbms\Database' )
			->disableOriginalConstructor()
			->onlyMethods( [ 'addQuotes' ] )
			->getMockForAbstractClass();

		$database->expects( $this->once() )
			->method( 'addQuotes' )
			->with( 'Fan' )
			->willReturn( 'Fan' );

		$connectionProvider = $this->getMockBuilder( '\SMW\Connection\ConnectionProvider' )
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
			->onlyMethods( [ 'tableName' ] )
			->getMockForAbstractClass();

		$database->expects( $this->any() )
			->method( 'tableName' )
			->willReturnArgument( 0 );

		$connectionProvider = $this->getMockBuilder( '\SMW\Connection\ConnectionProvider' )
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

	public function testSelectMethod() {
		$resultWrapper = $this->getMockBuilder( '\Wikimedia\Rdbms\ResultWrapper' )
			->disableOriginalConstructor()
			->getMock();

		$database = $this->getMockBuilder( '\Wikimedia\Rdbms\Database' )
			->disableOriginalConstructor()
			->onlyMethods( [ 'select' ] )
			->getMockForAbstractClass();

		$database->expects( $this->once() )
			->method( 'select' )
			->willReturn( $resultWrapper );

		$connectionProvider = $this->getMockBuilder( '\SMW\Connection\ConnectionProvider' )
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

		$this->assertInstanceOf(
			'\Wikimedia\Rdbms\ResultWrapper',
			$instance->select( 'Foo', 'Bar', '', __METHOD__ )
		);
	}

	public function testSelectFieldMethod() {
		$database = $this->getMockBuilder( '\Wikimedia\Rdbms\Database' )
			->disableOriginalConstructor()
			->onlyMethods( [ 'selectField' ] )
			->getMockForAbstractClass();

		$database->expects( $this->once() )
			->method( 'selectField' )
			->with( 'Foo' )
			->willReturn( 'Bar' );

		$connectionProvider = $this->getMockBuilder( '\SMW\Connection\ConnectionProvider' )
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
			'Bar',
			$instance->selectField( 'Foo', 'Bar', '', __METHOD__, [] )
		);
	}

	/**
	 * @dataProvider querySqliteProvider
	 */
	public function testQueryOnSQLite( $query, $expected ) {
		$resultWrapper = $this->getMockBuilder( '\Wikimedia\Rdbms\ResultWrapper' )
			->disableOriginalConstructor()
			->getMock();

		$read = $this->getMockBuilder( '\Wikimedia\Rdbms\Database' )
			->disableOriginalConstructor()
			->onlyMethods( [ 'getType' ] )
			->getMockForAbstractClass();

		$read->expects( $this->any() )
			->method( 'getType' )
			->willReturn( 'sqlite' );

		$readConnectionProvider = $this->getMockBuilder( '\SMW\Connection\ConnectionProvider' )
			->disableOriginalConstructor()
			->getMock();

		$readConnectionProvider->expects( $this->atLeastOnce() )
			->method( 'getConnection' )
			->willReturn( $read );

		$write = $this->getMockBuilder( '\Wikimedia\Rdbms\Database' )
			->disableOriginalConstructor()
			->onlyMethods( [ 'query' ] )
			->getMockForAbstractClass();

		$write->expects( $this->once() )
			->method( 'query' )
			->with( $expected )
			->willReturn( $resultWrapper );

		$writeConnectionProvider = $this->getMockBuilder( '\SMW\Connection\ConnectionProvider' )
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
			'\Wikimedia\Rdbms\ResultWrapper',
			$instance->query( $query )
		);
	}

	public function querySqliteProvider() {
		$provider = [
			[ 'TEMPORARY', 'TEMP' ],
			[ 'RAND', 'RANDOM' ],
			[ 'ENGINE=MEMORY', '' ],
			[ 'DROP TEMP', 'DROP' ]
		];

		return $provider;
	}

	public function testSelectThrowsException() {
		$database = $this->getMockBuilder( '\Wikimedia\Rdbms\Database' )
			->disableOriginalConstructor()
			->onlyMethods( [ 'select' ] )
			->getMockForAbstractClass();

		if ( version_compare( MW_VERSION, '1.41', '>=' ) ) {
			$database->expects( $this->once() )
				->method( 'select' )
				->willThrowException( new RuntimeException( 'Database error' ) );
		} else {
			$database->expects( $this->once() )
				->method( 'select' );
		}

		$connectionProvider = $this->getMockBuilder( '\SMW\Connection\ConnectionProvider' )
			->disableOriginalConstructor()
			->getMock();

		$connectionProvider->expects( $this->atLeastOnce() )
			->method( 'getConnection' )
			->willReturn( $database );

		$instance = new Database(
			new ConnRef(
				[
					'read'  => $connectionProvider
				]
			),
			$this->transactionHandler
		);

		$this->expectException( 'RuntimeException' );

		$this->assertInstanceOf(
			'\Wikimedia\Rdbms\ResultWrapper',
			$instance->select( 'Foo', 'Bar', '', __METHOD__ )
		);
	}

	public function testQueryThrowsException() {
		$database = $this->getMockBuilder( '\Wikimedia\Rdbms\Database' )
			->disableOriginalConstructor()
			->onlyMethods( [ 'query' ] )
			->getMockForAbstractClass();

		$databaseException = new DBError( $database, 'foo' );

		$database->expects( $this->once() )
			->method( 'query' )
			->willThrowException( $databaseException );

		$connectionProvider = $this->getMockBuilder( '\SMW\Connection\ConnectionProvider' )
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
		$readConnectionProvider = $this->getMockBuilder( '\SMW\Connection\ConnectionProvider' )
			->disableOriginalConstructor()
			->getMock();

		$writeConnectionProvider = $this->getMockBuilder( '\SMW\Connection\ConnectionProvider' )
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
		$readConnectionProvider = $this->getMockBuilder( '\SMW\Connection\ConnectionProvider' )
			->disableOriginalConstructor()
			->getMock();

		$writeConnectionProvider = $this->getMockBuilder( '\SMW\Connection\ConnectionProvider' )
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
		$readConnectionProvider = $this->getMockBuilder( '\SMW\Connection\ConnectionProvider' )
			->disableOriginalConstructor()
			->getMock();

		$read = $this->getMockBuilder( '\Wikimedia\Rdbms\IDatabase' )
			->disableOriginalConstructor()
			->getMock();

		$readConnectionProvider->expects( $this->any() )
			->method( 'getConnection' )
			->willReturn( $read );

		$write = $this->getMockBuilder( '\Wikimedia\Rdbms\IDatabase' )
			->disableOriginalConstructor()
			->getMock();

		$write->expects( $this->once() )
			->method( 'startAtomic' );

		$write->expects( $this->never() )
			->method( 'endAtomic' );

		$writeConnectionProvider = $this->getMockBuilder( '\SMW\Connection\ConnectionProvider' )
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
		$readConnectionProvider = $this->getMockBuilder( '\SMW\Connection\ConnectionProvider' )
			->disableOriginalConstructor()
			->getMock();

		$read = $this->getMockBuilder( '\Wikimedia\Rdbms\IDatabase' )
			->disableOriginalConstructor()
			->getMock();

		$readConnectionProvider->expects( $this->any() )
			->method( 'getConnection' )
			->willReturn( $read );

		$write = $this->getMockBuilder( '\Wikimedia\Rdbms\IDatabase' )
			->disableOriginalConstructor()
			->getMock();

		$write->expects( $this->once() )
			->method( 'startAtomic' );

		$write->expects( $this->once() )
			->method( 'endAtomic' );

		$writeConnectionProvider = $this->getMockBuilder( '\SMW\Connection\ConnectionProvider' )
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

	public function testListTables() {
		$readConnectionProvider = $this->getMockBuilder( '\SMW\Connection\ConnectionProvider' )
			->disableOriginalConstructor()
			->getMock();

		$read = $this->getMockBuilder( '\Wikimedia\Rdbms\Database' )
			->disableOriginalConstructor()
			->getMock();

		$read->expects( $this->once() )
			->method( 'listTables' );

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
			->onlyMethods( [ 'getFlag', 'clearFlag', 'setFlag', 'getType', 'query' ] )
			->getMockForAbstractClass();

		$database->expects( $this->any() )
			->method( 'getType' )
			->willReturn( 'mysql' );

		$database->expects( $this->any() )
			->method( 'getFlag' )
			->willReturn( true );

		$database->expects( $this->once() )
			->method( 'clearFlag' );

		$database->expects( $this->once() )
			->method( 'setFlag' );

		$readConnectionProvider = $this->getMockBuilder( '\SMW\Connection\ConnectionProvider' )
			->disableOriginalConstructor()
			->getMock();

		$readConnectionProvider->expects( $this->atLeastOnce() )
			->method( 'getConnection' )
			->willReturn( $database );

		$writeConnectionProvider = $this->getMockBuilder( '\SMW\Connection\ConnectionProvider' )
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
}

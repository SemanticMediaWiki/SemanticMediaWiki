<?php

namespace SMW\Tests\MediaWiki\Connection;

use SMW\Connection\ConnectionProviderRef;
use SMW\Tests\PHPUnitCompat;
use SMW\MediaWiki\Connection\Database;

/**
 * @covers \SMW\MediaWiki\Connection\Database
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class DatabaseTest extends \PHPUnit_Framework_TestCase {

	use PHPUnitCompat;

	private $connectionProviderRef;

	protected function setUp() {
		parent::setUp();

		$this->connectionProviderRef = $this->getMockBuilder( '\SMW\Connection\ConnectionProviderRef' )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			Database::class,
			new Database( $this->connectionProviderRef )
		);
	}

	public function testNewQuery() {

		$instance = new Database(
			$this->connectionProviderRef
		);

		$this->assertInstanceOf(
			'\SMW\MediaWiki\Connection\Query',
			$instance->newQuery()
		);
	}

	public function testNumRowsMethod() {

		$database = $this->getMockBuilder( '\DatabaseBase' )
			->disableOriginalConstructor()
			->setMethods( [ 'numRows' ] )
			->getMockForAbstractClass();

		$database->expects( $this->once() )
			->method( 'numRows' )
			->with( $this->equalTo( 'Fuyu' ) )
			->will( $this->returnValue( 1 ) );

		$connectionProvider = $this->getMockBuilder( '\SMW\Connection\ConnectionProvider' )
			->disableOriginalConstructor()
			->getMock();

		$connectionProvider->expects( $this->atLeastOnce() )
			->method( 'getConnection' )
			->will( $this->returnValue( $database ) );

		$instance = new Database(
			new ConnectionProviderRef(
				[
					'read' => $connectionProvider
				]
			)
		);

		$this->assertEquals(
			1,
			$instance->numRows( 'Fuyu' )
		);
	}

	public function testAddQuotesMethod() {

		$database = $this->getMockBuilder( '\DatabaseBase' )
			->disableOriginalConstructor()
			->setMethods( [ 'addQuotes' ] )
			->getMockForAbstractClass();

		$database->expects( $this->once() )
			->method( 'addQuotes' )
			->with( $this->equalTo( 'Fan' ) )
			->will( $this->returnValue( 'Fan' ) );

		$connectionProvider = $this->getMockBuilder( '\SMW\Connection\ConnectionProvider' )
			->disableOriginalConstructor()
			->getMock();

		$connectionProvider->expects( $this->atLeastOnce() )
			->method( 'getConnection' )
			->will( $this->returnValue( $database ) );

		$instance = new Database(
			new ConnectionProviderRef(
				[
					'read' => $connectionProvider
				]
			)
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

		$database = $this->getMockBuilder( '\DatabaseBase' )
			->disableOriginalConstructor()
			->setMethods( [ 'tableName', 'getType' ] )
			->getMockForAbstractClass();

		$database->expects( $this->any() )
			->method( 'tableName' )
			->with( $this->equalTo( 'Foo' ) )
			->will( $this->returnValue( 'Foo' ) );

		$database->expects( $this->once() )
			->method( 'getType' )
			->will( $this->returnValue( $type ) );

		$connectionProvider = $this->getMockBuilder( '\SMW\Connection\ConnectionProvider' )
			->disableOriginalConstructor()
			->getMock();

		$connectionProvider->expects( $this->atLeastOnce() )
			->method( 'getConnection' )
			->will( $this->returnValue( $database ) );

		$instance = new Database(
			new ConnectionProviderRef(
				[
					'read' => $connectionProvider
				]
			)
		);

		$instance->setDBPrefix( 'bar_' );

		$expected = $type === 'sqlite' ? 'bar_Foo' : 'Foo';

		$this->assertEquals(
			$expected,
			$instance->tableName( 'Foo' )
		);
	}

	public function testSelectMethod() {

		$resultWrapper = $this->getMockBuilder( 'ResultWrapper' )
			->disableOriginalConstructor()
			->getMock();

		$database = $this->getMockBuilder( '\DatabaseBase' )
			->disableOriginalConstructor()
			->setMethods( [ 'select' ] )
			->getMockForAbstractClass();

		$database->expects( $this->once() )
			->method( 'select' )
			->will( $this->returnValue( $resultWrapper ) );

		$connectionProvider = $this->getMockBuilder( '\SMW\Connection\ConnectionProvider' )
			->disableOriginalConstructor()
			->getMock();

		$connectionProvider->expects( $this->atLeastOnce() )
			->method( 'getConnection' )
			->will( $this->returnValue( $database ) );

		$instance = new Database(
			new ConnectionProviderRef(
				[
					'read' => $connectionProvider
				]
			)
		);

		$this->assertInstanceOf(
			'ResultWrapper',
			$instance->select( 'Foo', 'Bar', '', __METHOD__ )
		);
	}

	public function testSelectFieldMethod() {

		$database = $this->getMockBuilder( '\DatabaseBase' )
			->disableOriginalConstructor()
			->setMethods( [ 'selectField' ] )
			->getMockForAbstractClass();

		$database->expects( $this->once() )
			->method( 'selectField' )
			->with( $this->equalTo( 'Foo' ) )
			->will( $this->returnValue( 'Bar' ) );

		$connectionProvider = $this->getMockBuilder( '\SMW\Connection\ConnectionProvider' )
			->disableOriginalConstructor()
			->getMock();

		$connectionProvider->expects( $this->atLeastOnce() )
			->method( 'getConnection' )
			->will( $this->returnValue( $database ) );

		$instance = new Database(
			new ConnectionProviderRef(
				[
					'read' => $connectionProvider
				]
			)
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

		$resultWrapper = $this->getMockBuilder( 'ResultWrapper' )
			->disableOriginalConstructor()
			->getMock();

		$read = $this->getMockBuilder( '\DatabaseBase' )
			->disableOriginalConstructor()
			->setMethods( [ 'getType' ] )
			->getMockForAbstractClass();

		$read->expects( $this->any() )
			->method( 'getType' )
			->will( $this->returnValue( 'sqlite' ) );

		$readConnectionProvider = $this->getMockBuilder( '\SMW\Connection\ConnectionProvider' )
			->disableOriginalConstructor()
			->getMock();

		$readConnectionProvider->expects( $this->atLeastOnce() )
			->method( 'getConnection' )
			->will( $this->returnValue( $read ) );

		$write = $this->getMockBuilder( '\DatabaseBase' )
			->disableOriginalConstructor()
			->setMethods( [ 'query' ] )
			->getMockForAbstractClass();

		$write->expects( $this->once() )
			->method( 'query' )
			->with( $this->equalTo( $expected ) )
			->will( $this->returnValue( $resultWrapper ) );

		$writeConnectionProvider = $this->getMockBuilder( '\SMW\Connection\ConnectionProvider' )
			->disableOriginalConstructor()
			->getMock();

		$writeConnectionProvider->expects( $this->atLeastOnce() )
			->method( 'getConnection' )
			->will( $this->returnValue( $write ) );

		$instance = new Database(
			new ConnectionProviderRef(
				[
					'read'  => $readConnectionProvider,
					'write' => $writeConnectionProvider
				]
			)
		);

		$this->assertInstanceOf(
			'ResultWrapper',
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

		$database = $this->getMockBuilder( '\DatabaseBase' )
			->disableOriginalConstructor()
			->setMethods( [ 'select' ] )
			->getMockForAbstractClass();

		$database->expects( $this->once() )
			->method( 'select' );

		$connectionProvider = $this->getMockBuilder( '\SMW\Connection\ConnectionProvider' )
			->disableOriginalConstructor()
			->getMock();

		$connectionProvider->expects( $this->atLeastOnce() )
			->method( 'getConnection' )
			->will( $this->returnValue( $database ) );

		$instance = new Database(
			new ConnectionProviderRef(
				[
					'read'  => $connectionProvider
				]
			)
		);

		$this->setExpectedException( 'RuntimeException' );

		$this->assertInstanceOf(
			'ResultWrapper',
			$instance->select( 'Foo', 'Bar', '', __METHOD__ )
		);
	}

	public function testQueryThrowsException() {

		$database = $this->getMockBuilder( '\DatabaseBase' )
			->disableOriginalConstructor()
			->setMethods( [ 'query' ] )
			->getMockForAbstractClass();

		$databaseException = new \DBError( $database, 'foo' );

		$database->expects( $this->once() )
			->method( 'query' )
			->will( $this->throwException( $databaseException ) );

		$connectionProvider = $this->getMockBuilder( '\SMW\Connection\ConnectionProvider' )
			->disableOriginalConstructor()
			->getMock();

		$connectionProvider->expects( $this->any() )
			->method( 'getConnection' )
			->will( $this->returnValue( $database ) );

		$instance = new Database(
			new ConnectionProviderRef(
				[
					'read'  => $connectionProvider,
					'write' => $connectionProvider
				]
			)
		);

		$this->setExpectedException( 'Exception' );
		$instance->query( 'Foo', __METHOD__ );
	}

	public function testGetEmptyTransactionTicket() {

		$readConnectionProvider = $this->getMockBuilder( '\SMW\Connection\ConnectionProvider' )
			->disableOriginalConstructor()
			->getMock();

		$writeConnectionProvider = $this->getMockBuilder( '\SMW\Connection\ConnectionProvider' )
			->disableOriginalConstructor()
			->getMock();

		$loadBalancerFactory = $this->getMockBuilder( '\stdClass' )
			->setMethods( [ 'getEmptyTransactionTicket', 'hasMasterChanges' ] )
			->getMock();

		$loadBalancerFactory->expects( $this->once() )
			->method( 'hasMasterChanges' )
			->will( $this->returnValue( false ) );

		$loadBalancerFactory->expects( $this->once() )
			->method( 'getEmptyTransactionTicket' );

		$instance = new Database(
			new ConnectionProviderRef(
				[
					'read'  => $readConnectionProvider,
					'write' => $writeConnectionProvider
				]
			),
			$loadBalancerFactory
		);

		$instance->getEmptyTransactionTicket( __METHOD__ );
	}

	public function testGetEmptyTransactionTicketOnMasterChanges() {

		$readConnectionProvider = $this->getMockBuilder( '\SMW\Connection\ConnectionProvider' )
			->disableOriginalConstructor()
			->getMock();

		$writeConnectionProvider = $this->getMockBuilder( '\SMW\Connection\ConnectionProvider' )
			->disableOriginalConstructor()
			->getMock();

		$loadBalancerFactory = $this->getMockBuilder( '\stdClass' )
			->setMethods( [ 'getEmptyTransactionTicket', 'hasMasterChanges' ] )
			->getMock();

		$loadBalancerFactory->expects( $this->once() )
			->method( 'hasMasterChanges' )
			->will( $this->returnValue( true ) );

		$loadBalancerFactory->expects( $this->never() )
			->method( 'getEmptyTransactionTicket' );

		$instance = new Database(
			new ConnectionProviderRef(
				[
					'read'  => $readConnectionProvider,
					'write' => $writeConnectionProvider
				]
			),
			$loadBalancerFactory
		);

		$this->assertNull(
			$instance->getEmptyTransactionTicket( __METHOD__ )
		);
	}

	public function testCommitAndWaitForReplication() {

		$readConnectionProvider = $this->getMockBuilder( '\SMW\Connection\ConnectionProvider' )
			->disableOriginalConstructor()
			->getMock();

		$writeConnectionProvider = $this->getMockBuilder( '\SMW\Connection\ConnectionProvider' )
			->disableOriginalConstructor()
			->getMock();

		$loadBalancerFactory = $this->getMockBuilder( '\stdClass' )
			->setMethods( [ 'commitAndWaitForReplication' ] )
			->getMock();

		$loadBalancerFactory->expects( $this->once() )
			->method( 'commitAndWaitForReplication' );

		$instance = new Database(
			new ConnectionProviderRef(
				[
					'read'  => $readConnectionProvider,
					'write' => $writeConnectionProvider
				]
			),
			$loadBalancerFactory
		);

		$instance->commitAndWaitForReplication( __METHOD__, 123 );
	}

	public function testDoQueryWithAutoCommit() {

		$database = $this->getMockBuilder( '\DatabaseBase' )
			->disableOriginalConstructor()
			->setMethods( [ 'getFlag', 'clearFlag', 'setFlag', 'getType', 'query' ] )
			->getMockForAbstractClass();

		$database->expects( $this->any() )
			->method( 'getType' )
			->will( $this->returnValue( 'mysql' ) );

		$database->expects( $this->any() )
			->method( 'getFlag' )
			->will( $this->returnValue( true ) );

		$database->expects( $this->once() )
			->method( 'clearFlag' );

		$database->expects( $this->once() )
			->method( 'setFlag' );

		$readConnectionProvider = $this->getMockBuilder( '\SMW\Connection\ConnectionProvider' )
			->disableOriginalConstructor()
			->getMock();

		$readConnectionProvider->expects( $this->atLeastOnce() )
			->method( 'getConnection' )
			->will( $this->returnValue( $database ) );

		$writeConnectionProvider = $this->getMockBuilder( '\SMW\Connection\ConnectionProvider' )
			->disableOriginalConstructor()
			->getMock();

		$writeConnectionProvider->expects( $this->atLeastOnce() )
			->method( 'getConnection' )
			->will( $this->returnValue( $database ) );

		$instance = new Database(
			new ConnectionProviderRef(
				[
					'read'  => $readConnectionProvider,
					'write' => $writeConnectionProvider
				]
			)
		);

		$instance->setFlag( Database::AUTO_COMMIT );
		$instance->query( 'foo', __METHOD__, false );
	}

	/**
	 * @dataProvider missingWriteConnectionProvider
	 */
	public function testMissingWriteConnectionThrowsException( $func, $args ) {

		$connectionProvider = $this->getMockBuilder( '\SMW\Connection\ConnectionProvider' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new Database(
			new ConnectionProviderRef( [] )
		);

		$this->setExpectedException( 'RuntimeException' );
		call_user_func_array( [ $instance, $func ], $args );
	}

	public function dbTypeProvider() {
		return [
			[ 'mysql' ],
			[ 'sqlite' ],
			[ 'postgres' ]
		];
	}

	public function missingWriteConnectionProvider() {

		yield [
			'query', [ 'foo' ]
		];

		yield [
			'nextSequenceValue', [ 'foo' ]
		];

		yield [
			'insertId', []
		];

		yield [
			'clearFlag', [ 'Foo' ]
		];

		yield [
			'getFlag', [ 'Foo' ]
		];

		yield [
			'setFlag', [ 'Foo' ]
		];

		yield [
			'insert', [ 'Foo', 'Bar' ]
		];

		yield [
			'update', [ 'Foo', 'Bar', 'Foobar' ]
		];

		yield [
			'delete', [ 'Foo', 'Bar' ]
		];

		yield [
			'replace', [ 'Foo', 'Bar', 'Foobar' ]
		];

		yield [
			'makeList', [ 'Foo', 'Bar' ]
		];

		yield [
			'beginAtomicTransaction', [ 'Foo' ]
		];

		yield [
			'endAtomicTransaction', [ 'Foo' ]
		];

		yield [
			'onTransactionIdle', [ 'Foo' ]
		];
	}

}

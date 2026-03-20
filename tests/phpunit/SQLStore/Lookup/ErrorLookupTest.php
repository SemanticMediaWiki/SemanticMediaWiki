<?php

namespace SMW\Tests\SQLStore\Lookup;

use PHPUnit\Framework\TestCase;
use SMW\DataItems\Property;
use SMW\DataItems\WikiPage;
use SMW\IteratorFactory;
use SMW\MediaWiki\Connection\Database;
use SMW\MediaWiki\Connection\Query;
use SMW\RequestOptions;
use SMW\SQLStore\EntityStore\EntityIdManager;
use SMW\SQLStore\Lookup\ErrorLookup;
use SMW\SQLStore\SQLStore;
use Wikimedia\Rdbms\ResultWrapper;

/**
 * @covers \SMW\SQLStore\Lookup\ErrorLookup
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since   3.1
 *
 * @author mwjames
 */
class ErrorLookupTest extends TestCase {

	private $store;
	private $connection;
	private $iteratorFactory;

	protected function setUp(): void {
		$this->connection = $this->getMockBuilder( Database::class )
			->disableOriginalConstructor()
			->getMock();

		$this->store = $this->getMockBuilder( SQLStore::class )
			->disableOriginalConstructor()
			->setMethods( [ 'getConnection' ] )
			->getMock();

		$this->store->expects( $this->any() )
			->method( 'getConnection' )
			->willReturn( $this->connection );

		$this->iteratorFactory = $this->getMockBuilder( IteratorFactory::class )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			ErrorLookup::class,
			new ErrorLookup( $this->store )
		);
	}

	public function testBuildArray() {
		$res = [
			(object)[ 'o_hash' => 'Foo', 'o_blob' => null ],
			(object)[ 'o_hash' => 'Foo', 'o_blob' => 'Bar' ]
		];

		$this->connection->expects( $this->any() )
			->method( 'unescape_bytea' )
			->willReturnArgument( 0 );

		$instance = new ErrorLookup(
			$this->store
		);

		$this->assertEquals(
			[
				'Foo',
				'Bar'
			],
			$instance->buildArray( $res )
		);
	}

	public function testFindErrorsByType() {
		$idTable = $this->getMockBuilder( EntityIdManager::class )
			->disableOriginalConstructor()
			->getMock();

		$store = $this->getMockBuilder( SQLStore::class )
			->disableOriginalConstructor()
			->setMethods( [ 'getConnection', 'getPropertyTables', 'findDiTypeTableId', 'getObjectIds', 'findPropertyTableID' ] )
			->getMock();

		$store->expects( $this->any() )
			->method( 'getObjectIds' )
			->willReturn( $idTable );

		$store->expects( $this->any() )
			->method( 'getConnection' )
			->willReturn( $this->connection );

		$store->expects( $this->any() )
			->method( 'findDiTypeTableId' )
			->willReturnOnConsecutiveCalls( '_foo', '_bar' );

		$store->expects( $this->any() )
			->method( 'findPropertyTableID' )
			->willReturnOnConsecutiveCalls( 'smw_di_blob', 'smw_di_blob', 'smw_di_blob' );

		$this->connection->expects( $this->any() )
			->method( 'addQuotes' )
			->willReturnArgument( 0 );

		$this->connection->expects( $this->any() )
			->method( 'tableName' )
			->willReturnArgument( 0 );

		$query = new Query( $this->connection );

		$resultWrapper = $this->getMockBuilder( ResultWrapper::class )
			->disableOriginalConstructor()
			->getMock();

		$this->connection->expects( $this->atLeastOnce() )
			->method( 'newQuery' )
			->willReturn( $query );

		$instance = new ErrorLookup(
			$store
		);

		$property = $this->getMockBuilder( Property::class )
			->disableOriginalConstructor()
			->getMock();

		$dataItem = $this->getMockBuilder( '\SMWDIBlob' )
			->disableOriginalConstructor()
			->getMock();

		$instance->findErrorsByType( 'foo' );

		$this->assertEquals(
			'SELECT t2.s_id AS s_id, t3.o_hash AS o_hash, t3.o_blob AS o_blob ' .
			'FROM smw_object_ids AS t0 ' .
			'INNER JOIN _foo AS t1 ON t0.smw_id=t1.s_id ' .
			'INNER JOIN smw_di_blob AS t2 ON t1.o_id=t2.s_id ' .
			'INNER JOIN smw_di_blob AS t3 ON t3.s_id=t2.s_id ' .
			'WHERE (t0.smw_iw!=:smw) AND (t0.smw_iw!=:smw-delete) AND ' .
			'(t1.p_id=) AND (t2.p_id=) AND (t2.o_hash=foo) AND (t3.p_id=)',
			$query->build()
		);
	}

	public function testFindErrorsByType_WithSubobjects() {
		$requestOptions = new RequestOptions();
		$requestOptions->setOption( 'checkConstraintErrors', SMW_CONSTRAINT_ERR_CHECK_ALL );

		$idTable = $this->getMockBuilder( EntityIdManager::class )
			->disableOriginalConstructor()
			->getMock();

		$store = $this->getMockBuilder( SQLStore::class )
			->disableOriginalConstructor()
			->setMethods( [ 'getConnection', 'getPropertyTables', 'findDiTypeTableId', 'getObjectIds', 'findPropertyTableID' ] )
			->getMock();

		$store->expects( $this->any() )
			->method( 'getObjectIds' )
			->willReturn( $idTable );

		$store->expects( $this->any() )
			->method( 'getConnection' )
			->willReturn( $this->connection );

		$store->expects( $this->any() )
			->method( 'findDiTypeTableId' )
			->willReturnOnConsecutiveCalls( '_foo', '_bar' );

		$store->expects( $this->any() )
			->method( 'findPropertyTableID' )
			->willReturnOnConsecutiveCalls( 'smw_fpt_sobj', 'smw_di_blob', 'smw_di_blob' );

		$this->connection->expects( $this->any() )
			->method( 'addQuotes' )
			->willReturnArgument( 0 );

		$this->connection->expects( $this->any() )
			->method( 'tableName' )
			->willReturnArgument( 0 );

		$query = new Query( $this->connection );

		$resultWrapper = $this->getMockBuilder( ResultWrapper::class )
			->disableOriginalConstructor()
			->getMock();

		$this->connection->expects( $this->atLeastOnce() )
			->method( 'newQuery' )
			->willReturn( $query );

		$instance = new ErrorLookup(
			$store
		);

		$property = $this->getMockBuilder( Property::class )
			->disableOriginalConstructor()
			->getMock();

		$dataItem = $this->getMockBuilder( '\SMWDIBlob' )
			->disableOriginalConstructor()
			->getMock();

		$instance->findErrorsByType( 'foo', WikiPage::newFromText( 'Foo' ), $requestOptions );

		$this->assertEquals(
			'SELECT t2.s_id AS s_id, t3.o_hash AS o_hash, t3.o_blob AS o_blob ' .
			'FROM smw_object_ids AS t0 ' .
			'INNER JOIN _foo AS t1 ON t0.smw_id=t1.s_id ' .
			'LEFT JOIN smw_fpt_sobj AS s1 ON s1.o_id=t1.s_id ' .
			'INNER JOIN smw_di_blob AS t2 ON t1.o_id=t2.s_id ' .
			'INNER JOIN smw_di_blob AS t3 ON t3.s_id=t2.s_id ' .
			'WHERE (t0.smw_iw!=:smw) AND (t0.smw_iw!=:smw-delete) AND ' .
			'((s1.s_id= OR t1.s_id=)) AND (t1.p_id=) AND (t2.p_id=) AND (t2.o_hash=foo) AND (t3.p_id=)',
			$query->build()
		);
	}

}

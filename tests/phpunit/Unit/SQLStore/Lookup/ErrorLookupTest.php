<?php

namespace SMW\Tests\SQLStore\Lookup;

use SMW\SQLStore\Lookup\ErrorLookup;
use SMW\DIWikiPage;
use SMW\RequestOptions;

/**
 * @covers \SMW\SQLStore\Lookup\ErrorLookup
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since   3.1
 *
 * @author mwjames
 */
class ErrorLookupTest extends \PHPUnit_Framework_TestCase {

	private $store;
	private $connection;
	private $iteratorFactory;

	protected function setUp() {

		$this->connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$this->store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->setMethods( [ 'getConnection' ] )
			->getMock();

		$this->store->expects( $this->any() )
			->method( 'getConnection' )
			->will( $this->returnValue( $this->connection ) );

		$this->iteratorFactory = $this->getMockBuilder( '\SMW\IteratorFactory' )
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
			->will( $this->returnArgument( 0 ) );

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

		$idTable = $this->getMockBuilder( '\SMW\SQLStore\EntityStore\EntityIdManager' )
			->disableOriginalConstructor()
			->getMock();

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->setMethods( [ 'getConnection', 'getPropertyTables', 'findDiTypeTableId', 'getObjectIds', 'findPropertyTableID' ] )
			->getMock();

		$store->expects( $this->any() )
			->method( 'getObjectIds' )
			->will( $this->returnValue( $idTable ) );

		$store->expects( $this->any() )
			->method( 'getConnection' )
			->will( $this->returnValue( $this->connection ) );

		$store->expects( $this->any() )
			->method( 'findDiTypeTableId' )
			->will( $this->onConsecutiveCalls( '_foo', '_bar' ) );

		$store->expects( $this->any() )
			->method( 'findPropertyTableID' )
			->will( $this->onConsecutiveCalls( 'smw_di_blob', 'smw_di_blob', 'smw_di_blob' ) );

		$this->connection->expects( $this->any() )
			->method( 'addQuotes' )
			->will( $this->returnArgument( 0 ) );

		$this->connection->expects( $this->any() )
			->method( 'tableName' )
			->will( $this->returnArgument( 0 ) );

		$query = new \SMW\MediaWiki\Connection\Query( $this->connection );

		$resultWrapper = $this->getMockBuilder( '\ResultWrapper' )
			->disableOriginalConstructor()
			->getMock();

		$this->connection->expects( $this->atLeastOnce() )
			->method( 'newQuery' )
			->will( $this->returnValue( $query ) );

		$instance = new ErrorLookup(
			$store
		);

		$property = $this->getMockBuilder( '\SMW\DIProperty' )
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

		$idTable = $this->getMockBuilder( '\SMW\SQLStore\EntityStore\EntityIdManager' )
			->disableOriginalConstructor()
			->getMock();

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->setMethods( [ 'getConnection', 'getPropertyTables', 'findDiTypeTableId', 'getObjectIds', 'findPropertyTableID' ] )
			->getMock();

		$store->expects( $this->any() )
			->method( 'getObjectIds' )
			->will( $this->returnValue( $idTable ) );

		$store->expects( $this->any() )
			->method( 'getConnection' )
			->will( $this->returnValue( $this->connection ) );

		$store->expects( $this->any() )
			->method( 'findDiTypeTableId' )
			->will( $this->onConsecutiveCalls( '_foo', '_bar' ) );

		$store->expects( $this->any() )
			->method( 'findPropertyTableID' )
			->will( $this->onConsecutiveCalls( 'smw_fpt_sobj', 'smw_di_blob', 'smw_di_blob' ) );

		$this->connection->expects( $this->any() )
			->method( 'addQuotes' )
			->will( $this->returnArgument( 0 ) );

		$this->connection->expects( $this->any() )
			->method( 'tableName' )
			->will( $this->returnArgument( 0 ) );

		$query = new \SMW\MediaWiki\Connection\Query( $this->connection );

		$resultWrapper = $this->getMockBuilder( '\ResultWrapper' )
			->disableOriginalConstructor()
			->getMock();

		$this->connection->expects( $this->atLeastOnce() )
			->method( 'newQuery' )
			->will( $this->returnValue( $query ) );

		$instance = new ErrorLookup(
			$store
		);

		$property = $this->getMockBuilder( '\SMW\DIProperty' )
			->disableOriginalConstructor()
			->getMock();

		$dataItem = $this->getMockBuilder( '\SMWDIBlob' )
			->disableOriginalConstructor()
			->getMock();

		$instance->findErrorsByType( 'foo', DIWikiPage::newFromText( 'Foo' ), $requestOptions );

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

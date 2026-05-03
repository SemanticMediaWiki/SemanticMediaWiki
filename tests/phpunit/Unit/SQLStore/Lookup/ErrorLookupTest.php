<?php

namespace SMW\Tests\Unit\SQLStore\Lookup;

use PHPUnit\Framework\TestCase;
use SMW\DataItems\WikiPage;
use SMW\IteratorFactory;
use SMW\MediaWiki\Connection\Database;
use SMW\RequestOptions;
use SMW\SQLStore\EntityStore\EntityIdManager;
use SMW\SQLStore\Lookup\ErrorLookup;
use SMW\SQLStore\SQLStore;
use SMW\Tests\Unit\MediaWiki\Connection\MockSelectQueryBuilderTrait;

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

	use MockSelectQueryBuilderTrait;

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

		$this->connection->expects( $this->atLeastOnce() )
			->method( 'newSelectQueryBuilder' )
			->willReturn( $this->createMockSelectQueryBuilder( [] ) );

		$instance = new ErrorLookup(
			$store
		);

		$this->assertIsIterable( $instance->findErrorsByType( 'foo' ) );
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

		$this->connection->expects( $this->atLeastOnce() )
			->method( 'newSelectQueryBuilder' )
			->willReturn( $this->createMockSelectQueryBuilder( [] ) );

		$instance = new ErrorLookup(
			$store
		);

		$this->assertIsIterable(
			$instance->findErrorsByType( 'foo', WikiPage::newFromText( 'Foo' ), $requestOptions )
		);
	}

}

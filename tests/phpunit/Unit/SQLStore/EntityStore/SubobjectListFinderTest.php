<?php

namespace SMW\Tests\Unit\SQLStore\EntityStore;

use PHPUnit\Framework\TestCase;
use SMW\DataItems\WikiPage;
use SMW\IteratorFactory;
use SMW\Iterators\MappingIterator;
use SMW\MediaWiki\Connection\Database;
use SMW\Services\ServicesFactory as ApplicationFactory;
use SMW\SQLStore\EntityStore\SubobjectListFinder;
use SMW\SQLStore\SQLStore;
use SMW\Tests\Unit\MediaWiki\Connection\MockSelectQueryBuilderTrait;
use stdClass;

/**
 * @covers \SMW\SQLStore\EntityStore\SubobjectListFinder
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 2.5
 *
 * @author mwjames
 */
class SubobjectListFinderTest extends TestCase {

	use MockSelectQueryBuilderTrait;

	private $iteratorFactory;

	public function setUp(): void {
		parent::setUp();

		$this->iteratorFactory = ApplicationFactory::getInstance()->getIteratorFactory();
	}

	public function testCanConstruct() {
		$store = $this->getMockBuilder( SQLStore::class )
			->disableOriginalConstructor()
			->getMock();

		$iteratorFactory = $this->getMockBuilder( IteratorFactory::class )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			SubobjectListFinder::class,
			new SubobjectListFinder( $store, $iteratorFactory )
		);
	}

	/**
	 * @dataProvider subjectProvider
	 */
	public function testNewMappingIterator( $subject ) {
		$connection = $this->getMockBuilder( Database::class )
			->disableOriginalConstructor()
			->getMock();

		$qb = $this->createMockSelectQueryBuilder( [] );

		$connection->expects( $this->atLeastOnce() )
			->method( 'newSelectQueryBuilder' )
			->willReturn( $qb );

		$store = $this->getMockBuilder( SQLStore::class )
			->disableOriginalConstructor()
			->setMethods( [ 'getConnection' ] )
			->getMock();

		$store->expects( $this->atLeastOnce() )
			->method( 'getConnection' )
			->willReturn( $connection );

		$instance = new SubobjectListFinder(
			$store,
			$this->iteratorFactory
		);

		$this->assertInstanceOf(
			MappingIterator::class,
			$instance->find( $subject )
		);
	}

	/**
	 * @dataProvider subjectProvider
	 */
	public function testIterateOn( $subject ) {
		$row = new stdClass;
		$row->smw_id = 42;
		$row->smw_sortkey = 'sort';
		$row->smw_sort = 'SORT';
		$row->smw_subobject = '10000000001';

		$expected = [
			'Foo', 0, '', 'sort', '10000000001'
		];

		$connection = $this->getMockBuilder( Database::class )
			->disableOriginalConstructor()
			->getMock();

		$whereConditions = [];
		$qb = $this->createMockSelectQueryBuilder( [ $row ], $whereConditions );

		$connection->expects( $this->atLeastOnce() )
			->method( 'newSelectQueryBuilder' )
			->willReturn( $qb );

		$store = $this->getMockBuilder( SQLStore::class )
			->disableOriginalConstructor()
			->setMethods( [ 'getConnection', 'getDataItemHandlerForDIType' ] )
			->getMock();

		$store->expects( $this->atLeastOnce() )
			->method( 'getConnection' )
			->willReturn( $connection );

		$instance = new SubobjectListFinder(
			$store,
			$this->iteratorFactory
		);

		foreach ( $instance->find( $subject ) as $v ) {
			$this->assertEquals( 42, $v->getId() );
		}

		$this->assertNotEmpty( $whereConditions );
		$flat = $whereConditions[0];
		$this->assertArrayHasKey( 'smw_title', $flat );
		$this->assertArrayHasKey( 'smw_namespace', $flat );
		$this->assertArrayHasKey( 'smw_iw', $flat );
	}

	public function subjectProvider() {
		$provider[] = [
			WikiPage::newFromText( 'Foo' )
		];

		$provider[] = [
			WikiPage::newFromText( 'Bar', SMW_NS_PROPERTY )
		];

		$provider[] = [
			WikiPage::newFromText( 'Modification date', SMW_NS_PROPERTY )
		];

		return $provider;
	}

}

<?php

namespace SMW\Tests\Unit\SQLStore\EntityStore;

use PHPUnit\Framework\TestCase;
use SMW\DataItems\WikiPage;
use SMW\IteratorFactory;
use SMW\Iterators\MappingIterator;
use SMW\MediaWiki\Connection\Database;
use SMW\SQLStore\EntityStore\DuplicateFinder;
use SMW\SQLStore\RedirectStore;
use SMW\SQLStore\SQLStore;
use SMW\Tests\Unit\MediaWiki\Connection\MockSelectQueryBuilderTrait;
use stdClass;

/**
 * @covers \SMW\SQLStore\EntityStore\DuplicateFinder
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since   3.0
 *
 * @author mwjames
 */
class DuplicateFinderTest extends TestCase {

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
			DuplicateFinder::class,
			new DuplicateFinder( $this->store, $this->iteratorFactory )
		);
	}

	public function testHasDuplicate() {
		$whereConditions = [];
		$qb = $this->createMockSelectQueryBuilder(
			[ (object)[ 'smw_id' => 1 ], (object)[ 'smw_id' => 2 ] ],
			$whereConditions
		);

		$this->connection->expects( $this->atLeastOnce() )
			->method( 'newSelectQueryBuilder' )
			->willReturn( $qb );

		$instance = new DuplicateFinder(
			$this->store,
			$this->iteratorFactory
		);

		$this->assertTrue(
			$instance->hasDuplicate( WikiPage::newFromText( 'Foo' ) )
		);

		$this->assertContains(
			[
				'smw_title' => 'Foo',
				'smw_namespace' => 0,
				'smw_subobject' => '',
			],
			$whereConditions
		);
	}

	public function testFindDuplicates_ID_Table() {
		$row = new stdClass;
		$row->count = 42;
		$row->smw_title = 'Foo';
		$row->smw_namespace = 0;
		$row->smw_iw = '';
		$row->smw_subobject = '';

		$expected = [
			'count' => 42,
			'smw_title' => 'Foo',
			'smw_namespace' => 0,
			'smw_iw' => '',
			'smw_subobject' => ''
		];

		$qb = $this->createMockSelectQueryBuilder( [ $row ] );

		$this->connection->expects( $this->atLeastOnce() )
			->method( 'newSelectQueryBuilder' )
			->willReturn( $qb );

		$instance = new DuplicateFinder(
			$this->store,
			new IteratorFactory()
		);

		$res = $instance->findDuplicates();

		$this->assertInstanceOf(
			MappingIterator::class,
			$res
		);

		$this->assertEquals(
			[ $expected ],
			iterator_to_array( $res )
		);
	}

	public function testFindDuplicates_REDI_Table() {
		$row = new stdClass;
		$row->count = 42;
		$row->s_title = 'Foo';
		$row->s_namespace = 0;
		$row->o_id = 1001;

		$expected = [
			'count' => 42,
			's_title' => 'Foo',
			's_namespace' => 0,
			'o_id' => 1001
		];

		$qb = $this->createMockSelectQueryBuilder( [ $row ] );

		$this->connection->expects( $this->atLeastOnce() )
			->method( 'newSelectQueryBuilder' )
			->willReturn( $qb );

		$instance = new DuplicateFinder(
			$this->store,
			new IteratorFactory()
		);

		$res = $instance->findDuplicates(
			RedirectStore::TABLE_NAME
		);

		$this->assertInstanceOf(
			MappingIterator::class,
			$res
		);

		$this->assertEquals(
			[ $expected ],
			iterator_to_array( $res )
		);
	}

}

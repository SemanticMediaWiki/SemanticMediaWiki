<?php

namespace SMW\Tests\SQLStore\EntityStore;

use SMW\SQLStore\EntityStore\DuplicateFinder;
use SMW\DIWikiPage;
use SMW\MediaWiki\Connection\Query;
use SMW\IteratorFactory;
use SMW\Tests\PHPUnitCompat;

/**
 * @covers \SMW\SQLStore\EntityStore\DuplicateFinder
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2
 * @since   3.0
 *
 * @author mwjames
 */
class DuplicateFinderTest extends \PHPUnit\Framework\TestCase {

	use PHPUnitCompat;

	private $store;
	private $connection;
	private $iteratorFactory;

	protected function setUp(): void {
		$this->connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$this->store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->onlyMethods( [ 'getConnection' ] )
			->getMock();

		$this->store->expects( $this->any() )
			->method( 'getConnection' )
			->willReturn( $this->connection );

		$this->iteratorFactory = $this->getMockBuilder( '\SMW\IteratorFactory' )
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
		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->any() )
			->method( 'addQuotes' )
			->willReturnArgument( 0 );

		$connection->expects( $this->any() )
			->method( 'tableName' )
			->willReturnArgument( 0 );

		$query = new \SMW\MediaWiki\Connection\Query( $connection );

		$resultWrapper = $this->getMockBuilder( '\Wikimedia\Rdbms\ResultWrapper' )
			->disableOriginalConstructor()
			->getMock();

		$this->connection->expects( $this->atLeastOnce() )
			->method( 'newQuery' )
			->willReturn( $query );

		$this->connection->expects( $this->atLeastOnce() )
			->method( 'readQuery' )
			->willReturn( $resultWrapper );

		$instance = new DuplicateFinder(
			$this->store,
			$this->iteratorFactory
		);

		$instance->hasDuplicate( DIWikiPage::newFromText( 'Foo' ) );

		$this->assertJsonStringEqualsJsonString(
			'{' .
			'"tables": "smw_object_ids",' .
			'"fields":["smw_id","smw_sortkey"],' .
			'"conditions":[["smw_title=Foo"],["smw_namespace=0"],["smw_subobject="],["smw_iw!=:smw"],["smw_iw!=:smw-delete"],["smw_iw!=:smw-redi"]],' .
			'"joins":[],' .
			'"options":{"LIMIT":2},"alias":"","index":0,"autocommit":false}',
			(string)$query
		);
	}

	public function testFindDuplicates_ID_Table() {
		$row = new \stdClass;
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

		$query = new Query( $this->connection );

		$this->connection->expects( $this->once() )
			->method( 'newQuery' )
			->willReturn( $query );

		$this->connection->expects( $this->once() )
			->method( 'readQuery' )
			->willReturn( [ $row ] );

		$instance = new DuplicateFinder(
			$this->store,
			new IteratorFactory()
		);

		$res = $instance->findDuplicates();

		$this->assertInstanceOf(
			'\SMW\Iterators\MappingIterator',
			$res
		);

		$this->assertContains(
			'HAVING":"count(*) > 1',
			$query->__toString()
		);

		$this->assertEquals(
			[ $expected ],
			iterator_to_array( $res )
		);
	}

	public function testFindDuplicates_REDI_Table() {
		$row = new \stdClass;
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

		$query = new Query( $this->connection );

		$this->connection->expects( $this->once() )
			->method( 'newQuery' )
			->willReturn( $query );

		$this->connection->expects( $this->once() )
			->method( 'readQuery' )
			->willReturn( [ $row ] );

		$instance = new DuplicateFinder(
			$this->store,
			new IteratorFactory()
		);

		$res = $instance->findDuplicates(
			\SMW\SQLStore\RedirectStore::TABLE_NAME
		);

		$this->assertInstanceOf(
			'\SMW\Iterators\MappingIterator',
			$res
		);

		$this->assertContains(
			'HAVING":"count(*) > 1',
			$query->__toString()
		);

		$this->assertEquals(
			[ $expected ],
			iterator_to_array( $res )
		);
	}

}

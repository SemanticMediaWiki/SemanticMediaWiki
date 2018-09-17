<?php

namespace SMW\Tests\SQLStore\EntityStore;

use SMW\SQLStore\EntityStore\UniquenessLookup;
use SMW\DIWikiPage;
use SMW\MediaWiki\Connection\Query;
use SMW\IteratorFactory;

/**
 * @covers \SMW\SQLStore\EntityStore\UniquenessLookup
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2
 * @since   3.0
 *
 * @author mwjames
 */
class UniquenessLookupTest extends \PHPUnit_Framework_TestCase {

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
			UniquenessLookup::class,
			new UniquenessLookup( $this->store, $this->iteratorFactory )
		);
	}

	public function testIsUnique() {

		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->any() )
			->method( 'addQuotes' )
			->will( $this->returnArgument( 0 ) );

		$connection->expects( $this->any() )
			->method( 'tableName' )
			->will( $this->returnArgument( 0 ) );

		$query = new \SMW\MediaWiki\Connection\Query( $connection );

		$resultWrapper = $this->getMockBuilder( '\ResultWrapper' )
			->disableOriginalConstructor()
			->getMock();

		$this->connection->expects( $this->atLeastOnce() )
			->method( 'newQuery' )
			->will( $this->returnValue( $query ) );

		$this->connection->expects( $this->atLeastOnce() )
			->method( 'query' )
			->will( $this->returnValue( $resultWrapper ) );

		$instance = new UniquenessLookup(
			$this->store,
			$this->iteratorFactory
		);

		$instance->isUnique( DIWikiPage::newFromText( 'Foo' ) );

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

	public function testFindDuplicates() {

		$row = new \stdClass;
		$row->count = 42;
		$row->smw_title = 'Foo';
		$row->smw_namespace = 0;
		$row->smw_iw = '';
		$row->smw_subobject ='';

		$query = new Query( $this->connection );

		$this->connection->expects( $this->once() )
			->method( 'newQuery' )
			->will( $this->returnValue( $query ) );

		$this->connection->expects( $this->once() )
			->method( 'query' )
			->will( $this->returnValue( [ $row ] ) );

		$instance = new UniquenessLookup(
			$this->store,
			new IteratorFactory()
		);

		$this->assertInstanceOf(
			'\SMW\Iterators\MappingIterator',
			$instance->findDuplicates()
		);

		$this->assertContains(
			'HAVING":"count(*) > 1',
			$query->__toString()
		);
	}

}

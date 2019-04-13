<?php

namespace SMW\Tests\SQLStore\Lookup;

use SMW\SQLStore\Lookup\DisplayTitleLookup;
use SMW\MediaWiki\Connection\Query;
use SMW\DIWikiPage;
use SMW\DIProperty;

/**
 * @covers \SMW\SQLStore\Lookup\DisplayTitleLookup
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since   3.1
 *
 * @author mwjames
 */
class DisplayTitleLookupTest extends \PHPUnit_Framework_TestCase {

	private $store;

	protected function setUp() {

		$this->store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			DisplayTitleLookup::class,
			new DisplayTitleLookup( $this->store )
		);
	}

	public function testPrefetchFromList() {

		$subjects = [
			DIWikiPage::newFromText( 'Foo' ),
			DIWikiPage::newFromText( 'Bar' )
		];

		$rows = [
			(object)[ 's_id' => 42, 'o_blob' => null, 'o_hash' => '123' ],
			(object)[ 's_id' => 1001, 'o_blob' => 'abc_blob', 'o_hash' => 'abc_hash' ]
		];

		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$idTable = $this->getMockBuilder( '\SMWSql3SmwIds' )
			->disableOriginalConstructor()
			->getMock();

		$idTable->expects( $this->any() )
			->method( 'getId' )
			->will( $this->onConsecutiveCalls( 42, 1001 ) );

		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->once() )
			->method( 'tablename' )
			->will( $this->returnArgument( 0 ) );

		$connection->expects( $this->once() )
			->method( 'select' )
			->with(
				$this->equalTo( 'foo_table' ),
				$this->equalTo(  [ 's_id', 'o_hash', 'o_blob' ] ),
				$this->equalTo(  [ 's_id' => [ 42, 1001 ] ] ) )
			->will( $this->returnValue( $rows ) );

		$tableDefinition = $this->getMockBuilder( '\SMW\SQLStore\TableDefinition' )
			->disableOriginalConstructor()
			->getMock();

		$tableDefinition->expects( $this->any() )
			->method( 'getName' )
			->will( $this->returnValue( 'foo_table' ) );

		$this->store->expects( $this->any() )
			->method( 'getObjectIds' )
			->will( $this->returnValue( $idTable ) );

		$this->store->expects( $this->any() )
			->method( 'getConnection' )
			->will( $this->returnValue( $connection ) );

		$this->store->expects( $this->any() )
			->method( 'findPropertyTableID' )
			->will( $this->returnValue( 'Foo' ) );

		$this->store->expects( $this->any() )
			->method( 'getPropertyTables' )
			->will( $this->returnValue( [ 'Foo' => $tableDefinition ] ) );

		$instance = new DisplayTitleLookup(
			$this->store
		);

		$this->assertEquals(
			[
				$subjects[0]->getSha1() => '123',
				$subjects[1]->getSha1() => 'abc_blob'
			],
			$instance->prefetchFromList( $subjects )
		);
	}

}

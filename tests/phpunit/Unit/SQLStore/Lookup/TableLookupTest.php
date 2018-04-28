<?php

namespace SMW\Tests\SQLStore\Lookup;

use SMW\SQLStore\Lookup\TableLookup;
use SMW\DIWikiPage;

/**
 * @covers \SMW\SQLStore\Lookup\TableLookup
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class TableLookupTest extends \PHPUnit_Framework_TestCase {

	private $connection;

	protected function setUp() {

		$this->connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			TableLookup::class,
			new TableLookup( $this->connection )
		);
	}

	public function testMatch() {

		$this->connection->expects( $this->any() )
			->method( 'select' )
			->will( $this->returnValue( [] ) );

		$instance = new TableLookup( $this->connection );

		$this->assertInstanceOf(
			'\SMW\Iterators\ResultIterator',
			$instance->match( 'Foo', [], [] )
		);
	}

	public function testMap() {

		$this->connection->expects( $this->any() )
			->method( 'select' )
			->will( $this->returnValue( [] ) );

		$instance = new TableLookup( $this->connection );

		$this->assertInstanceOf(
			'\SMW\Iterators\MappingIterator',
			$instance->map( $instance->match( 'Foo', [], [] ), [ $this, 'emptyMap' ] )
		);
	}

	public function emptyMap() {}

}

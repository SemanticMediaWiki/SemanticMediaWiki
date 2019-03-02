<?php

namespace SMW\Tests\Schema;

use SMW\DIWikiPage;
use SMW\DIProperty;
use SMWDIBlob as DIBlob;
use SMW\Schema\SchemaFinder;

/**
 * @covers \SMW\Schema\SchemaFinder
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.1
 *
 * @author mwjames
 */
class SchemaFinderTest extends \PHPUnit_Framework_TestCase {

	private $store;

	protected function setUp() {

		$this->store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			SchemaFinder::class,
			new SchemaFinder( $this->store )
		);
	}

	public function testGetSchemaListByType() {

		$data[] = new DIBlob( json_encode( [ 'Foo' => [ 'Bar' => 42 ], 1001 ] ) );
		$data[] = new DIBlob( json_encode( [ 'Foo' => [ 'Foobar' => 'test' ], [ 'Foo' => 'Bar' ] ] ) );

		$this->store->expects( $this->any() )
			->method( 'getPropertySubjects' )
			->will( $this->returnValue( [
				DIWikiPage::newFromText( 'Foo' ),
				DIWikiPage::newFromText( 'Bar' ) ] ) );

		$this->store->expects( $this->any() )
			->method( 'getPropertyValues' )
			->with(
				$this->anyThing(),
				$this->equalTo( new DIProperty( '_SCHEMA_DEF' ) ) )
			->will( $this->onConsecutiveCalls( [ $data[0] ], [ $data[1] ] ) );

		$instance = new SchemaFinder( $this->store );

		$this->assertInstanceOf(
			'\SMW\Schema\SchemaList',
			$instance->getSchemaListByType( 'Foo' )
		);
	}

}

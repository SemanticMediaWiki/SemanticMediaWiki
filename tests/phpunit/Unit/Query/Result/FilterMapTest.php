<?php

namespace SMW\Tests\Query\Result;

use SMW\DIProperty;
use SMW\DIWikiPage;
use SMW\Query\Result\FilterMap;

/**
 * @covers \SMW\Query\Result\FilterMap
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.2
 *
 * @author mwjames
 */
class FilterMapTest extends \PHPUnit_Framework_TestCase {

	private $store;
	private $entityIdManager;

	protected function setUp() : void {
		parent::setUp();

		$this->entityIdManager = $this->getMockBuilder( '\SMW\SQLStore\EntityStore\EntityIdManager' )
			->disableOriginalConstructor()
			->getMock();

		$this->store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->setMethods( [ 'getObjectIds' ] )
			->getMockForAbstractClass();

		$this->store->expects( $this->any() )
			->method( 'getObjectIds' )
			->will( $this->returnValue( $this->entityIdManager ) );
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			FilterMap::class,
			new FilterMap( $this->store, [] )
		);
	}

	public function testGetCountListByType() {

		$this->entityIdManager->expects( $this->once() )
			->method( 'preload' )
			->with( $this->equalTo( [ 'Foo' ] ) );

		$instance = new FilterMap(
			$this->store,
			[ 'Foo' ]
		);

		$this->assertEquals(
			[],
			$instance->getCountListByType( FilterMap::PROPERTY_LIST )
		);
	}

}

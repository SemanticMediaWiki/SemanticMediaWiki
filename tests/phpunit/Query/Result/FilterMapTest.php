<?php

namespace SMW\Tests\Query\Result;

use PHPUnit\Framework\TestCase;
use SMW\Query\Result\FilterMap;
use SMW\SQLStore\EntityStore\EntityIdManager;
use SMW\Store;

/**
 * @covers \SMW\Query\Result\FilterMap
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.2
 *
 * @author mwjames
 */
class FilterMapTest extends TestCase {

	private $store;
	private $entityIdManager;

	protected function setUp(): void {
		parent::setUp();

		$this->entityIdManager = $this->getMockBuilder( EntityIdManager::class )
			->disableOriginalConstructor()
			->getMock();

		$this->store = $this->getMockBuilder( Store::class )
			->disableOriginalConstructor()
			->setMethods( [ 'getObjectIds' ] )
			->getMockForAbstractClass();

		$this->store->expects( $this->any() )
			->method( 'getObjectIds' )
			->willReturn( $this->entityIdManager );
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
			->with( [ 'Foo' ] );

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

<?php

namespace SMW\Tests\Query\Result;

use SMW\DIProperty;
use SMW\DIWikiPage;
use SMW\Query\Result\FilterMap;
use SMW\SQLStore\EntityStore\EntityIdManager;

/**
 * @covers \SMW\Query\Result\FilterMap
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.2
 *
 * @author mwjames
 */
class FilterMapTest extends \PHPUnit\Framework\TestCase {

	private $store;
	private $entityIdManager;

	protected function setUp(): void {
		parent::setUp();

		$this->entityIdManager = $this->createMock( EntityIdManager::class );

		$this->store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();
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

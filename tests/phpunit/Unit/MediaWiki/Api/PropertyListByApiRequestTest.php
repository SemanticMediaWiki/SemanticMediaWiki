<?php

namespace SMW\Tests\Unit\MediaWiki\Api;

use PHPUnit\Framework\TestCase;
use SMW\DataItems\Error;
use SMW\DataItems\Property;
use SMW\Lookup\CachedListLookup;
use SMW\MediaWiki\Api\PropertyListByApiRequest;
use SMW\Property\SpecificationLookup;
use SMW\Store;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\MediaWiki\Api\PropertyListByApiRequest
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 2.4
 *
 * @author mwjames
 */
class PropertyListByApiRequestTest extends TestCase {

	private $store;
	private $testEnvironment;

	protected function setUp(): void {
		parent::setUp();

		$this->store = $this->getMockBuilder( Store::class )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$this->testEnvironment = new TestEnvironment();
		$this->testEnvironment->registerObject( 'Store', $this->store );
	}

	protected function tearDown(): void {
		$this->testEnvironment->tearDown();
	}

	public function testCanConstruct() {
		$propertySpecificationLookup = $this->getMockBuilder( SpecificationLookup::class )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			PropertyListByApiRequest::class,
			new PropertyListByApiRequest( $this->store, $propertySpecificationLookup )
		);
	}

	public function testGetSerializedListForProperty() {
		$list[] = [
			new Property( 'Foo' ),
			42
		];

		$list[] = [
			new Property( 'Foaf:Foo' ),
			1001
		];

		$list[] = [
			new Error( 'error' ),
			-1
		];

		$list[] = [];

		$isCached = true;

		$expectedSerializedPropertyList = [
			'Foo' => [
				'label' => 'Foo',
				'key' => 'Foo',
				'isUserDefined' => true,
				'usageCount' => 42,
				'description' => ''
			],
			'Foaf:Foo' => [
				'label' => 'Foaf:Foo',
				'key' => 'Foaf:Foo',
				'isUserDefined' => true,
				'usageCount' => 1001,
				'description' => ''
			]
		];

		$expectedNamespaces = [
			'Foaf'
		];

		$expectedMeta = [
			'limit' => 3,
			'count' => 2,
			'isCached' => $isCached
		];

		$cachedListLookup = $this->getMockBuilder( CachedListLookup::class )
			->disableOriginalConstructor()
			->getMock();

		$cachedListLookup->expects( $this->once() )
			->method( 'fetchList' )
			->willReturn( $list );

		$cachedListLookup->expects( $this->once() )
			->method( 'isFromCache' )
			->willReturn( $isCached );

		$this->store->expects( $this->once() )
			->method( 'getPropertiesSpecial' )
			->willReturn( $cachedListLookup );

		$propertySpecificationLookup = $this->getMockBuilder( SpecificationLookup::class )
			->disableOriginalConstructor()
			->getMock();

		$instance = new PropertyListByApiRequest( $this->store, $propertySpecificationLookup );
		$instance->setLimit( 3 );

		$this->assertTrue(
			$instance->findPropertyListBy( 'Foo' )
		);

		$this->assertEquals(
			$expectedSerializedPropertyList,
			$instance->getPropertyList()
		);

		$this->assertEquals(
			$expectedNamespaces,
			$instance->getNamespaces()
		);

		$this->assertEquals(
			$expectedMeta,
			$instance->getMeta()
		);

		$this->assertEquals(
			3,
			$instance->getContinueOffset()
		);
	}

}

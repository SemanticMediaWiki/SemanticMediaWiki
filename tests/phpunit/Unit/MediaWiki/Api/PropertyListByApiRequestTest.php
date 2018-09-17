<?php

namespace SMW\Tests\MediaWiki\Api;

use SMW\DIProperty;
use SMW\MediaWiki\Api\PropertyListByApiRequest;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\MediaWiki\Api\PropertyListByApiRequest
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.4
 *
 * @author mwjames
 */
class PropertyListByApiRequestTest extends \PHPUnit_Framework_TestCase {

	private $store;
	private $testEnvironment;

	protected function setUp() {
		parent::setUp();

		$this->store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$this->testEnvironment = new TestEnvironment();
		$this->testEnvironment->registerObject( 'Store', $this->store );
	}

	protected function tearDown() {
		$this->testEnvironment->tearDown();
 	}

	public function testCanConstruct() {

		$propertySpecificationLookup = $this->getMockBuilder( '\SMW\PropertySpecificationLookup' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			'SMW\MediaWiki\Api\PropertyListByApiRequest',
			new PropertyListByApiRequest( $this->store, $propertySpecificationLookup )
		);
	}

	public function testGetSerializedListForProperty() {

		$list[] = [
			new DIProperty( 'Foo' ),
			42
		];

		$list[] = [
			new DIProperty( 'Foaf:Foo' ),
			1001
		];

		$list[] = [
			new \SMWDIError( 'error' ),
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

		$cachedListLookup = $this->getMockBuilder( '\SMW\SQLStore\Lookup\CachedListLookup' )
			->disableOriginalConstructor()
			->getMock();

		$cachedListLookup->expects( $this->once() )
			->method( 'fetchList' )
			->will( $this->returnValue( $list ) );

		$cachedListLookup->expects( $this->once() )
			->method( 'isFromCache' )
			->will( $this->returnValue( $isCached ) );

		$this->store->expects( $this->once() )
			->method( 'getPropertiesSpecial' )
			->will( $this->returnValue( $cachedListLookup ) );

		$propertySpecificationLookup = $this->getMockBuilder( '\SMW\PropertySpecificationLookup' )
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

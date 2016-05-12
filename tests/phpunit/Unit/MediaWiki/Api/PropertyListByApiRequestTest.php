<?php

namespace SMW\Tests\MediaWiki\Api;

use SMW\DIProperty;
use SMW\MediaWiki\Api\PropertyListByApiRequest;

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

	protected function setUp() {
		parent::setUp();

		$this->store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();
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

		$list[] = array(
			new DIProperty( 'Foo' ),
			42
		);

		$list[] = array(
			new DIProperty( 'Foaf:Foo' ),
			1001
		);

		$list[] = array(
			new \SMWDIError( 'error' ),
			-1
		);

		$list[] = array();

		$isCached = true;

		$expectedSerializedPropertyList = array(
			'Foo' => array(
				'label' => 'Foo',
				'key' => 'Foo',
				'isUserDefined' => true,
				'usageCount' => 42,
				'description' => ''
			),
			'Foaf:Foo' => array(
				'label' => 'Foaf:Foo',
				'key' => 'Foaf:Foo',
				'isUserDefined' => true,
				'usageCount' => 1001,
				'description' => ''
			)
		);

		$expectedNamespaces = array(
			'Foaf'
		);

		$expectedMeta = array(
			'limit' => 3,
			'count' => 2,
			'isCached' => $isCached
		);

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
			$instance->findPropertyListFor( 'Foo' )
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

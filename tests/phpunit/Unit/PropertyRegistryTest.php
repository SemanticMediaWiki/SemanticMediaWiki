<?php

namespace SMW\Tests;

use SMW\DataTypeRegistry;
use SMW\DIProperty;
use SMW\PropertyAliasFinder;
use SMW\PropertyLabelFinder;
use SMW\PropertyRegistry;

/**
 * @covers \SMW\PropertyRegistry
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.1
 *
 * @author mwjames
 */
class PropertyRegistryTest extends \PHPUnit_Framework_TestCase {

	private $cache;
	private $store;

	protected function setUp() {
		parent::setUp();

		$this->cache = $this->getMockBuilder( '\Onoi\Cache\Cache' )
			->disableOriginalConstructor()
			->getMock();
	}

	protected function tearDown() {
		PropertyRegistry::clear();
		DataTypeRegistry::clear();

		parent::tearDown();
	}

	public function testCanConstruct() {

		$datatypeRegistry = $this->getMockBuilder( '\SMW\DataTypeRegistry' )
			->disableOriginalConstructor()
			->getMock();

		$datatypeRegistry->expects( $this->once() )
			->method( 'getKnownTypeLabels' )
			->will( $this->returnValue( [] ) );

		$datatypeRegistry->expects( $this->once() )
			->method( 'getKnownTypeAliases' )
			->will( $this->returnValue( [] ) );

		$propertyLabelFinder = $this->getMockBuilder( '\SMW\PropertyLabelFinder' )
			->disableOriginalConstructor()
			->getMock();

		$propertyAliasFinder = $this->getMockBuilder( '\SMW\PropertyAliasFinder' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			'\SMW\PropertyRegistry',
			new PropertyRegistry( $datatypeRegistry, $propertyLabelFinder, $propertyAliasFinder )
		);
	}

	public function testGetInstance() {

		$instance = PropertyRegistry::getInstance();

		$this->assertSame(
			$instance,
			PropertyRegistry::getInstance()
		);

		PropertyRegistry::clear();

		$this->assertNotSame(
			$instance,
			PropertyRegistry::getInstance()
		);
	}

	public function testLanguageIndependantPropertyLabelAliasInvocation() {

		$datatypeRegistry = $this->getMockBuilder( '\SMW\DataTypeRegistry' )
			->disableOriginalConstructor()
			->getMock();

		$datatypeRegistry->expects( $this->once() )
			->method( 'getKnownTypeLabels' )
			->will( $this->returnValue( [ '_uri' => 'URL' ] ) );

		$datatypeRegistry->expects( $this->once() )
			->method( 'getKnownTypeAliases' )
			->will( $this->returnValue( [ 'URI' => '_uri' ] ) );

		$propertyLabelFinder = $this->getMockBuilder( '\SMW\PropertyLabelFinder' )
			->disableOriginalConstructor()
			->getMock();

		$propertyAliases = new PropertyAliasFinder(
			$this->cache,
			[ 'Has type' => '_TYPE' ]
		);

		$instance = new PropertyRegistry(
			$datatypeRegistry,
			$propertyLabelFinder,
			$propertyAliases
		);

		$this->assertEquals(
			[
				'Has type' => '_TYPE',
				'URI' => '_uri' ],
			$instance->getKnownPropertyAliases()
		);
	}

	public function testRegisterProperty() {

		$datatypeRegistry = $this->getMockBuilder( '\SMW\DataTypeRegistry' )
			->disableOriginalConstructor()
			->getMock();

		$datatypeRegistry->expects( $this->once() )
			->method( 'getKnownTypeLabels' )
			->will( $this->returnValue( [] ) );

		$datatypeRegistry->expects( $this->once() )
			->method( 'getKnownTypeAliases' )
			->will( $this->returnValue( [] ) );

		$propertyLabelFinder = $this->getMockBuilder( '\SMW\PropertyLabelFinder' )
			->disableOriginalConstructor()
			->getMock();

		$propertyAliases = new PropertyAliasFinder(
			$this->cache
		);

		$instance = new PropertyRegistry(
			$datatypeRegistry,
			$propertyLabelFinder,
			$propertyAliases
		);

		$instance->registerProperty(
			DIProperty::TYPE_HAS_TYPE,
			'__typ',
			'Has type',
			true
		);

		$this->assertEquals(
			[ '_TYPE' => [ '__typ', true, true ] ],
			$instance->getPropertyList()
		);

		$this->assertTrue(
			$instance->isVisible( '_TYPE' )
		);

		$this->assertTrue(
			$instance->isAnnotable( '_TYPE' )
		);

		$this->assertTrue(
			$instance->isRegistered( '_TYPE' )
		);
	}

	public function testUnregisterProperty() {

		$datatypeRegistry = $this->getMockBuilder( '\SMW\DataTypeRegistry' )
			->disableOriginalConstructor()
			->getMock();

		$datatypeRegistry->expects( $this->once() )
			->method( 'getKnownTypeLabels' )
			->will( $this->returnValue( [] ) );

		$datatypeRegistry->expects( $this->once() )
			->method( 'getKnownTypeAliases' )
			->will( $this->returnValue( [] ) );

		$propertyLabelFinder = $this->getMockBuilder( '\SMW\PropertyLabelFinder' )
			->disableOriginalConstructor()
			->getMock();

		$propertyAliases = new PropertyAliasFinder(
			$this->cache
		);

		$instance = new PropertyRegistry(
			$datatypeRegistry,
			$propertyLabelFinder,
			$propertyAliases
		);

		$this->assertFalse(
			$instance->isVisible( '_UnregisteredType' )
		);

		$this->assertFalse(
			$instance->isAnnotable( '_UnregisteredType' )
		);

		$this->assertFalse(
			$instance->isRegistered( '_UnregisteredType' )
		);
	}

	public function testFindPropertyId() {

		$datatypeRegistry = $this->getMockBuilder( '\SMW\DataTypeRegistry' )
			->disableOriginalConstructor()
			->getMock();

		$datatypeRegistry->expects( $this->once() )
			->method( 'getKnownTypeLabels' )
			->will( $this->returnValue( [] ) );

		$datatypeRegistry->expects( $this->once() )
			->method( 'getKnownTypeAliases' )
			->will( $this->returnValue( [] ) );

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$propertyLabelFinder = new PropertyLabelFinder( $store, [] );

		$propertyAliases = new PropertyAliasFinder(
			$this->cache
		);

		$instance = new PropertyRegistry(
			$datatypeRegistry,
			$propertyLabelFinder,
			$propertyAliases
		);

		$instance->registerProperty(
			DIProperty::TYPE_HAS_TYPE,
			'__typ',
			'Has type',
			true
		);

		$instance->registerPropertyAlias( '_TYPE', 'foo' );

		$this->assertEquals(
			'_TYPE',
			$instance->findPropertyIdByLabel( 'Has type' )
		);

		$this->assertEquals(
			'_TYPE',
			$instance->findPropertyIdByLabel( 'foo', true )
		);

		// findPropertyId legacy test
		$this->assertEquals(
			'_TYPE',
			$instance->findPropertyId( 'Has type' )
		);
	}

	public function testFindPropertyLabelForRegisteredId() {

		$datatypeRegistry = $this->getMockBuilder( '\SMW\DataTypeRegistry' )
			->disableOriginalConstructor()
			->getMock();

		$datatypeRegistry->expects( $this->once() )
			->method( 'getKnownTypeLabels' )
			->will( $this->returnValue( [] ) );

		$datatypeRegistry->expects( $this->once() )
			->method( 'getKnownTypeAliases' )
			->will( $this->returnValue( [] ) );

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$propertyLabelFinder = new PropertyLabelFinder( $store, [] );

		$propertyAliases = new PropertyAliasFinder(
			$this->cache
		);

		$instance = new PropertyRegistry(
			$datatypeRegistry,
			$propertyLabelFinder,
			$propertyAliases
		);

		$instance->registerProperty(
			DIProperty::TYPE_HAS_TYPE,
			'__typ',
			'Has type',
			true
		);

		$this->assertEquals(
			'Has type',
			$instance->findPropertyLabelById( '_TYPE' )
		);

		// findPropertyLabel legacy test
		$this->assertEquals(
			'Has type',
			$instance->findPropertyLabel( '_TYPE' )
		);

		// This was part of an extra test but the extra test caused an segfault on postgres travis-ci

		$this->assertEquals(
			'__typ',
			$instance->getPropertyTypeId( '_TYPE' )
		);

		// getPredefinedPropertyTypeId legacy test
		$this->assertEquals(
			'__typ',
			$instance->getPredefinedPropertyTypeId( '_TYPE' )
		);
	}

	public function testFindPropertyInfoForUnregisteredId() {

		$datatypeRegistry = $this->getMockBuilder( '\SMW\DataTypeRegistry' )
			->disableOriginalConstructor()
			->getMock();

		$datatypeRegistry->expects( $this->once() )
			->method( 'getKnownTypeLabels' )
			->will( $this->returnValue( [] ) );

		$datatypeRegistry->expects( $this->once() )
			->method( 'getKnownTypeAliases' )
			->will( $this->returnValue( [] ) );

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$propertyLabelFinder = new PropertyLabelFinder( $store, [] );

		$propertyAliases = new PropertyAliasFinder(
			$this->cache
		);

		$instance = new PropertyRegistry(
			$datatypeRegistry,
			$propertyLabelFinder,
			$propertyAliases
		);

		$this->assertEquals(
			'',
			$instance->findPropertyLabelById( '_UnknownId' )
		);

		$this->assertEquals(
			'',
			$instance->getPropertyTypeId( '_UnknownId' )
		);

		$this->assertFalse(
			$instance->findPropertyIdByLabel( 'unknownLabel' )
		);

		$this->assertFalse(
			$instance->findPropertyIdByLabel( 'unknownLabel', true )
		);
	}

	public function testfindPropertyIdFromLabelByLanguageCode() {

		$datatypeRegistry = $this->getMockBuilder( '\SMW\DataTypeRegistry' )
			->disableOriginalConstructor()
			->getMock();

		$datatypeRegistry->expects( $this->once() )
			->method( 'getKnownTypeLabels' )
			->will( $this->returnValue( [] ) );

		$datatypeRegistry->expects( $this->once() )
			->method( 'getKnownTypeAliases' )
			->will( $this->returnValue( [] ) );

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$propertyLabelFinder = new PropertyLabelFinder( $store, [] );

		$propertyAliases = new PropertyAliasFinder(
			$this->cache
		);

		$instance = new PropertyRegistry(
			$datatypeRegistry,
			$propertyLabelFinder,
			$propertyAliases
		);

		$this->assertEquals(
			'_TYPE',
			$instance->findPropertyIdFromLabelByLanguageCode( 'A le type', 'fr' )
		);
	}

	public function testFindPropertyLabelByLanguageCode() {

		$datatypeRegistry = $this->getMockBuilder( '\SMW\DataTypeRegistry' )
			->disableOriginalConstructor()
			->getMock();

		$datatypeRegistry->expects( $this->once() )
			->method( 'getKnownTypeLabels' )
			->will( $this->returnValue( [] ) );

		$datatypeRegistry->expects( $this->once() )
			->method( 'getKnownTypeAliases' )
			->will( $this->returnValue( [] ) );

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$propertyLabelFinder = new PropertyLabelFinder( $store, [] );

		$propertyAliases = new PropertyAliasFinder(
			$this->cache
		);

		$instance = new PropertyRegistry(
			$datatypeRegistry,
			$propertyLabelFinder,
			$propertyAliases
		);

		$this->assertEquals(
			'A le type',
			$instance->findPropertyLabelFromIdByLanguageCode( '_TYPE', 'fr' )
		);
	}

	public function testPropertyDescriptionMsgKey() {

		$datatypeRegistry = $this->getMockBuilder( '\SMW\DataTypeRegistry' )
			->disableOriginalConstructor()
			->getMock();

		$datatypeRegistry->expects( $this->once() )
			->method( 'getKnownTypeLabels' )
			->will( $this->returnValue( [] ) );

		$datatypeRegistry->expects( $this->once() )
			->method( 'getKnownTypeAliases' )
			->will( $this->returnValue( [] ) );

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$propertyLabelFinder = new PropertyLabelFinder( $store, [] );

		$propertyAliases = new PropertyAliasFinder(
			$this->cache
		);

		$instance = new PropertyRegistry(
			$datatypeRegistry,
			$propertyLabelFinder,
			$propertyAliases
		);

		$instance->registerPropertyDescriptionMsgKeyById( '_foo', 'bar' );

		$this->assertEquals(
			'bar',
			$instance->findPropertyDescriptionMsgKeyById( '_foo' )
		);

		$this->assertEmpty(
			$instance->findPropertyDescriptionMsgKeyById( 'unknown' )
		);
	}

	public function testDataTypePropertyExemptionList() {

		$datatypeRegistry = $this->getMockBuilder( '\SMW\DataTypeRegistry' )
			->disableOriginalConstructor()
			->getMock();

		$datatypeRegistry->expects( $this->once() )
			->method( 'getKnownTypeLabels' )
			->will( $this->returnValue( [ '_foo' => 'Foo', '_foobar' => 'Foobar' ] ) );

		$datatypeRegistry->expects( $this->once() )
			->method( 'getKnownTypeAliases' )
			->will( $this->returnValue( [ 'Bar' => '_bar' ] ) );

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$propertyLabelFinder = new PropertyLabelFinder( $store, [] );

		$propertyAliases = new PropertyAliasFinder(
			$this->cache
		);

		$dataTypePropertyExemptionList = [ 'Foo', 'Bar' ];

		$instance = new PropertyRegistry(
			$datatypeRegistry,
			$propertyLabelFinder,
			$propertyAliases,
			$dataTypePropertyExemptionList
		);

		$this->assertEquals(
			'_foobar',
			$instance->findPropertyIdByLabel( 'Foobar' )
		);

		$this->assertFalse(
			$instance->findPropertyIdByLabel( 'Foo' )
		);

		$this->assertFalse(
			$instance->findPropertyIdByLabel( 'Bar' )
		);
	}

	/**
	 * @dataProvider typeToCanonicalLabelProvider
	 */
	public function testFindCanonicalPropertyLabelById( $id, $expected ) {

		$instance = PropertyRegistry::getInstance();

		$this->assertSame(
			$expected,
			$instance->findCanonicalPropertyLabelById( $id )
		);
	}

	public function typeToCanonicalLabelProvider() {

		$provider[] = [
			'_txt',
			'Text'
		];

		$provider[] = [
			'_TEXT',
			'Text'
		];

		return $provider;
	}

}

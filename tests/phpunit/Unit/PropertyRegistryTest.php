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
			->will( $this->returnValue( array() ) );

		$datatypeRegistry->expects( $this->once() )
			->method( 'getKnownTypeAliases' )
			->will( $this->returnValue( array() ) );

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
			->will( $this->returnValue( array( '_uri' => 'URL' ) ) );

		$datatypeRegistry->expects( $this->once() )
			->method( 'getKnownTypeAliases' )
			->will( $this->returnValue( array( 'URI' => '_uri' ) ) );

		$propertyLabelFinder = $this->getMockBuilder( '\SMW\PropertyLabelFinder' )
			->disableOriginalConstructor()
			->getMock();

		$propertyAliases = new PropertyAliasFinder( array( 'Has type' => '_TYPE' ) );

		$instance = new PropertyRegistry(
			$datatypeRegistry,
			$propertyLabelFinder,
			$propertyAliases
		);

		$this->assertEquals(
			array(
				'Has type' => '_TYPE',
				'URI' => '_uri' ),
			$instance->getKnownPropertyAliases()
		);
	}

	public function testRegisterProperty() {

		$datatypeRegistry = $this->getMockBuilder( '\SMW\DataTypeRegistry' )
			->disableOriginalConstructor()
			->getMock();

		$datatypeRegistry->expects( $this->once() )
			->method( 'getKnownTypeLabels' )
			->will( $this->returnValue( array() ) );

		$datatypeRegistry->expects( $this->once() )
			->method( 'getKnownTypeAliases' )
			->will( $this->returnValue( array() ) );

		$propertyLabelFinder = $this->getMockBuilder( '\SMW\PropertyLabelFinder' )
			->disableOriginalConstructor()
			->getMock();

		$propertyAliases = new PropertyAliasFinder();

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
			array( '_TYPE' => array( '__typ', true, true ) ),
			$instance->getKnownPropertyTypes()
		);

		$this->assertTrue(
			$instance->isVisibleToUser( '_TYPE' )
		);

		$this->assertTrue(
			$instance->isUnrestrictedForAnnotationUse( '_TYPE' )
		);

		$this->assertTrue(
			$instance->isKnownPropertyId( '_TYPE' )
		);
	}

	public function testUnregisterProperty() {

		$datatypeRegistry = $this->getMockBuilder( '\SMW\DataTypeRegistry' )
			->disableOriginalConstructor()
			->getMock();

		$datatypeRegistry->expects( $this->once() )
			->method( 'getKnownTypeLabels' )
			->will( $this->returnValue( array() ) );

		$datatypeRegistry->expects( $this->once() )
			->method( 'getKnownTypeAliases' )
			->will( $this->returnValue( array() ) );

		$propertyLabelFinder = $this->getMockBuilder( '\SMW\PropertyLabelFinder' )
			->disableOriginalConstructor()
			->getMock();

		$propertyAliases = new PropertyAliasFinder();

		$instance = new PropertyRegistry(
			$datatypeRegistry,
			$propertyLabelFinder,
			$propertyAliases
		);

		$this->assertFalse(
			$instance->isVisibleToUser( '_UnregisteredType' )
		);

		$this->assertFalse(
			$instance->isUnrestrictedForAnnotationUse( '_UnregisteredType' )
		);

		$this->assertFalse(
			$instance->isKnownPropertyId( '_UnregisteredType' )
		);
	}

	public function testFindPropertyId() {

		$datatypeRegistry = $this->getMockBuilder( '\SMW\DataTypeRegistry' )
			->disableOriginalConstructor()
			->getMock();

		$datatypeRegistry->expects( $this->once() )
			->method( 'getKnownTypeLabels' )
			->will( $this->returnValue( array() ) );

		$datatypeRegistry->expects( $this->once() )
			->method( 'getKnownTypeAliases' )
			->will( $this->returnValue( array() ) );

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$propertyLabelFinder = new PropertyLabelFinder( $store, array() );

		$propertyAliases = new PropertyAliasFinder();

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
			->will( $this->returnValue( array() ) );

		$datatypeRegistry->expects( $this->once() )
			->method( 'getKnownTypeAliases' )
			->will( $this->returnValue( array() ) );

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$propertyLabelFinder = new PropertyLabelFinder( $store, array() );

		$propertyAliases = new PropertyAliasFinder();

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
			->will( $this->returnValue( array() ) );

		$datatypeRegistry->expects( $this->once() )
			->method( 'getKnownTypeAliases' )
			->will( $this->returnValue( array() ) );

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$propertyLabelFinder = new PropertyLabelFinder( $store, array() );

		$propertyAliases = new PropertyAliasFinder();

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

	public function testFindPropertyIdByLanguageCode() {

		$datatypeRegistry = $this->getMockBuilder( '\SMW\DataTypeRegistry' )
			->disableOriginalConstructor()
			->getMock();

		$datatypeRegistry->expects( $this->once() )
			->method( 'getKnownTypeLabels' )
			->will( $this->returnValue( array() ) );

		$datatypeRegistry->expects( $this->once() )
			->method( 'getKnownTypeAliases' )
			->will( $this->returnValue( array() ) );

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$propertyLabelFinder = new PropertyLabelFinder( $store, array() );

		$propertyAliases = new PropertyAliasFinder();

		$instance = new PropertyRegistry(
			$datatypeRegistry,
			$propertyLabelFinder,
			$propertyAliases
		);

		$this->assertEquals(
			'_TYPE',
			$instance->findPropertyIdByLanguageCode( 'A le type', 'fr' )
		);
	}

}

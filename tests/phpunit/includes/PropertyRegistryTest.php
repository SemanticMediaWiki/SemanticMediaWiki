<?php

namespace SMW\Tests;

use SMW\PropertyRegistry;
use SMW\DataTypeRegistry;

/**
 * @uses \SMW\PropertyRegistry
 *
 * @ingroup Test
 *
 * @group SMW
 * @group SMWExtension
 * @group semantic-mediawiki-unit
 * @group mediawiki-databaseless
 *
 * @license GNU GPL v2+
 * @since 1.9.3
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

		$propertyLabels = array();
		$propertyAliases = array();

		$this->assertInstanceOf(
			'\SMW\PropertyRegistry',
			new PropertyRegistry( $datatypeRegistry, $propertyLabels, $propertyAliases )
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

		$propertyLabels = array( '_TYPE' => 'Has type' );
		$propertyAliases = array( 'Has type' => '_TYPE' );

		$instance = new PropertyRegistry( $datatypeRegistry, $propertyLabels, $propertyAliases );

		$this->assertEquals(
			array(
				'_TYPE' => 'Has type',
				'_uri' => 'URL' ),
			$instance->getKnownPropertyLabels()
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

		$propertyLabels = array( );
		$propertyAliases = array();

		$instance = new PropertyRegistry( $datatypeRegistry, $propertyLabels, $propertyAliases );
		$instance->registerProperty( PropertyRegistry::TYPE_HAS_TYPE, '__typ', 'Has type', true );

		$this->assertEquals(
			array( '_TYPE' => 'Has type' ),
			$instance->getKnownPropertyLabels()
		);

		$this->assertEquals(
			array( '_TYPE' => array( '__typ', true ) ),
			$instance->getKnownPropertyTypes()
		);

		$this->assertTrue(
			$instance->getPropertyVisibility( '_TYPE' )
		);

		$this->assertTrue(
			$instance->isKnownProperty( '_TYPE' )
		);

		$this->assertFalse(
			$instance->getPropertyVisibility( '_UnregisteredType' )
		);

		$this->assertFalse(
			$instance->isKnownProperty( '_UnregisteredType' )
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

		$propertyLabels = array( );
		$propertyAliases = array();

		$instance = new PropertyRegistry( $datatypeRegistry, $propertyLabels, $propertyAliases );
		$instance->registerProperty( PropertyRegistry::TYPE_HAS_TYPE, '__typ', 'Has type', true );
		$instance->registerPropertyAlias( '_TYPE', 'foo' );

		$this->assertEquals( '_TYPE', $instance->findPropertyIdByLabel( 'Has type' ) );
		$this->assertEquals( '_TYPE', $instance->findPropertyIdByLabel( 'foo', true ) );

		$this->assertFalse( $instance->findPropertyIdByLabel( 'unknownLabel' ) );
		$this->assertFalse( $instance->findPropertyIdByLabel( 'unknownLabel', true ) );

		// findPropertyId legacy test
		$this->assertEquals( '_TYPE', $instance->findPropertyId( 'Has type' ) );
	}

	public function testFindPropertyLabel() {

		$datatypeRegistry = $this->getMockBuilder( '\SMW\DataTypeRegistry' )
			->disableOriginalConstructor()
			->getMock();

		$datatypeRegistry->expects( $this->once() )
			->method( 'getKnownTypeLabels' )
			->will( $this->returnValue( array() ) );

		$datatypeRegistry->expects( $this->once() )
			->method( 'getKnownTypeAliases' )
			->will( $this->returnValue( array() ) );

		$propertyLabels = array( );
		$propertyAliases = array();

		$instance = new PropertyRegistry( $datatypeRegistry, $propertyLabels, $propertyAliases );
		$instance->registerProperty( PropertyRegistry::TYPE_HAS_TYPE, '__typ', 'Has type', true );

		$this->assertEquals( 'Has type' , $instance->findPropertyLabelById( '_TYPE' ) );
		$this->assertEquals( '' , $instance->findPropertyLabelById( '_UnknownId' ) );

		// findPropertyLabel legacy test
		$this->assertEquals( 'Has type', $instance->findPropertyLabel( '_TYPE' ) );

		// This was part of an extra test but the extra test caused an segfault on postgres travis-ci

		$this->assertEquals( '__typ' , $instance->getDataTypeId( '_TYPE' ) );
		$this->assertEquals( '' , $instance->getDataTypeId( '_UnknownId' ) );

		// getPredefinedPropertyTypeId legacy test
		$this->assertEquals( '__typ', $instance->getPredefinedPropertyTypeId( '_TYPE' ) );
	}

}

<?php

namespace SMW\Tests;

use SMW\DIProperty;
use SMW\DIWikiPage;

/**
 * @covers \SMW\DIProperty
 * @covers SMWDataItem
 *
 * @group SMW
 * @group SMWExtension
 * @group SMWDataItems
 *
 * @author Nischay Nahata
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class DIPropertyTest extends DataItemTest {

	/**
	 * @see DataItemTest::getClass
	 *
	 * @since 1.8
	 *
	 * @return string
	 */
	public function getClass() {
		return '\SMWDIProperty';
	}

	/**
	 * @see DataItemTest::constructorProvider
	 *
	 * @since 1.8
	 *
	 * @return array
	 */
	public function constructorProvider() {
		return array(
			array( 0 ),
			array( 243.35353 ),
			array( 'ohi there' ),
		);
	}

	public function testSetPropertyTypeIdOnUserDefinedProperty() {

		$property = new DIProperty( 'SomeBlobProperty' );
		$property->setPropertyTypeId( '_txt' );

		$this->assertEquals( '_txt', $property->findPropertyTypeID() );
	}

	public function testSetPropertyTypeIdOnPredefinedProperty() {

		$property = new DIProperty( '_MDAT' );
		$property->setPropertyTypeId( '_dat' );

		$this->assertEquals( '_dat', $property->findPropertyTypeID() );
	}

	public function testSetUnknownPropertyTypeIdThrowsException() {

		$property = new DIProperty( 'SomeUnknownTypeIdProperty' );

		$this->setExpectedException( 'RuntimeException' );
		$property->setPropertyTypeId( '_unknownTypeId' );
	}

	public function testSetPropertyTypeIdOnPredefinedPropertyThrowsException() {

		$property = new DIProperty( '_MDAT' );

		$this->setExpectedException( 'InvalidArgumentException' );
		$property->setPropertyTypeId( '_txt' );
	}

	public function testCorrectInversePrefixForPredefinedProperty() {

		$property = new DIProperty( '_SOBJ', true );

		$this->assertTrue(
			$property->isInverse()
		);

		$label = $property->getLabel();

		$this->assertEquals(
			'-',
			$label{0}
		);
	}

	public function testUseInterwikiPrefix() {

		$property = new DIProperty( 'Foo' );
		$property->setInterwiki( 'bar' );

		$this->assertEquals(
			new DIWikiPage( 'Foo', SMW_NS_PROPERTY, 'bar' ),
			$property->getDiWikiPage()
		);
	}

	public function testCreatePropertyFromLabelThatContainsInverseMarker() {

		$property = DIProperty::newFromUserLabel( '-Foo' );
		$property->setInterwiki( 'bar' );

		$this->assertTrue(
			$property->isInverse()
		);

		$this->assertEquals(
			new DIWikiPage( 'Foo', SMW_NS_PROPERTY, 'bar' ),
			$property->getDiWikiPage()
		);
	}

	public function testCreatePropertyFromLabelThatContainsLanguageMarker() {

		$property = DIProperty::newFromUserLabel( '-Foo@en' );
		$property->setInterwiki( 'bar' );

		$this->assertTrue(
			$property->isInverse()
		);

		$this->assertEquals(
			new DIWikiPage( 'Foo', SMW_NS_PROPERTY, 'bar' ),
			$property->getDiWikiPage()
		);
	}

	public function testCanonicalRepresentation() {

		$property = new DIProperty( '_MDAT' );

		$this->assertEquals(
			'Modification date',
			$property->getCanonicalLabel()
		);

		$this->assertEquals(
			new DIWikiPage( 'Modification_date', SMW_NS_PROPERTY ),
			$property->getCanonicalDiWikiPage()
		);
	}

}

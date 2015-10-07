<?php

namespace SMW\Tests;

use SMW\DIProperty;

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

}

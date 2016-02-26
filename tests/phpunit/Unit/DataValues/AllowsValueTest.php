<?php

namespace SMW\Tests\DataValues;

use SMW\DataValues\AllowsValue;
use SMW\Tests\TestEnvironment;
use SMW\DataItemFactory;

/**
 * @covers \SMW\DataValues\AllowsValue
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.4
 *
 * @author mwjames
 */
class AllowsValueTest extends \PHPUnit_Framework_TestCase {

	private $testEnvironment;
	private $dataItemFactory;
	private $propertySpecificationLookup;

	protected function setUp() {
		$this->testEnvironment = new TestEnvironment();

		$this->dataItemFactory = new DataItemFactory();

		$this->propertySpecificationLookup = $this->getMockBuilder( '\SMW\PropertySpecificationLookup' )
			->disableOriginalConstructor()
			->getMock();

		$this->testEnvironment->registerObject( 'PropertySpecificationLookup', $this->propertySpecificationLookup );
	}

	protected function tearDown() {
		$this->testEnvironment->tearDown();
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			'\SMW\DataValues\AllowsValue',
			new AllowsValue()
		);
	}

	public function testGetAllowedValuesForProperty() {

		$this->propertySpecificationLookup->expects( $this->any() )
			->method( 'getAllowedValuesFor' )
			->will( $this->returnValue( array() ) );

		$property = $this->dataItemFactory->newDIProperty( 'Foo' );

		$instance = new AllowsValue();

		$this->assertInternalType(
			'array',
			$instance->getAllowedValuesFor( $property )
		);
	}

	public function testIsAllowedValueOnTheValidatedDataValue() {

		$this->propertySpecificationLookup->expects( $this->any() )
			->method( 'getAllowedValuesFor' )
			->will( $this->returnValue( array( $this->dataItemFactory->newDIBlob( 'Foo' ) ) ) );

		$dataValue = $this->getMockBuilder( '\SMWDataValue' )
			->disableOriginalConstructor()
			->setMethods( array( 'getProperty', 'getDataItem', 'getTypeID' ) )
			->getMockForAbstractClass();

		$dataValue->expects( $this->any() )
			->method( 'getTypeID' )
			->will( $this->returnValue( '_txt' ) );

		$dataValue->expects( $this->any() )
			->method( 'getProperty' )
			->will( $this->returnValue( $this->dataItemFactory->newDIProperty( 'Bar' ) ) );

		$dataValue->expects( $this->any() )
			->method( 'getDataItem' )
			->will( $this->returnValue( $this->dataItemFactory->newDIBlob( 'Foo' ) ) );

		$instance = new AllowsValue();

		$this->assertTrue(
			$instance->isAllowedValueFor( $dataValue )
		);

		$this->assertEmpty(
			$instance->getErrors()
		);
	}

	public function testIsNotAllowedValueOnTheValidatedDataValue() {

		$this->propertySpecificationLookup->expects( $this->any() )
			->method( 'getAllowedValuesFor' )
			->will( $this->returnValue( array( $this->dataItemFactory->newDIBlob( 'NOTALLOWED' ) ) ) );

		$dataValue = $this->getMockBuilder( '\SMWDataValue' )
			->disableOriginalConstructor()
			->setMethods( array( 'getProperty', 'getDataItem', 'getTypeID' ) )
			->getMockForAbstractClass();

		$dataValue->expects( $this->any() )
			->method( 'getTypeID' )
			->will( $this->returnValue( '_txt' ) );

		$dataValue->expects( $this->any() )
			->method( 'getProperty' )
			->will( $this->returnValue( $this->dataItemFactory->newDIProperty( 'Bar' ) ) );

		$dataValue->expects( $this->any() )
			->method( 'getDataItem' )
			->will( $this->returnValue( $this->dataItemFactory->newDIBlob( 'Foo' ) ) );

		$instance = new AllowsValue();

		$this->assertFalse(
			$instance->isAllowedValueFor( $dataValue )
		);

		$this->assertNotEmpty(
			$instance->getErrors()
		);
	}

}

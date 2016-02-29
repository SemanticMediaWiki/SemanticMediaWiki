<?php

namespace SMW\Tests\DataValues;

use SMW\DataValues\AllowsListValue;
use SMW\Tests\TestEnvironment;
use SMW\DataItemFactory;

/**
 * @covers \SMW\DataValues\AllowsListValue
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.4
 *
 * @author mwjames
 */
class AllowsListValueTest extends \PHPUnit_Framework_TestCase {

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
			'\SMW\DataValues\AllowsListValue',
			new AllowsListValue()
		);
	}

	public function testIsAllowedValueOnTheValidatedDataValue() {

		$property = $this->dataItemFactory->newDIProperty( 'ValidAllowedValue' );

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
			->will( $this->returnValue( $property ) );

		$dataValue->expects( $this->any() )
			->method( 'getDataItem' )
			->will( $this->returnValue( $this->dataItemFactory->newDIBlob( 'Foo' ) ) );

		$instance = new AllowsListValue();

		$this->assertTrue(
			$instance->doCheckAllowedValuesFor( $dataValue )
		);

		$this->assertEmpty(
			$instance->getErrors()
		);
	}

	public function testIsNotAllowedValueOnTheValidatedDataValue() {

		$property = $this->dataItemFactory->newDIProperty( 'InvalidAllowedValue' );

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
			->will( $this->returnValue( $property ) );

		$dataValue->expects( $this->any() )
			->method( 'getDataItem' )
			->will( $this->returnValue( $this->dataItemFactory->newDIBlob( 'Foo' ) ) );

		$instance = new AllowsListValue();

		$this->assertFalse(
			$instance->doCheckAllowedValuesFor( $dataValue )
		);

		$this->assertNotEmpty(
			$instance->getErrors()
		);
	}

}

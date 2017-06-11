<?php

namespace SMW\Tests\DataValues\ValueValidators;

use SMW\DataItemFactory;
use SMW\DataValues\ValueValidators\AllowsListConstraintValueValidator;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\DataValues\ValueValidators\AllowsListConstraintValueValidator
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.4
 *
 * @author mwjames
 */
class AllowsListConstraintValueValidatorTest extends \PHPUnit_Framework_TestCase {

	private $testEnvironment;
	private $dataItemFactory;
	private $propertySpecificationLookup;
	private $allowsListValueParser;

	protected function setUp() {
		$this->testEnvironment = new TestEnvironment();
		$this->dataItemFactory = new DataItemFactory();

		$this->allowsListValueParser = $this->getMockBuilder( '\SMW\DataValues\ValueParsers\AllowsListValueParser' )
			->disableOriginalConstructor()
			->getMock();

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
			'\SMW\DataValues\ValueValidators\AllowsListConstraintValueValidator',
			new AllowsListConstraintValueValidator( $this->allowsListValueParser )
		);
	}

	public function testIsAllowedValueOnTheValidatedDataValue() {

		$property = $this->dataItemFactory->newDIProperty( 'ValidAllowedValue' );

		$this->propertySpecificationLookup->expects( $this->any() )
			->method( 'getAllowedValuesBy' )
			->will( $this->returnValue( array( $this->dataItemFactory->newDIBlob( 'Foo' ) ) ) );

		$this->propertySpecificationLookup->expects( $this->any() )
			->method( 'getAllowedListValueBy' )
			->will( $this->returnValue( array() ) );

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

		$instance = new AllowsListConstraintValueValidator(
			$this->allowsListValueParser
		);

		$instance->validate( $dataValue );

		$this->assertFalse(
			$instance->hasConstraintViolation()
		);
	}

	public function testIsNotAllowedValueOnTheValidatedDataValue() {

		$property = $this->dataItemFactory->newDIProperty( 'InvalidAllowedValue' );

		$this->propertySpecificationLookup->expects( $this->any() )
			->method( 'getAllowedValuesBy' )
			->will( $this->returnValue( array( $this->dataItemFactory->newDIBlob( 'NOTALLOWED' ) ) ) );

		$this->propertySpecificationLookup->expects( $this->any() )
			->method( 'getAllowedListValueBy' )
			->will( $this->returnValue( array() ) );

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

		$instance = new AllowsListConstraintValueValidator(
			$this->allowsListValueParser
		);

		$instance->validate( $dataValue );

		$this->assertTrue(
			$instance->hasConstraintViolation()
		);
	}

	public function testIsNotAllowedValueWithShortenedLongList() {

		$property = $this->dataItemFactory->newDIProperty( 'InvalidAllowedValue' );

		$this->propertySpecificationLookup->expects( $this->any() )
			->method( 'getAllowedValuesBy' )
			->will( $this->returnValue(
				array(
					$this->dataItemFactory->newDIBlob( 'VAL1' ),
					$this->dataItemFactory->newDIBlob( 'VAL2' ),
					$this->dataItemFactory->newDIBlob( 'VAL2' ),
					$this->dataItemFactory->newDIBlob( 'VAL3' ),
					$this->dataItemFactory->newDIBlob( 'VAL4' ),
					$this->dataItemFactory->newDIBlob( 'VAL5' ),
					$this->dataItemFactory->newDIBlob( 'VAL6' ),
					$this->dataItemFactory->newDIBlob( 'VAL7' ),
					$this->dataItemFactory->newDIBlob( 'VAL8' ),
					$this->dataItemFactory->newDIBlob( 'VAL9' ),
					$this->dataItemFactory->newDIBlob( 'VAL0' ),
					$this->dataItemFactory->newDIBlob( 'VAL11' ) ) ) );

		$this->propertySpecificationLookup->expects( $this->any() )
			->method( 'getAllowedListValueBy' )
			->will( $this->returnValue( array( ) ) );

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

		$instance = new AllowsListConstraintValueValidator(
			$this->allowsListValueParser
		);

		$instance->validate( $dataValue );

		$this->assertEquals(
			array(
				'2da6400856e4455038d21793670ff9f7' => '[8,"smw_notinenum","","VAL1, VAL2, VAL3, VAL4, VAL5, VAL6, VAL7, VAL8, VAL9, VAL0, ...","InvalidAllowedValue"]'
			),
			$dataValue->getErrors()
		);
	}

}

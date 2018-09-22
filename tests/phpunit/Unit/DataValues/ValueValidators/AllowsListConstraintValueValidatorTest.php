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
	}

	protected function tearDown() {
		$this->testEnvironment->tearDown();
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			AllowsListConstraintValueValidator::class,
			new AllowsListConstraintValueValidator( $this->allowsListValueParser, $this->propertySpecificationLookup )
		);
	}

	public function testIsAllowedValueOnTheValidatedDataValue() {

		$property = $this->dataItemFactory->newDIProperty( 'ValidAllowedValue' );

		$this->propertySpecificationLookup->expects( $this->any() )
			->method( 'getAllowedValues' )
			->will( $this->returnValue( [ $this->dataItemFactory->newDIBlob( 'Foo' ) ] ) );

		$this->propertySpecificationLookup->expects( $this->any() )
			->method( 'getAllowedListValues' )
			->will( $this->returnValue( [] ) );

		$dataValue = $this->getMockBuilder( '\SMWDataValue' )
			->disableOriginalConstructor()
			->setMethods( [ 'getProperty', 'getDataItem', 'getTypeID' ] )
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
			$this->allowsListValueParser,
			$this->propertySpecificationLookup
		);

		$instance->validate( $dataValue );

		$this->assertFalse(
			$instance->hasConstraintViolation()
		);
	}

	public function testIsNotAllowedValueOnTheValidatedDataValue() {

		$property = $this->dataItemFactory->newDIProperty( 'InvalidAllowedValue' );

		$this->propertySpecificationLookup->expects( $this->any() )
			->method( 'getAllowedValues' )
			->will( $this->returnValue( [ $this->dataItemFactory->newDIBlob( 'NOTALLOWED' ) ] ) );

		$this->propertySpecificationLookup->expects( $this->any() )
			->method( 'getAllowedListValues' )
			->will( $this->returnValue( [] ) );

		$dataValue = $this->getMockBuilder( '\SMWDataValue' )
			->disableOriginalConstructor()
			->setMethods( [ 'getProperty', 'getDataItem', 'getTypeID' ] )
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
			$this->allowsListValueParser,
			$this->propertySpecificationLookup
		);

		$instance->validate( $dataValue );

		$this->assertTrue(
			$instance->hasConstraintViolation()
		);
	}

	public function testIsNotAllowedValueWithShortenedLongList() {

		$property = $this->dataItemFactory->newDIProperty( 'InvalidAllowedValue' );

		$this->propertySpecificationLookup->expects( $this->any() )
			->method( 'getAllowedValues' )
			->will( $this->returnValue(
				[
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
					$this->dataItemFactory->newDIBlob( 'VAL11' ) ] ) );

		$this->propertySpecificationLookup->expects( $this->any() )
			->method( 'getAllowedListValues' )
			->will( $this->returnValue( [ ] ) );

		$dataValue = $this->getMockBuilder( '\SMWDataValue' )
			->disableOriginalConstructor()
			->setMethods( [ 'getProperty', 'getDataItem', 'getTypeID' ] )
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
			$this->allowsListValueParser,
			$this->propertySpecificationLookup
		);

		$instance->validate( $dataValue );

		$this->assertEquals(
			[
				'a3cb94437b0f3619eaebd527123a4558' => '[8,"smw-datavalue-constraint-error-allows-value-list","","VAL1, VAL2, VAL3, VAL4, VAL5, VAL6, VAL7, VAL8, VAL9, VAL0, ...","InvalidAllowedValue"]'
			],
			$dataValue->getErrors()
		);
	}

	public function testIsAllowedValueFromCombinedList() {

		$property = $this->dataItemFactory->newDIProperty( 'ValidAllowedValue' );

		$this->allowsListValueParser->expects( $this->any() )
			->method( 'parse' )
			->will( $this->onConsecutiveCalls( [ 'Foo' => 'foo' ], [ 'Bar' => 'bar' ] ) );

		$this->propertySpecificationLookup->expects( $this->any() )
			->method( 'getAllowedValues' )
			->will( $this->returnValue( [] ) );

		$this->propertySpecificationLookup->expects( $this->any() )
			->method( 'getAllowedListValues' )
			->will( $this->returnValue( [
				$this->dataItemFactory->newDIBlob( 'list_foo' ),
				$this->dataItemFactory->newDIBlob( 'list_bar' ) ] ) );

		$dataValue = $this->getMockBuilder( '\SMWDataValue' )
			->disableOriginalConstructor()
			->setMethods( [ 'getProperty', 'getDataItem', 'getTypeID' ] )
			->getMockForAbstractClass();

		$dataValue->expects( $this->any() )
			->method( 'getTypeID' )
			->will( $this->returnValue( '_txt' ) );

		$dataValue->expects( $this->any() )
			->method( 'getProperty' )
			->will( $this->returnValue( $property ) );

		$dataValue->expects( $this->any() )
			->method( 'getDataItem' )
			->will( $this->returnValue( $this->dataItemFactory->newDIBlob( 'Bar' ) ) );

		$instance = new AllowsListConstraintValueValidator(
			$this->allowsListValueParser,
			$this->propertySpecificationLookup
		);

		$instance->validate( $dataValue );

		$this->assertFalse(
			$instance->hasConstraintViolation()
		);
	}

	/**
	 * @dataProvider rangeProvider
	 */
	public function testCompareNumberRange( $allowsValue, $dataItem, $expected ) {

		$property = $this->dataItemFactory->newDIProperty( 'InvalidAllowedValue' );

		$this->propertySpecificationLookup->expects( $this->any() )
			->method( 'getAllowedValues' )
			->will( $this->returnValue( $allowsValue ) );

		$this->propertySpecificationLookup->expects( $this->any() )
			->method( 'getAllowedListValues' )
			->will( $this->returnValue( [ ] ) );

		$dataValue = $this->getMockBuilder( '\SMWDataValue' )
			->disableOriginalConstructor()
			->setMethods( [ 'getProperty', 'getDataItem', 'getTypeID', 'getWikiValue' ] )
			->getMockForAbstractClass();

		$dataValue->expects( $this->any() )
			->method( 'getTypeID' )
			->will( $this->returnValue( '_num' ) );

		$dataValue->expects( $this->any() )
			->method( 'getWikiValue' )
			->will( $this->returnValue( $dataItem->getNumber() ) );

		$dataValue->expects( $this->any() )
			->method( 'getProperty' )
			->will( $this->returnValue( $property ) );

		$dataValue->expects( $this->any() )
			->method( 'getDataItem' )
			->will( $this->returnValue( $dataItem ) );

		$instance = new AllowsListConstraintValueValidator(
			$this->allowsListValueParser,
			$this->propertySpecificationLookup
		);

		$instance->validate( $dataValue );

		$this->assertEquals(
			$expected,
			$dataValue->getErrors()
		);
	}

	public function rangeProvider() {

		$dataItemFactory = new DataItemFactory();

		// Combinations do test that the order for a range and a discrete value
		// doesn't matter

		// Range
		yield [
			[
				$dataItemFactory->newDIBlob( '>1' ),
				$dataItemFactory->newDIBlob( '<4' )
			],
			$dataItemFactory->newDINumber( 3 ),
			[]
		];

		yield [
			[
				$dataItemFactory->newDIBlob( '>1' ),
				$dataItemFactory->newDIBlob( '5' ),
				$dataItemFactory->newDIBlob( '<4' )
			],
			$dataItemFactory->newDINumber( 5 ),
			[]
		];

		yield [
			[
				$dataItemFactory->newDIBlob( '<4' ),
				$dataItemFactory->newDIBlob( '>1' ),
				$dataItemFactory->newDIBlob( '5' ),
			],
			$dataItemFactory->newDINumber( 5 ),
			[]
		];

		yield [
			[
				$dataItemFactory->newDIBlob( '5' ),
				$dataItemFactory->newDIBlob( '<4' ),
				$dataItemFactory->newDIBlob( '>1' ),
			],
			$dataItemFactory->newDINumber( 5 ),
			[]
		];

		yield [
			[
				$dataItemFactory->newDIBlob( '>1' ),
				$dataItemFactory->newDIBlob( '<4' )
			],
			$dataItemFactory->newDINumber( 5 ),
			[
				'9b7a08f3296c976852260443ff290b16' => '[8,"smw-datavalue-constraint-error-allows-value-range","5","<4","InvalidAllowedValue"]'
			]
		];

		yield [
			[
				$dataItemFactory->newDIBlob( '<4' ),
				$dataItemFactory->newDIBlob( '>1' )
			],
			$dataItemFactory->newDINumber( 5 ),
			[
				'28e3bb64ee1452b03e0e2afac787c010' => '[8,"smw-datavalue-constraint-error-allows-value-range","5","<4, >1","InvalidAllowedValue"]'
			]
		];

		// Bounds
		yield [
			[
				$dataItemFactory->newDIBlob( '1...10' )
			],
			$dataItemFactory->newDINumber( 5 ),
			[]
		];

		yield [
			[
				$dataItemFactory->newDIBlob( '1...10' )
			],
			$dataItemFactory->newDINumber( 10 ),
			[]
		];

		yield [
			[
				$dataItemFactory->newDIBlob( '1...10' ),
				$dataItemFactory->newDIBlob( '15' )
			],
			$dataItemFactory->newDINumber( 15 ),
			[]
		];

		yield [
			[
				$dataItemFactory->newDIBlob( '1...10' ),
				$dataItemFactory->newDIBlob( '50...100' )
			],
			$dataItemFactory->newDINumber( 100 ),
			[]
		];

		yield [
			[
				$dataItemFactory->newDIBlob( '<200' ),
				$dataItemFactory->newDIBlob( '1...10' ),
				$dataItemFactory->newDIBlob( '15' ),
				$dataItemFactory->newDIBlob( '>100' )
			],
			$dataItemFactory->newDINumber( 101 ),
			[]
		];

		yield [
			[
				$dataItemFactory->newDIBlob( '1...10' )
			],
			$dataItemFactory->newDINumber( 15 ),
			[
				'437bd520d612acf0ccb7f460c2784275' => '[8,"smw-datavalue-constraint-error-allows-value-range","15","1...10","InvalidAllowedValue"]'
			]
		];
	}

}

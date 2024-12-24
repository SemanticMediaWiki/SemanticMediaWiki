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
class AllowsListConstraintValueValidatorTest extends \PHPUnit\Framework\TestCase {

	private $testEnvironment;
	private $dataItemFactory;
	private $propertySpecificationLookup;
	private $allowsListValueParser;

	protected function setUp(): void {
		$this->testEnvironment = new TestEnvironment();
		$this->dataItemFactory = new DataItemFactory();

		$this->allowsListValueParser = $this->getMockBuilder( '\SMW\DataValues\ValueParsers\AllowsListValueParser' )
			->disableOriginalConstructor()
			->getMock();

		$this->propertySpecificationLookup = $this->getMockBuilder( '\SMW\PropertySpecificationLookup' )
			->disableOriginalConstructor()
			->getMock();
	}

	protected function tearDown(): void {
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
			->willReturn( [ $this->dataItemFactory->newDIBlob( 'Foo' ) ] );

		$this->propertySpecificationLookup->expects( $this->any() )
			->method( 'getAllowedListValues' )
			->willReturn( [] );

		$dataValue = $this->getMockBuilder( '\SMWDataValue' )
			->disableOriginalConstructor()
			->onlyMethods( [ 'getProperty', 'getDataItem', 'getTypeID' ] )
			->getMockForAbstractClass();

		$dataValue->expects( $this->any() )
			->method( 'getTypeID' )
			->willReturn( '_txt' );

		$dataValue->expects( $this->any() )
			->method( 'getProperty' )
			->willReturn( $property );

		$dataValue->expects( $this->any() )
			->method( 'getDataItem' )
			->willReturn( $this->dataItemFactory->newDIBlob( 'Foo' ) );

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
			->willReturn( [ $this->dataItemFactory->newDIBlob( 'NOTALLOWED' ) ] );

		$this->propertySpecificationLookup->expects( $this->any() )
			->method( 'getAllowedListValues' )
			->willReturn( [] );

		$dataValue = $this->getMockBuilder( '\SMWDataValue' )
			->disableOriginalConstructor()
			->onlyMethods( [ 'getProperty', 'getDataItem', 'getTypeID' ] )
			->getMockForAbstractClass();

		$dataValue->expects( $this->any() )
			->method( 'getTypeID' )
			->willReturn( '_txt' );

		$dataValue->expects( $this->any() )
			->method( 'getProperty' )
			->willReturn( $property );

		$dataValue->expects( $this->any() )
			->method( 'getDataItem' )
			->willReturn( $this->dataItemFactory->newDIBlob( 'Foo' ) );

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
			->willReturn(
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
					$this->dataItemFactory->newDIBlob( 'VAL11' ) ] );

		$this->propertySpecificationLookup->expects( $this->any() )
			->method( 'getAllowedListValues' )
			->willReturn( [] );

		$dataValue = $this->getMockBuilder( '\SMWDataValue' )
			->disableOriginalConstructor()
			->onlyMethods( [ 'getProperty', 'getDataItem', 'getTypeID' ] )
			->getMockForAbstractClass();

		$dataValue->expects( $this->any() )
			->method( 'getTypeID' )
			->willReturn( '_txt' );

		$dataValue->expects( $this->any() )
			->method( 'getProperty' )
			->willReturn( $property );

		$dataValue->expects( $this->any() )
			->method( 'getDataItem' )
			->willReturn( $this->dataItemFactory->newDIBlob( 'Foo' ) );

		$instance = new AllowsListConstraintValueValidator(
			$this->allowsListValueParser,
			$this->propertySpecificationLookup
		);

		$instance->validate( $dataValue );

		$this->assertEquals(
			[
				'f36bb09e4f4a0e9b0a6f8bb437d61556' => '[8,"smw-constraint-error-allows-value-list","","VAL1, VAL2, VAL3, VAL4, VAL5, VAL6, VAL7, VAL8, VAL9, VAL0, ...","InvalidAllowedValue"]'
			],
			$dataValue->getErrors()
		);
	}

	public function testIsAllowedValueFromCombinedList() {
		$property = $this->dataItemFactory->newDIProperty( 'ValidAllowedValue' );

		$this->allowsListValueParser->expects( $this->any() )
			->method( 'parse' )
			->willReturnOnConsecutiveCalls( [ 'Foo' => 'foo' ], [ 'Bar' => 'bar' ] );

		$this->propertySpecificationLookup->expects( $this->any() )
			->method( 'getAllowedValues' )
			->willReturn( [] );

		$this->propertySpecificationLookup->expects( $this->any() )
			->method( 'getAllowedListValues' )
			->willReturn( [
				$this->dataItemFactory->newDIBlob( 'list_foo' ),
				$this->dataItemFactory->newDIBlob( 'list_bar' ) ] );

		$dataValue = $this->getMockBuilder( '\SMWDataValue' )
			->disableOriginalConstructor()
			->onlyMethods( [ 'getProperty', 'getDataItem', 'getTypeID' ] )
			->getMockForAbstractClass();

		$dataValue->expects( $this->any() )
			->method( 'getTypeID' )
			->willReturn( '_txt' );

		$dataValue->expects( $this->any() )
			->method( 'getProperty' )
			->willReturn( $property );

		$dataValue->expects( $this->any() )
			->method( 'getDataItem' )
			->willReturn( $this->dataItemFactory->newDIBlob( 'Bar' ) );

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
			->willReturn( $allowsValue );

		$this->propertySpecificationLookup->expects( $this->any() )
			->method( 'getAllowedListValues' )
			->willReturn( [] );

		$dataValue = $this->getMockBuilder( '\SMWDataValue' )
			->disableOriginalConstructor()
			->onlyMethods( [ 'getProperty', 'getDataItem', 'getTypeID', 'getWikiValue' ] )
			->getMockForAbstractClass();

		$dataValue->expects( $this->any() )
			->method( 'getTypeID' )
			->willReturn( '_num' );

		$dataValue->expects( $this->any() )
			->method( 'getWikiValue' )
			->willReturn( $dataItem->getNumber() );

		$dataValue->expects( $this->any() )
			->method( 'getProperty' )
			->willReturn( $property );

		$dataValue->expects( $this->any() )
			->method( 'getDataItem' )
			->willReturn( $dataItem );

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
				'e7690021e785b6d8d4134876eb5a8f77' => '[8,"smw-constraint-error-allows-value-range","5","<4","InvalidAllowedValue"]'
			]
		];

		yield [
			[
				$dataItemFactory->newDIBlob( '<4' ),
				$dataItemFactory->newDIBlob( '>1' )
			],
			$dataItemFactory->newDINumber( 5 ),
			[
				'b4fd5d4a6e16686601b355e61cf99e60' => '[8,"smw-constraint-error-allows-value-range","5","<4, >1","InvalidAllowedValue"]'
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
				'cf3a2d2e576e418b1a9e6a215eb567db' => '[8,"smw-constraint-error-allows-value-range","15","1...10","InvalidAllowedValue"]'
			]
		];
	}

}

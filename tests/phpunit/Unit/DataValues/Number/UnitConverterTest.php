<?php

namespace SMW\Tests\DataValues\Number;

use SMW\DataItemFactory;
use SMW\DataValues\Number\UnitConverter;
use SMW\Tests\TestEnvironment;
use SMWNumberValue as NumberValue;

/**
 * @covers \SMW\DataValues\Number\UnitConverter
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.4
 *
 * @author mwjames
 */
class UnitConverterTest extends \PHPUnit_Framework_TestCase {

	private $testEnvironment;
	private $dataItemFactory;
	private $propertySpecificationLookup;
	private $entityCache;

	protected function setUp() {
		$this->testEnvironment = new TestEnvironment();
		$this->dataItemFactory = new DataItemFactory();

		$this->propertySpecificationLookup = $this->getMockBuilder( '\SMW\PropertySpecificationLookup' )
			->disableOriginalConstructor()
			->getMock();

		$this->entityCache = $this->getMockBuilder( '\SMW\EntityCache' )
			->disableOriginalConstructor()
			->setMethods( [ 'fetch', 'save', 'associate' ] )
			->getMock();
	}

	protected function tearDown() {
		$this->testEnvironment->tearDown();
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			UnitConverter::class,
			new UnitConverter( $this->propertySpecificationLookup, $this->entityCache )
		);
	}

	public function testErrorOnMissingConversionData() {

		$property = $this->dataItemFactory->newDIProperty( 'Foo' );

		$numberValue = $this->getMockBuilder( '\SMWNumberValue' )
			->disableOriginalConstructor()
			->getMock();

		$numberValue->expects( $this->any() )
			->method( 'getProperty' )
			->will( $this->returnValue( $property ) );

		$instance = new UnitConverter(
			$this->propertySpecificationLookup,
			$this->entityCache
		);

		$instance->fetchConversionData(
			$numberValue
		);

		$this->assertNotEmpty(
			$instance->getErrors()
		);
	}

	/**
	 * @dataProvider conversionDataProvider
	 */
	public function testFetchConversionData( $thousands, $decimal, $correspondsTo, $unitIds, $unitFactors, $mainUnit, $prefixalUnitPreference ) {

		$property = $this->dataItemFactory->newDIProperty( 'Foo' );

		$this->propertySpecificationLookup->expects( $this->once() )
			->method( 'getSpecification' )
			->will( $this->returnValue( [ $this->dataItemFactory->newDIBlob( $correspondsTo ) ] ) );

		$numberValue = new NumberValue();
		$numberValue->setProperty( $property );

		$numberValue->setOption(
			NumberValue::THOUSANDS_SEPARATOR,
			$thousands
		);

		$numberValue->setOption(
			NumberValue::DECIMAL_SEPARATOR,
			$decimal
		);

		$instance = new UnitConverter(
			$this->propertySpecificationLookup,
			$this->entityCache
		);

		$instance->fetchConversionData(
			$numberValue
		);

		$this->assertEmpty(
			$instance->getErrors()
		);

		$this->assertEquals(
			$unitIds,
			$instance->getUnitIds()
		);

		$this->assertEquals(
			$unitFactors,
			$instance->getUnitFactors()
		);

		$this->assertEquals(
			$mainUnit,
			$instance->getMainUnit()
		);

		$this->assertEquals(
			$prefixalUnitPreference,
			$instance->getPrefixalUnitPreference()
		);
	}

	public function testLoadConversionData() {

		$property = $this->dataItemFactory->newDIProperty( 'Foo' );

		$data = [
			'ids' => '',
			'factors' => '',
			'main' => '',
			'prefix' => ''
		];

		$this->entityCache->expects( $this->atLeastOnce() )
			->method( 'fetch' )
			->will( $this->onConsecutiveCalls( false, $data ) );

		$this->entityCache->expects( $this->once() )
			->method( 'save' );

		$this->entityCache->expects( $this->once() )
			->method( 'associate' )
			->with( $this->equalTo( $property->getDiWikiPage() ) );

		$this->propertySpecificationLookup->expects( $this->once() )
			->method( 'getSpecification' )
			->will( $this->returnValue( [ $this->dataItemFactory->newDIBlob( 'Foo' ) ] ) );

		$numberValue = new NumberValue();
		$numberValue->setProperty( $property );

		$instance = new UnitConverter(
			$this->propertySpecificationLookup,
			$this->entityCache
		);

		$instance->loadConversionData( $numberValue );

		// Cached
		$instance->loadConversionData( $numberValue );
	}

	public function conversionDataProvider() {

		$provider[] = [
			',',
			'.',
			'¥,JPY,Japanese Yen 1.5',
			[
				'¥' => '¥',
				'JPY' => '¥',
				'JapaneseYen' => '¥',
				'' => ''
			],
			[
				'' => 1,
				'¥' => 1.5
			],
			'',
			[
				'¥' => true,
				'JPY' => true,
				'JapaneseYen' => true
			]
		];

		$provider[] = [
			',',
			'.',
			'0.5 British Pound, GBP, pounds sterling, £',
			[
				'BritishPound' => 'BritishPound',
				'GBP' => 'BritishPound',
				'poundssterling' => 'BritishPound',
				'£' => 'BritishPound',
				'' => ''
			],
			[
				'' => 1,
				'BritishPound' => 0.5
			],
			'',
			[
				'BritishPound' => false,
				'GBP' => false,
				'poundssterling' => false,
				'£' => false
			]
		];

		$provider[] = [
			'.',
			',',
			'0,5 thousand rub., thousand ₽',
			[
				'thousandrub.' => 'thousandrub.',
				'thousand₽' => 'thousandrub.',
				'' => ''
			],
			[
				'thousandrub.' => 0.5,
				'' => 1
			],
			'',
			[
				'thousandrub.' => false,
				'thousand₽' => false
			]
		];

		$provider[] = [
			'.',
			',',
			'€ 1',
			[
				'€' => '€',
				'' => ''
			],
			[
				'€' => 1,
				'' => 1
			],
			'€',
			[
				'€' => true,
			]
		];

		return $provider;
	}

}

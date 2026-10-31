<?php

namespace SMW\Tests\Unit\DataValues\Number;

use PHPUnit\Framework\TestCase;
use SMW\DataItemFactory;
use SMW\DataValues\Number\UnitConverter;
use SMW\DataValues\NumberValue;
use SMW\Property\SpecificationLookup;
use SMW\Tests\TestEnvironment;
use SMW\Tests\Utils\EntityCacheFactory;
use Wikimedia\ObjectCache\BagOStuff;
use Wikimedia\ObjectCache\HashBagOStuff;

/**
 * @covers \SMW\DataValues\Number\UnitConverter
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 2.4
 *
 * @author mwjames
 */
class UnitConverterTest extends TestCase {

	private $testEnvironment;
	private $dataItemFactory;
	private $propertySpecificationLookup;
	private $entityCache;

	protected function setUp(): void {
		$this->testEnvironment = new TestEnvironment();
		$this->dataItemFactory = new DataItemFactory();

		$this->propertySpecificationLookup = $this->getMockBuilder( SpecificationLookup::class )
			->disableOriginalConstructor()
			->getMock();

		$this->entityCache = EntityCacheFactory::newEntityCache();
	}

	protected function tearDown(): void {
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

		$numberValue = $this->getMockBuilder( NumberValue::class )
			->disableOriginalConstructor()
			->getMock();

		$numberValue->expects( $this->any() )
			->method( 'getProperty' )
			->willReturn( $property );

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
			->willReturn( [ $this->dataItemFactory->newDIBlob( $correspondsTo ) ] );

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

	public function testLoadConversionDataReadsTheSpecificationOnlyOnce() {
		$property = $this->dataItemFactory->newDIProperty( 'Foo' );

		$this->propertySpecificationLookup->expects( $this->once() )
			->method( 'getSpecification' )
			->willReturn( [ $this->dataItemFactory->newDIBlob( '€ 1' ), $this->dataItemFactory->newDIBlob( '¥,JPY 1.5' ) ] );

		$numberValue = new NumberValue();
		$numberValue->setProperty( $property );

		( new UnitConverter( $this->propertySpecificationLookup, $this->entityCache ) )->loadConversionData( $numberValue );

		$instance = new UnitConverter(
			$this->propertySpecificationLookup,
			$this->entityCache
		);

		$instance->loadConversionData( $numberValue );

		$this->assertSame( '€', $instance->getMainUnit() );
		$this->assertSame( '¥', $instance->getUnitIds()['JPY'] );
		$this->assertSame( 1.5, $instance->getUnitFactors()['¥'] );
		$this->assertTrue( $instance->getPrefixalUnitPreference()['€'] );
	}

	public function testLoadConversionDataReportsAPropertyWithoutUnits() {
		$this->propertySpecificationLookup->expects( $this->any() )
			->method( 'getSpecification' )
			->willReturn( [] );

		$numberValue = new NumberValue();
		$numberValue->setProperty( $this->dataItemFactory->newDIProperty( 'Foo' ) );

		$instance = new UnitConverter(
			$this->propertySpecificationLookup,
			$this->entityCache
		);

		$instance->loadConversionData( $numberValue );

		$this->assertNotEmpty( $numberValue->getErrors() );
	}

	public function testUnitsAreCachedPerProperty() {
		$this->propertySpecificationLookup->expects( $this->exactly( 2 ) )
			->method( 'getSpecification' )
			->willReturnCallback( fn ( $subject ) => [
				$this->dataItemFactory->newDIBlob( $subject->getDBkey() === 'Foo' ? '€ 1' : '¥ 1' )
			] );

		$foo = new NumberValue();
		$foo->setProperty( $this->dataItemFactory->newDIProperty( 'Foo' ) );
		$bar = new NumberValue();
		$bar->setProperty( $this->dataItemFactory->newDIProperty( 'Bar' ) );

		$backend = new HashBagOStuff();
		$this->newUnitConverter( $backend )->loadConversionData( $foo );
		$this->newUnitConverter( $backend )->loadConversionData( $bar );
		$this->newUnitConverter( $backend )->loadConversionData( $foo );

		$instance = $this->newUnitConverter( $backend );
		$instance->loadConversionData( $bar );

		$this->assertArrayHasKey( '¥', $instance->getUnitFactors() );
	}

	public function testLoadingCachedUnitsClearsTheErrorsOfAnEarlierProperty() {
		$this->propertySpecificationLookup->expects( $this->any() )
			->method( 'getSpecification' )
			->willReturnCallback( fn ( $subject ) => $subject->getDBkey() === 'Foo' ? [ $this->dataItemFactory->newDIBlob( '€ 1' ) ] : [] );

		$foo = new NumberValue();
		$foo->setProperty( $this->dataItemFactory->newDIProperty( 'Foo' ) );
		$bar = new NumberValue();
		$bar->setProperty( $this->dataItemFactory->newDIProperty( 'Bar' ) );

		$instance = new UnitConverter( $this->propertySpecificationLookup, $this->entityCache );
		$instance->loadConversionData( $foo );
		$instance->loadConversionData( $bar );
		$instance->loadConversionData( $foo );

		$this->assertSame( [], $instance->getErrors() );
	}

	/**
	 * A converter of its own process, over a backend shared with other processes
	 */
	private function newUnitConverter( BagOStuff $backend ): UnitConverter {
		return new UnitConverter( $this->propertySpecificationLookup, EntityCacheFactory::newEntityCache( $backend ) );
	}

	public function testLoadConversionDataRetriesAfterAnError() {
		$property = $this->dataItemFactory->newDIProperty( 'Foo' );

		$this->propertySpecificationLookup->expects( $this->exactly( 2 ) )
			->method( 'getSpecification' )
			->willReturnOnConsecutiveCalls(
				[],
				[ $this->dataItemFactory->newDIBlob( '¥,JPY 1.5' ) ]
			);

		$numberValue = new NumberValue();
		$numberValue->setProperty( $property );

		$instance = new UnitConverter(
			$this->propertySpecificationLookup,
			$this->entityCache
		);

		$instance->loadConversionData( $numberValue );
		$instance->loadConversionData( $numberValue );

		$this->assertEquals(
			[ '' => 1, '¥' => 1.5 ],
			$instance->getUnitFactors()
		);
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

<?php

namespace SMW\Tests\DataValues;

use SMW\DataItemFactory;
use SMW\DataValues\UnitConversionFetcher;
use SMW\Tests\TestEnvironment;
use SMWNumberValue as NumberValue;

/**
 * @covers \SMW\DataValues\UnitConversionFetcher
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.4
 *
 * @author mwjames
 */
class UnitConversionFetcherTest extends \PHPUnit_Framework_TestCase {

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

		$numberValue = $this->getMockBuilder( '\SMWNumberValue' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			'\SMW\DataValues\UnitConversionFetcher',
			new UnitConversionFetcher( $numberValue )
		);
	}

	public function testErrorOnMissingConversionData() {

		$numberValue = $this->getMockBuilder( '\SMWNumberValue' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new UnitConversionFetcher(
			$numberValue
		);

		$instance->fetchConversionData(
			$this->dataItemFactory->newDIProperty( 'Foo' )
		);

		$this->assertNotEmpty(
			$instance->getErrors()
		);
	}

	/**
	 * @dataProvider conversionDataProvider
	 */
	public function testFetchConversionData( $thousands, $decimal, $correspondsTo, $unitIds, $unitFactors, $mainUnit, $prefixalUnitPreference ) {

		$cachedPropertyValuesPrefetcher = $this->getMockBuilder( '\SMW\CachedPropertyValuesPrefetcher' )
			->disableOriginalConstructor()
			->getMock();

		$cachedPropertyValuesPrefetcher->expects( $this->once() )
			->method( 'getPropertyValues' )
			->will( $this->returnValue( [
				$this->dataItemFactory->newDIBlob( $correspondsTo )
			] ) );

		$numberValue = new NumberValue();

		$numberValue->setOption(
			'separator.thousands',
			$thousands
		);

		$numberValue->setOption(
			'separator.decimal',
			$decimal
		);

		$instance = new UnitConversionFetcher(
			$numberValue,
			$cachedPropertyValuesPrefetcher
		);

		$instance->fetchConversionData(
			$this->dataItemFactory->newDIProperty( 'Foo' )
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

	public function testFetchCachedConversionData() {

		$container = $this->getMockBuilder( '\Onoi\BlobStore\Container' )
			->disableOriginalConstructor()
			->getMock();

		$container->expects( $this->any() )
			->method( 'has' )
			->will( $this->onConsecutiveCalls( false, true ) );

		$blobStore = $this->getMockBuilder( '\Onoi\BlobStore\BlobStore' )
			->disableOriginalConstructor()
			->getMock();

		$blobStore->expects( $this->exactly( 2 ) )
			->method( 'read' )
			->will( $this->returnValue( $container ) );

		$blobStore->expects( $this->once() )
			->method( 'save' );

		$cachedPropertyValuesPrefetcher = $this->getMockBuilder( '\SMW\CachedPropertyValuesPrefetcher' )
			->disableOriginalConstructor()
			->getMock();

		$cachedPropertyValuesPrefetcher->expects( $this->atLeastOnce() )
			->method( 'getBlobStore' )
			->will( $this->returnValue( $blobStore ) );

		$cachedPropertyValuesPrefetcher->expects( $this->once() )
			->method( 'getPropertyValues' )
			->will( $this->returnValue( [
				$this->dataItemFactory->newDIBlob( 'Foo' )
			] ) );

		$numberValue = new NumberValue();

		$instance = new UnitConversionFetcher(
			$numberValue,
			$cachedPropertyValuesPrefetcher
		);

		$property = $this->dataItemFactory->newDIProperty( 'Foo' );

		$instance->fetchCachedConversionData( $property );

		// Cached
		$instance->fetchCachedConversionData( $property);
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

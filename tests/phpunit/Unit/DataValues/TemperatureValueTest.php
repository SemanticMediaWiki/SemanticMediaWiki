<?php

namespace SMW\Tests\DataValues;

use SMW\DataItemFactory;
use SMW\DataValues\TemperatureValue;
use SMW\DataValues\ValueFormatters\NumberValueFormatter;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\DataValues\TemperatureValue
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.4
 *
 * @author mwjames
 */
class TemperatureValueTest extends \PHPUnit_Framework_TestCase {

	private $testEnvironment;
	private $dataItemFactory;
	private $propertySpecificationLookup;
	private $dataValueServiceFactory;

	protected function setUp() {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment();
		$this->dataItemFactory = new DataItemFactory();

		$this->propertySpecificationLookup = $this->getMockBuilder( '\SMW\PropertySpecificationLookup' )
			->disableOriginalConstructor()
			->getMock();

		$this->testEnvironment->registerObject( 'PropertySpecificationLookup', $this->propertySpecificationLookup );

		$constraintValueValidator = $this->getMockBuilder( '\SMW\DataValues\ValueValidators\ConstraintValueValidator' )
			->disableOriginalConstructor()
			->getMock();

		$this->dataValueServiceFactory = $this->getMockBuilder( '\SMW\Services\DataValueServiceFactory' )
			->disableOriginalConstructor()
			->getMock();

		$this->dataValueServiceFactory->expects( $this->any() )
			->method( 'getConstraintValueValidator' )
			->will( $this->returnValue( $constraintValueValidator ) );

		$this->dataValueServiceFactory->expects( $this->any() )
			->method( 'getPropertySpecificationLookup' )
			->will( $this->returnValue( $this->propertySpecificationLookup ) );
	}

	protected function tearDown() {
		$this->testEnvironment->tearDown();
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			'\SMW\DataValues\TemperatureValue',
			new TemperatureValue()
		);
	}

	public function testSetUserValueToReturnKelvinForAnyNonPreferredDisplayUnit() {

		$instance = new TemperatureValue();

		$numberValueFormatter = new NumberValueFormatter();
		$numberValueFormatter->setDataValue( $instance );

		$this->dataValueServiceFactory->expects( $this->any() )
			->method( 'getValueFormatter' )
			->will( $this->returnValue( $numberValueFormatter ) );

		$instance->setDataValueServiceFactory(
			$this->dataValueServiceFactory
		);

		$instance->setUserValue( '100 °C' );

		$this->assertContains(
			'373.15 K',
			$instance->getWikiValue()
		);

		$instance->setUserValue( '100 Fahrenheit' );

		$this->assertContains(
			'310.92777777778 K',
			$instance->getWikiValue()
		);

		$this->assertContains(
			'100 Fahrenheit',
			$instance->getShortWikiText()
		);
	}

	public function testSetUserValueOnUnknownUnit() {

		$instance = new TemperatureValue();

		$numberValueFormatter = new NumberValueFormatter();
		$numberValueFormatter->setDataValue( $instance );

		$this->dataValueServiceFactory->expects( $this->any() )
			->method( 'getValueFormatter' )
			->will( $this->returnValue( $numberValueFormatter ) );

		$instance->setDataValueServiceFactory(
			$this->dataValueServiceFactory
		);

		$instance->setUserValue( '100 Unknown' );

		$this->assertContains(
			'error',
			$instance->getWikiValue()
		);
	}

	public function testSetUserValueToReturnOnPreferredDisplayUnit() {

		$this->propertySpecificationLookup->expects( $this->once() )
			->method( 'getDisplayUnits' )
			->will( $this->returnValue( [ 'Celsius' ] ) );

		$instance = new TemperatureValue();

		$numberValueFormatter = new NumberValueFormatter();
		$numberValueFormatter->setDataValue( $instance );

		$this->dataValueServiceFactory->expects( $this->any() )
			->method( 'getValueFormatter' )
			->will( $this->returnValue( $numberValueFormatter ) );

		$instance->setDataValueServiceFactory(
			$this->dataValueServiceFactory
		);

		$instance->setProperty(
			$this->dataItemFactory->newDIProperty( 'Foo' )
		);

		$instance->setUserValue( '100 °C' );

		$this->assertContains(
			'373.15 K',
			$instance->getWikiValue()
		);

		$this->assertContains(
			'100 °C',
			$instance->getShortWikiText()
		);

		$this->assertContains(
			'100&#160;°C (373&#160;K, 212&#160;°F, 672&#160;°R)',
			$instance->getLongWikiText()
		);
	}

	public function testSetUserValueToReturnOnPreferredDisplayPrecision() {

		$this->propertySpecificationLookup->expects( $this->once() )
			->method( 'getDisplayPrecision' )
			->will( $this->returnValue( 0 ) );

		$instance = new TemperatureValue();

		$numberValueFormatter = new NumberValueFormatter();
		$numberValueFormatter->setDataValue( $instance );

		$this->dataValueServiceFactory->expects( $this->any() )
			->method( 'getValueFormatter' )
			->will( $this->returnValue( $numberValueFormatter ) );

		$instance->setDataValueServiceFactory(
			$this->dataValueServiceFactory
		);

		$instance->setProperty(
			$this->dataItemFactory->newDIProperty( 'Foo' )
		);

		$instance->setUserValue( '100 °C' );

		$this->assertContains(
			'373 K',
			$instance->getWikiValue()
		);

		$this->assertContains(
			'100 °C',
			$instance->getShortWikiText()
		);

		$this->assertContains(
			'373&#160;K (100&#160;°C, 212&#160;°F, 672&#160;°R)',
			$instance->getLongWikiText()
		);
	}

}

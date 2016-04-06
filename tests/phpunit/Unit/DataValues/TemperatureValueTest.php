<?php

namespace SMW\Tests\DataValues;

use SMW\ApplicationFactory;
use SMW\DataValues\TemperatureValue;
use SMW\DIProperty;

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

	public function testCanConstruct() {

		$this->assertInstanceOf(
			'\SMW\DataValues\TemperatureValue',
			new TemperatureValue()
		);
	}

	public function testSetUserValueToReturnKelvinForAnyNonPreferredDisplayUnit() {

		$instance = new TemperatureValue();
		$instance->setUserValue( '100 °C' );

		$this->assertContains(
			'373.15 K',
			$instance->getWikiValue()
		);

		$instance->setUserValue( '100 Fahrenheit' );

		$this->assertContains(
			'310.928 K',
			$instance->getWikiValue()
		);

		$this->assertContains(
			'100 Fahrenheit',
			$instance->getShortWikiText()
		);
	}

	public function testSetUserValueOnUnknownUnit() {

		$instance = new TemperatureValue();
		$instance->setUserValue( '100 Unknown' );

		$this->assertContains(
			'error',
			$instance->getWikiValue()
		);
	}

	public function testSetUserValueToReturnOnPreferredDisplayUnit() {

		$propertySpecificationLookup = $this->getMockBuilder( '\SMW\PropertySpecificationLookup' )
			->disableOriginalConstructor()
			->getMock();

		$propertySpecificationLookup->expects( $this->once() )
			->method( 'getDisplayUnitsFor' )
			->will( $this->returnValue( array( 'Celsius' ) ) );

		ApplicationFactory::getInstance()->registerObject( 'PropertySpecificationLookup', $propertySpecificationLookup );

		$instance = new TemperatureValue();
		$instance->setProperty( new DIProperty( 'Foo' ) );
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
			'100&#160;°C (373&#160;K, 212&#160;°F, 672&#160;°R)',
			$instance->getLongWikiText()
		);

		ApplicationFactory::getInstance()->clear();
	}

}

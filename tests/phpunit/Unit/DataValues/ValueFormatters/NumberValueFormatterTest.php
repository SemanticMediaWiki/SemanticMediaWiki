<?php

namespace SMW\Tests\DataValues\ValueFormatters;

use SMW\DataValues\TemperatureValue;
use SMW\DataValues\ValueFormatters\NumberValueFormatter;
use SMWNumberValue as NumberValue;

/**
 * @covers \SMW\DataValues\ValueFormatters\NumberValueFormatter
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.4
 *
 * @author mwjames
 */
class NumberValueFormatterTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$this->assertInstanceOf(
			'\SMW\DataValues\ValueFormatters\NumberValueFormatter',
			new NumberValueFormatter()
		);
	}

	public function testIsFormatterForValidation() {

		$numberValue = $this->getMockBuilder( '\SMWNumberValue' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new NumberValueFormatter();

		$this->assertTrue(
			$instance->isFormatterFor( $numberValue )
		);
	}

	/**
	 * @dataProvider numberValueProvider
	 */
	public function testNumberFormat( $numberUserValue, $type, $linker, $expected ) {

		$numberValue = new NumberValue( '_num' );
		$numberValue->setUserValue( $numberUserValue );

		$numberValue->setOption( 'user.language', 'en' );
		$numberValue->setOption( 'content.language', 'en' );

		$instance = new NumberValueFormatter( $numberValue );

		$this->assertEquals(
			$expected,
			$instance->format( $type, $linker )
		);
	}

	/**
	 * @dataProvider temperaturValueProvider
	 */
	public function testTemperaturFormat( $numberUserValue, $type, $linker, $expected ) {

		$temperatureValue = new TemperatureValue( '_num' );
		$temperatureValue->setUserValue( $numberUserValue );

		$temperatureValue->setOption( 'user.language', 'en' );
		$temperatureValue->setOption( 'content.language', 'en' );

		$instance = new NumberValueFormatter( $temperatureValue );

		$this->assertEquals(
			$expected,
			$instance->format( $type, $linker )
		);
	}

	public function testTryToFormatOnMissingDataValueThrowsException() {

		$instance = new NumberValueFormatter();

		$this->setExpectedException( 'RuntimeException' );
		$instance->format( NumberValueFormatter::VALUE );
	}

	public function testTryToFormatWithUnknownType() {

		$numberValue = $this->getMockBuilder( '\SMWNumberValue' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new NumberValueFormatter( $numberValue );

		$this->assertEquals(
			'UNKNOWN',
			$instance->format( 'Foo' )
		);
	}

	public function numberValueProvider() {

		$provider['v.1'] = array(
			'foo',
			NumberValueFormatter::VALUE,
			null,
			'error'
		);

		$provider['v.2'] = array(
			100,
			NumberValueFormatter::VALUE,
			null,
			100
		);

		$provider['v.3'] = array(
			0.222,
			NumberValueFormatter::VALUE,
			null,
			0.222
		);

		$provider['ws.1'] = array(
			100,
			NumberValueFormatter::WIKI_SHORT,
			null,
			100
		);

		$provider['ws.2'] = array(
			100,
			NumberValueFormatter::WIKI_SHORT,
			'notNull',
			100
		);

		$provider['hs.1'] = array(
			100,
			NumberValueFormatter::HTML_SHORT,
			null,
			100
		);

		$provider['wl.1'] = array(
			100,
			NumberValueFormatter::WIKI_LONG,
			null,
			100
		);

		$provider['wl.2'] = array(
			100,
			NumberValueFormatter::WIKI_LONG,
			'notNull',
			100
		);

		$provider['hl.1'] = array(
			100,
			NumberValueFormatter::HTML_LONG,
			null,
			100
		);

		return $provider;
	}

	public function temperaturValueProvider() {

		$provider['v.1'] = array(
			'100 K',
			NumberValueFormatter::VALUE,
			null,
			'100 K'
		);

		$provider['ws.1'] = array(
			'100 K',
			NumberValueFormatter::WIKI_SHORT,
			null,
			'100 K'
		);

		$provider['ws.2'] = array(
			'100 K',
			NumberValueFormatter::WIKI_SHORT,
			'notNull',
			'<span class="smw-highlighter" data-type="3" data-state="inline" data-title="Unit conversion"><span class="smwtext">100 K</span><div class="smwttcontent">-173.15&#160;°C <br />-279.67&#160;°F <br />180&#160;°R <br /></div></span>'
		);

		$provider['wl.1'] = array(
			'100 K',
			NumberValueFormatter::WIKI_LONG,
			null,
			'100&#160;K (-173.15&#160;°C, -279.67&#160;°F, 180&#160;°R)'
		);

		$provider['wl.2'] = array(
			'100 K',
			NumberValueFormatter::WIKI_LONG,
			'notNull',
			'100&#160;K (-173.15&#160;°C, -279.67&#160;°F, 180&#160;°R)'
		);

		return $provider;
	}


}

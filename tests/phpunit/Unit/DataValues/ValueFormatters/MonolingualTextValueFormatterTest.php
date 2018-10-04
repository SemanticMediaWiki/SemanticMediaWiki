<?php

namespace SMW\Tests\DataValues\ValueFormatters;

use SMW\DataValues\MonolingualTextValue;
use SMW\DataValues\ValueFormatters\MonolingualTextValueFormatter;
use SMW\DataValues\ValueParsers\MonolingualTextValueParser;
use SMW\Tests\PHPUnitCompat;

/**
 * @covers \SMW\DataValues\ValueFormatters\MonolingualTextValueFormatter
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.4
 *
 * @author mwjames
 */
class MonolingualTextValueFormatterTest extends \PHPUnit_Framework_TestCase {

	use PHPUnitCompat;

	private $dataValueServiceFactory;

	protected function setUp() {
		parent::setUp();

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
			->method( 'getValueParser' )
			->will( $this->returnValue( new MonolingualTextValueParser() ) );
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			'\SMW\DataValues\ValueFormatters\MonolingualTextValueFormatter',
			new MonolingualTextValueFormatter()
		);
	}

	public function testIsFormatterForValidation() {

		$monolingualTextValue = $this->getMockBuilder( '\SMW\DataValues\MonolingualTextValue' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new MonolingualTextValueFormatter();

		$this->assertTrue(
			$instance->isFormatterFor( $monolingualTextValue )
		);
	}

	public function testToUseCaptionOutput() {

		$monolingualTextValue = new MonolingualTextValue();

		$monolingualTextValue->setDataValueServiceFactory(
			$this->dataValueServiceFactory
		);

		$monolingualTextValue->setCaption( 'ABC' );

		$instance = new MonolingualTextValueFormatter( $monolingualTextValue );

		$this->assertEquals(
			'ABC',
			$instance->format( MonolingualTextValueFormatter::WIKI_SHORT )
		);

		$this->assertEquals(
			'ABC',
			$instance->format( MonolingualTextValueFormatter::HTML_SHORT )
		);
	}

	/**
	 * @dataProvider stringValueProvider
	 */
	public function testFormat( $stringValue, $type, $linker, $expected ) {

		$monolingualTextValue = new MonolingualTextValue();

		$monolingualTextValue->setDataValueServiceFactory(
			$this->dataValueServiceFactory
		);

		$monolingualTextValue->setUserValue( $stringValue );

		$instance = new MonolingualTextValueFormatter( $monolingualTextValue );

		$this->assertEquals(
			$expected,
			$instance->format( $type, $linker )
		);
	}

	public function testTryToFormatOnMissingDataValueThrowsException() {

		$instance = new MonolingualTextValueFormatter();

		$this->setExpectedException( 'RuntimeException' );
		$instance->format( MonolingualTextValueFormatter::VALUE );
	}

	public function stringValueProvider() {

		$provider[] = [
			'foo@en',
			MonolingualTextValueFormatter::VALUE,
			null,
			'foo@en'
		];

		$provider[] = [
			'foo@en',
			MonolingualTextValueFormatter::WIKI_SHORT,
			null,
			'foo (en)'
		];

		$provider[] = [
			'foo@en',
			MonolingualTextValueFormatter::HTML_SHORT,
			null,
			'foo (en)'
		];

		$provider[] = [
			'foo@en',
			MonolingualTextValueFormatter::WIKI_LONG,
			null,
			'foo (en)'
		];

		$provider[] = [
			'foo@en',
			MonolingualTextValueFormatter::HTML_LONG,
			null,
			'foo (en)'
		];

		return $provider;
	}

}

<?php

namespace SMW\Tests\DataValues\ValueFormatters;

use PHPUnit\Framework\TestCase;
use SMW\DataValues\MonolingualTextValue;
use SMW\DataValues\ValueFormatters\MonolingualTextValueFormatter;
use SMW\DataValues\ValueParsers\MonolingualTextValueParser;
use SMW\DataValues\ValueValidators\ConstraintValueValidator;
use SMW\Services\DataValueServiceFactory;

/**
 * @covers \SMW\DataValues\ValueFormatters\MonolingualTextValueFormatter
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 2.4
 *
 * @author mwjames
 */
class MonolingualTextValueFormatterTest extends TestCase {

	private $dataValueServiceFactory;

	protected function setUp(): void {
		parent::setUp();

		$constraintValueValidator = $this->getMockBuilder( ConstraintValueValidator::class )
			->disableOriginalConstructor()
			->getMock();

		$this->dataValueServiceFactory = $this->getMockBuilder( DataValueServiceFactory::class )
			->disableOriginalConstructor()
			->getMock();

		$this->dataValueServiceFactory->expects( $this->any() )
			->method( 'getConstraintValueValidator' )
			->willReturn( $constraintValueValidator );

		$this->dataValueServiceFactory->expects( $this->any() )
			->method( 'getValueParser' )
			->willReturn( new MonolingualTextValueParser() );
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			MonolingualTextValueFormatter::class,
			new MonolingualTextValueFormatter()
		);
	}

	public function testIsFormatterForValidation() {
		$monolingualTextValue = $this->getMockBuilder( MonolingualTextValue::class )
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

		$this->expectException( 'RuntimeException' );
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

<?php

namespace SMW\Tests\DataValues\Number;

use Language;
use SMW\DataValues\Number\IntlNumberFormatter;
use SMW\Tests\PHPUnitCompat;

/**
 * @covers \SMW\DataValues\Number\IntlNumberFormatter
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.1
 *
 * @author mwjames
 */
class IntlNumberFormatterTest extends \PHPUnit_Framework_TestCase {

	use PHPUnitCompat;

	public function testCanConstruct() {

		$this->assertInstanceOf(
			IntlNumberFormatter::class,
			new IntlNumberFormatter( 10000 )
		);

		$this->assertInstanceOf(
			IntlNumberFormatter::class,
			IntlNumberFormatter::getInstance()
		);
	}

	/**
	 * @dataProvider numberProvider
	 */
	public function testLocalizedFormattedNumber( $maxNonExpNumber, $number, $userLanguage, $contentLanguage, $expected ) {

		$instance = new IntlNumberFormatter( $maxNonExpNumber );

		$instance->setOption( IntlNumberFormatter::USER_LANGUAGE, $userLanguage );
		$instance->setOption( IntlNumberFormatter::CONTENT_LANGUAGE, $contentLanguage );

		$this->assertEquals(
			$expected,
			$instance->format( $number )
		);
	}

	public function testZeroPadding() {

		$expected = '1000(foo)42';

		$instance = new IntlNumberFormatter( 10000 );

		// #2797
		$instance->setOption( IntlNumberFormatter::DECIMAL_SEPARATOR, '(foo)' );
		$instance->setOption( IntlNumberFormatter::THOUSANDS_SEPARATOR, '' );

		$this->assertEquals(
			$expected,
			$instance->format( 1000.42 )
		);
	}

	/**
	 * @dataProvider unformattedNumberByPrecisionProvider
	 */
	public function testGetUnformattedNumberByPrecision( $maxNonExpNumber, $number, $precision, $userLanguage, $contentLanguage, $expected ) {

		$instance = new IntlNumberFormatter( $maxNonExpNumber );

		$instance->setOption( IntlNumberFormatter::USER_LANGUAGE, $userLanguage );
		$instance->setOption( IntlNumberFormatter::CONTENT_LANGUAGE, $contentLanguage );

		$this->assertEquals(
			$expected,
			$instance->format( $number, $precision, IntlNumberFormatter::VALUE_FORMAT )
		);
	}

	public function testCompareFloatValue() {

		$instance = new IntlNumberFormatter( 1000 );

		$instance->setOption( IntlNumberFormatter::USER_LANGUAGE, 'en' );
		$instance->setOption( IntlNumberFormatter::CONTENT_LANGUAGE, 'en' );

		$this->assertSame(
			$instance->format( 100.0, false, IntlNumberFormatter::VALUE_FORMAT ),
			$instance->format( 100, false, IntlNumberFormatter::VALUE_FORMAT )
		);
	}

	/**
	 * @dataProvider separatorProvider
	 */
	public function testgetSeparatorByLanguage( $type, $locale, $userLanguage, $contentLanguage, $expected ) {

		$instance = new IntlNumberFormatter( 10000000 );

		$instance->setOption( IntlNumberFormatter::USER_LANGUAGE, $userLanguage );
		$instance->setOption( IntlNumberFormatter::CONTENT_LANGUAGE, $contentLanguage );

		$this->assertEquals(
			$expected,
			$instance->getSeparatorByLanguage( $type, $locale )
		);
	}

	public function testCustomSeparator() {

		$instance = new IntlNumberFormatter( 10000000 );

		$instance->setOption( IntlNumberFormatter::DECIMAL_SEPARATOR, 'FOO' );
		$instance->setOption( IntlNumberFormatter::THOUSANDS_SEPARATOR, 'BAR' );

		$this->assertEquals(
			'FOO',
			$instance->getSeparatorByLanguage( IntlNumberFormatter::DECIMAL_SEPARATOR, 'zzz' )
		);

		$this->assertEquals(
			'BAR',
			$instance->getSeparatorByLanguage( IntlNumberFormatter::THOUSANDS_SEPARATOR, 'zzz' )
		);
	}

	public function testTryTogetSeparatorByLanguageOnInvalidTypeThrowsException() {

		$instance = new IntlNumberFormatter( 10000000 );

		$this->setExpectedException( 'InvalidArgumentException' );
		$instance->getSeparatorByLanguage( 'Foo' );
	}

	public function numberProvider() {

		$provider[] = [
			10000,
			1000,
			'en',
			'en',
			'1,000'
		];

		$provider[] = [
			10000,
			1000.42,
			'en',
			'en',
			'1,000.42'
		];

		$provider[] = [
			10000,
			1000000,
			'en',
			'en',
			'1.0e+6'
		];

		$provider[] = [
			10000000,
			1000000,
			'en',
			'en',
			'1,000,000'
		];

		return $provider;
	}

	public function unformattedNumberByPrecisionProvider() {

		$provider['un.1'] = [
			10000,
			1000,
			2,
			'en',
			'en',
			'1000.00'
		];

		$provider['un.2'] = [
			10000,
			1000.42,
			3,
			'en',
			'en',
			'1000.420'
		];

		$provider['un.3'] = [
			10000,
			1000000,
			0,
			'en',
			'en',
			'1000000'
		];

		$provider['un.4'] = [
			10000000,
			1000000,
			2,
			'en',
			'en',
			'1000000.00'
		];

		$provider['un.5'] = [
			10000000,
			1000000,
			false,
			'en',
			'en',
			'1000000'
		];

		$provider['un.6'] = [
			10000000,
			312.23545555,
			false,
			'en',
			'en',
			'312.23545555'
		];

		$provider['un.7'] = [
			10000000,
			312.23545555,
			6,
			'en',
			'en',
			'312.235456'
		];

		$provider['un.8'] = [
			10000000,
			312.23545555,
			9,
			'en',
			'en',
			'312.235455550'
		];

		$provider['un.9'] = [
			10000000,
			312.23545555,
			null,
			'en',
			'en',
			'312.235455550'
		];

		$provider['un.10'] = [
			10000000,
			1.334e-13,
			false,
			'en',
			'en',
			'1.334e-13'
		];

		$provider['un.11'] = [
			10000000,
			1.334e-13,
			false,
			'en',
			'fr',
			'1,334e-13'
		];

		return $provider;
	}

	public function separatorProvider() {

		$provider['1.en'] = [
			IntlNumberFormatter::DECIMAL_SEPARATOR,
			IntlNumberFormatter::USER_LANGUAGE,
			'en',
			'en',
			'.'
		];

		$provider['2.en'] = [
			IntlNumberFormatter::THOUSANDS_SEPARATOR,
			IntlNumberFormatter::USER_LANGUAGE,
			'en',
			'en',
			','
		];

		$provider['3.en'] = [
			IntlNumberFormatter::DECIMAL_SEPARATOR,
			IntlNumberFormatter::CONTENT_LANGUAGE,
			'en',
			'en',
			'.'
		];

		$provider['4.en'] = [
			IntlNumberFormatter::THOUSANDS_SEPARATOR,
			IntlNumberFormatter::CONTENT_LANGUAGE,
			'en',
			'en',
			','
		];

		$provider['5.fr'] = [
			IntlNumberFormatter::DECIMAL_SEPARATOR,
			IntlNumberFormatter::USER_LANGUAGE,
			'fr',
			'en',
			','
		];

		$provider['6.fr'] = [
			IntlNumberFormatter::THOUSANDS_SEPARATOR,
			IntlNumberFormatter::USER_LANGUAGE,
			'fr',
			'en',
			' '
		];

		$provider['7.fr'] = [
			IntlNumberFormatter::DECIMAL_SEPARATOR,
			IntlNumberFormatter::CONTENT_LANGUAGE,
			'fr',
			'fr',
			','
		];

		$provider['8.fr'] = [
			IntlNumberFormatter::THOUSANDS_SEPARATOR,
			IntlNumberFormatter::CONTENT_LANGUAGE,
			'fr',
			'fr',
			' '
		];

		return $provider;
	}

}

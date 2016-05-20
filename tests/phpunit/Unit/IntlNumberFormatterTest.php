<?php

namespace SMW\Tests;

use Language;
use SMW\IntlNumberFormatter;

/**
 * @covers \SMW\IntlNumberFormatter
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.1
 *
 * @author mwjames
 */
class IntlNumberFormatterTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$this->assertInstanceOf(
			'\SMW\IntlNumberFormatter',
			new IntlNumberFormatter( 10000 )
		);

		$this->assertInstanceOf(
			'\SMW\IntlNumberFormatter',
			IntlNumberFormatter::getInstance()
		);
	}

	/**
	 * @dataProvider numberProvider
	 */
	public function testLocalizedFormattedNumber( $maxNonExpNumber, $number, $userLanguage, $contentLanguage, $expected ) {

		$instance = new IntlNumberFormatter( $maxNonExpNumber );

		$instance->setOption( 'user.language', $userLanguage );
		$instance->setOption( 'content.language', $contentLanguage );

		$this->assertEquals(
			$expected,
			$instance->format( $number )
		);
	}

	/**
	 * @dataProvider unformattedNumberByPrecisionProvider
	 */
	public function testGetUnformattedNumberByPrecision( $maxNonExpNumber, $number, $precision, $userLanguage, $contentLanguage, $expected ) {

		$instance = new IntlNumberFormatter( $maxNonExpNumber );

		$instance->setOption( 'user.language', $userLanguage );
		$instance->setOption( 'content.language', $contentLanguage );

		$this->assertEquals(
			$expected,
			$instance->format( $number, $precision, IntlNumberFormatter::VALUE_FORMAT )
		);
	}

	public function testCompareFloatValue() {

		$instance = new IntlNumberFormatter( 1000 );

		$instance->setOption( 'user.language', 'en' );
		$instance->setOption( 'content.language', 'en' );

		$this->assertSame(
			$instance->format( 100.0, false, IntlNumberFormatter::VALUE_FORMAT ),
			$instance->format( 100, false, IntlNumberFormatter::VALUE_FORMAT )
		);
	}

	/**
	 * @dataProvider separatorProvider
	 */
	public function testGetSeparator( $type, $locale, $userLanguage, $contentLanguage, $expected ) {

		$instance = new IntlNumberFormatter( 10000000 );

		$instance->setOption( 'user.language', $userLanguage );
		$instance->setOption( 'content.language', $contentLanguage );

		$this->assertEquals(
			$expected,
			$instance->getSeparator( $type, $locale )
		);
	}

	public function testCustomSeparator() {

		$instance = new IntlNumberFormatter( 10000000 );

		$instance->setOption( 'separator.decimal', 'FOO' );
		$instance->setOption( 'separator.thousands', 'BAR' );

		$this->assertEquals(
			'FOO',
			$instance->getSeparator( IntlNumberFormatter::DECIMAL_SEPARATOR, 'zzz' )
		);

		$this->assertEquals(
			'BAR',
			$instance->getSeparator( IntlNumberFormatter::THOUSANDS_SEPARATOR, 'zzz' )
		);
	}

	public function testTryToGetSeparatorOnInvalidTypeThrowsException() {

		$instance = new IntlNumberFormatter( 10000000 );

		$this->setExpectedException( 'InvalidArgumentException' );
		$instance->getSeparator( 'Foo' );
	}

	public function numberProvider() {

		$provider[] = array(
			10000,
			1000,
			'en',
			'en',
			'1,000'
		);

		$provider[] = array(
			10000,
			1000.42,
			'en',
			'en',
			'1,000.42'
		);

		$provider[] = array(
			10000,
			1000000,
			'en',
			'en',
			'1.0e+6'
		);

		$provider[] = array(
			10000000,
			1000000,
			'en',
			'en',
			'1,000,000'
		);

		return $provider;
	}

	public function unformattedNumberByPrecisionProvider() {

		$provider['un.1'] = array(
			10000,
			1000,
			2,
			'en',
			'en',
			'1000.00'
		);

		$provider['un.2'] = array(
			10000,
			1000.42,
			3,
			'en',
			'en',
			'1000.420'
		);

		$provider['un.3'] = array(
			10000,
			1000000,
			0,
			'en',
			'en',
			'1000000'
		);

		$provider['un.4'] = array(
			10000000,
			1000000,
			2,
			'en',
			'en',
			'1000000.00'
		);

		$provider['un.5'] = array(
			10000000,
			1000000,
			false,
			'en',
			'en',
			'1000000'
		);

		$provider['un.6'] = array(
			10000000,
			312.23545555,
			false,
			'en',
			'en',
			'312.23545555'
		);

		$provider['un.7'] = array(
			10000000,
			312.23545555,
			6,
			'en',
			'en',
			'312.235456'
		);

		$provider['un.8'] = array(
			10000000,
			312.23545555,
			9,
			'en',
			'en',
			'312.235455550'
		);

		$provider['un.9'] = array(
			10000000,
			312.23545555,
			null,
			'en',
			'en',
			'312.235455550'
		);

		$provider['un.10'] = array(
			10000000,
			1.334e-13,
			false,
			'en',
			'en',
			'1.334e-13'
		);

		$provider['un.11'] = array(
			10000000,
			1.334e-13,
			false,
			'en',
			'fr',
			'1,334e-13'
		);

		return $provider;
	}

	public function separatorProvider() {

		$provider['1.en'] = array(
			IntlNumberFormatter::DECIMAL_SEPARATOR,
			IntlNumberFormatter::USER_LANGUAGE,
			'en',
			'en',
			'.'
		);

		$provider['2.en'] = array(
			IntlNumberFormatter::THOUSANDS_SEPARATOR,
			IntlNumberFormatter::USER_LANGUAGE,
			'en',
			'en',
			','
		);

		$provider['3.en'] = array(
			IntlNumberFormatter::DECIMAL_SEPARATOR,
			IntlNumberFormatter::CONTENT_LANGUAGE,
			'en',
			'en',
			'.'
		);

		$provider['4.en'] = array(
			IntlNumberFormatter::THOUSANDS_SEPARATOR,
			IntlNumberFormatter::CONTENT_LANGUAGE,
			'en',
			'en',
			','
		);

		$provider['5.fr'] = array(
			IntlNumberFormatter::DECIMAL_SEPARATOR,
			IntlNumberFormatter::USER_LANGUAGE,
			'fr',
			'en',
			','
		);

		$provider['6.fr'] = array(
			IntlNumberFormatter::THOUSANDS_SEPARATOR,
			IntlNumberFormatter::USER_LANGUAGE,
			'fr',
			'en',
			' '
		);

		$provider['7.fr'] = array(
			IntlNumberFormatter::DECIMAL_SEPARATOR,
			IntlNumberFormatter::CONTENT_LANGUAGE,
			'fr',
			'fr',
			','
		);

		$provider['8.fr'] = array(
			IntlNumberFormatter::THOUSANDS_SEPARATOR,
			IntlNumberFormatter::CONTENT_LANGUAGE,
			'fr',
			'fr',
			' '
		);

		return $provider;
	}

}

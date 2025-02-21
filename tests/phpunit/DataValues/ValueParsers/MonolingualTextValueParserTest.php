<?php

namespace SMW\Tests\DataValues\ValueParsers;

use SMW\DataValues\ValueParsers\MonolingualTextValueParser;

/**
 * @covers \SMW\DataValues\ValueParsers\MonolingualTextValueParser
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 2.4
 *
 * @author mwjames
 */
class MonolingualTextValueParserTest extends \PHPUnit\Framework\TestCase {

	public function testCanConstruct() {
		$this->assertInstanceOf(
			'\SMW\DataValues\ValueParsers\MonolingualTextValueParser',
			new MonolingualTextValueParser()
		);
	}

	/**
	 * @dataProvider fullStringProvider
	 */
	public function testFullParsableString( $value, $expectedText, $expectedLanguageCode ) {
		$instance = new MonolingualTextValueParser();
		[ $text, $languageCode ] = $instance->parse( $value );

		$this->assertEquals(
			$expectedText,
			$text
		);

		$this->assertEquals(
			$expectedLanguageCode,
			$languageCode
		);
	}

	public function testParsableStringWithMissingLanguageCode() {
		$instance = new MonolingualTextValueParser();
		[ $text, $languageCode ] = $instance->parse( 'FooBar' );

		$this->assertEquals(
			'FooBar',
			$text
		);
	}

	public function fullStringProvider() {
		$provider[] = [
			'Foo@EN',
			'Foo',
			'en'
		];

		$provider[] = [
			'testWith@example.org@zh-hans',
			'testWith@example.org',
			'zh-Hans'
		];

		$provider[] = [
			[ 'EN' => 'Foo' ],
			'Foo',
			'en'
		];

		$provider[] = [
			[ 'EN', 'Foo' ],
			'Foo',
			''
		];

		$provider[] = [
			[ 'EN', [] ],
			'',
			''
		];

		return $provider;
	}

}

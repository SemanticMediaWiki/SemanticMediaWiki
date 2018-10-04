<?php

namespace SMW\Tests\Query\Parser;

use SMW\Query\Parser\Tokenizer;

/**
 * @covers \SMW\Query\Parser\Tokenizer
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class TokenizerTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$this->assertInstanceOf(
			Tokenizer::class,
			new Tokenizer()
		);
	}

	/**
	 * @dataProvider tokenProvider
	 */
	public function testGetToken( $currentString, $stoppattern, $expectedRes, $expectedCurrent ) {

		$instance = new Tokenizer();
		$instance->setDefaultPattern( [] );

		$res = $instance->getToken( $currentString, $stoppattern );

		$this->assertEquals(
			$expectedRes,
			$res
		);

		$this->assertEquals(
			$expectedCurrent,
			$currentString
		);
	}

	public function tokenProvider() {

		yield [
			'',
			'',
			'',
			''
		];

		yield [
			'[[Foo]]',
			'',
			'[[',
			'Foo]]'
		];

		yield [
			'|Foo',
			'',
			'|',
			'Foo'
		];

		yield [
			'::Foo',
			'',
			'::',
			'Foo'
		];

		yield [
			'Foo]]',
			'',
			'Foo',
			']]'
		];

		yield [
			'Foo]][[Bar',
			'',
			'Foo',
			']][[Bar'
		];

		yield [
			']][[Bar',
			'',
			']]',
			'[[Bar'
		];
	}

}

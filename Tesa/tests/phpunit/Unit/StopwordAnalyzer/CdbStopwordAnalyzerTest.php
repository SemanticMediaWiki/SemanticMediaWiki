<?php

namespace Onoi\Tesa\Tests\StopwordAnalyzer;

use Onoi\Tesa\StopwordAnalyzer\CdbStopwordAnalyzer;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Onoi\Tesa\StopwordAnalyzer\CdbStopwordAnalyzer
 * @group onoi-tesa
 *
 * @license GPL-2.0-or-later
 * @since 0.1
 *
 * @author mwjames
 */
class CdbStopwordAnalyzerTest extends TestCase {

	public function testTryToCreateCdbByLanguageOnInvalidLanguageThrowsException() {
		$this->expectException( 'RuntimeException' );

		CdbStopwordAnalyzer::createCdbByLanguage(
			CdbStopwordAnalyzer::getLocation(),
			'foo'
		);
	}

	public function testTryToCreateCdbByLanguageOnInvalidJsonIndexThrowsException() {
		$this->expectException( 'RuntimeException' );

		CdbStopwordAnalyzer::createCdbByLanguage(
			__DIR__ . '/../../Fixtures/StopwordAnalyzer/',
			'missingindex'
		);
	}

	public function testTryToCreateCdbByLanguageOnInvalidJsonThrowsException() {
		$this->expectException( 'RuntimeException' );

		CdbStopwordAnalyzer::createCdbByLanguage(
			__DIR__ . '/../../Fixtures/StopwordAnalyzer/',
			'invalid'
		);
	}

	/**
	 * @dataProvider languageProvider
	 */
	public function testCreateCdbByLanguage( $languageCode ) {
		$res = CdbStopwordAnalyzer::createCdbByLanguage(
			CdbStopwordAnalyzer::getLocation(),
			$languageCode
		);

		$this->assertTrue(
			$res
		);
	}

	/**
	 * @dataProvider stopWordProvider
	 */
	public function testIsStopWord( $languageCode, $word, $expected ) {
		$instane = new CdbStopwordAnalyzer(
			CdbStopwordAnalyzer::getTargetByLanguage( $languageCode )
		);

		$this->assertEquals(
			$expected,
			$instane->isStopWord( $word )
		);
	}

	public function languageProvider() {
		$provider[] = [
			'en',
		];

		$provider[] = [
			'de'
		];

		$provider[] = [
			'ja'
		];

		$provider[] = [
			'zh'
		];

		$provider[] = [
			'es'
		];

		$provider[] = [
			'fr'
		];

		$provider[] = [
			'pt'
		];

		$provider[] = [
			'pt-br'
		];

		return $provider;
	}

	public function stopWordProvider() {
		$provider[] = [
			'en',
			'Foo',
			false
		];

		$provider[] = [
			'en',
			'the',
			true
		];

		$provider[] = [
			'ja',
			'それぞれ',
			true
		];

		$provider[] = [
			'zh',
			'不单',
			true
		];

		$provider[] = [
			'es',
			'arriba',
			true
		];

		$provider[] = [
			'fr',
			'devrait',
			true
		];

		$provider[] = [
			'pt',
			'conhecido',
			true
		];

		$provider[] = [
			'pt-br',
			'mediante',
			true
		];

		return $provider;
	}

}

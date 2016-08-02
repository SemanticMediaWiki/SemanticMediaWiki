<?php

namespace Onoi\Tesa\Tests\StopwordAnalyzer;

use Onoi\Tesa\StopwordAnalyzer\CdbStopwordAnalyzer;

/**
 * @covers \Onoi\Tesa\StopwordAnalyzer\CdbStopwordAnalyzer
 * @group onoi-tesa
 *
 * @license GNU GPL v2+
 * @since 0.1
 *
 * @author mwjames
 */
class CdbStopwordAnalyzerTest extends \PHPUnit_Framework_TestCase {

	public function testTryToCreateCdbByLanguageOnInvalidLanguageThrowsException() {

		$this->setExpectedException( 'RuntimeException' );

		CdbStopwordAnalyzer::createCdbByLanguage(
			CdbStopwordAnalyzer::getLocation(),
			'foo'
		);
	}

	public function testTryToCreateCdbByLanguageOnInvalidJsonIndexThrowsException() {

		$this->setExpectedException( 'RuntimeException' );

		CdbStopwordAnalyzer::createCdbByLanguage(
			__DIR__ . '/../../Fixtures/StopwordAnalyzer/',
			'missingindex'
		);
	}

	public function testTryToCreateCdbByLanguageOnInvalidJsonThrowsException() {

		$this->setExpectedException( 'RuntimeException' );

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
	public function testIsStopWord( $languageCode , $word, $expected ) {

		$instane = new CdbStopwordAnalyzer(
			CdbStopwordAnalyzer::getTargetByLanguage( $languageCode )
		);

		$this->assertEquals(
			$expected,
			$instane->isStopWord( $word )
		);
	}

	public function languageProvider() {

		$provider[] = array(
			'en',
		);

		$provider[] = array(
			'de'
		);

		$provider[] = array(
			'ja'
		);

		$provider[] = array(
			'zh'
		);

		$provider[] = array(
			'es'
		);

		$provider[] = array(
			'fr'
		);

		$provider[] = array(
			'pt'
		);

		$provider[] = array(
			'pt-br'
		);

		return $provider;
	}

	public function stopWordProvider() {

		$provider[] = array(
			'en',
			'Foo',
			false
		);

		$provider[] = array(
			'en',
			'the',
			true
		);

		$provider[] = array(
			'ja',
			'それぞれ',
			true
		);

		$provider[] = array(
			'zh',
			'不单',
			true
		);

		$provider[] = array(
			'es',
			'arriba',
			true
		);

		$provider[] = array(
			'fr',
			'devrait',
			true
		);

		$provider[] = array(
			'pt',
			'conhecido',
			true
		);

		$provider[] = array(
			'pt-br',
			'mediante',
			true
		);

		return $provider;
	}

}

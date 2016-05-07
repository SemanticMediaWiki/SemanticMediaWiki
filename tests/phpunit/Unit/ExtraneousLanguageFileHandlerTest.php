<?php

namespace SMW\Tests;

use SMW\ExtraneousLanguageFileHandler;

/**
 * @covers \SMW\ExtraneousLanguageFileHandler
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.4
 *
 * @author mwjames
 */
class ExtraneousLanguageFileHandlerTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$this->assertInstanceOf(
			'\SMW\ExtraneousLanguageFileHandler',
			new ExtraneousLanguageFileHandler()
		);
	}

	/**
	 * @dataProvider languageCodeProvider
	 */
	public function testNewByLanguageCode( $languageCode, $expected ) {

		$instance = new ExtraneousLanguageFileHandler();

		$this->assertInstanceOf(
			$expected,
			$instance->newByLanguageCode( $languageCode )
		);
	}

	public function languageCodeProvider() {

		$provider[] = array(
			'en',
			'\SMWLanguageEn'
		);

		$provider[] = array(
			'es',
			'\SMWLanguageEs'
		);

		$provider[] = array(
			'ja',
			'\SMWLanguageJa'
		);

		$provider[] = array(
			'FOO',
			'\SMWLanguageEn'
		);

		$provider[] = array(
			'zh-hans',
			'\SMWLanguageZh_cn'
		);

		$provider[] = array(
			'zh-tw',
			'\SMWLanguageZh_tw'
		);

		return $provider;
	}

}

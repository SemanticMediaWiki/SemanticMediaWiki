<?php

namespace SMW\Tests\System;

use SMW\Tests\Utils\JsonFileReader;

/**
 * @group SMW
 * @group SMWExtension
 *
 * @group semantic-mediawiki-system
 *
 * @license GNU GPL v2+
 * @since 2.1
 *
 * @author mwjames
 */
class I18nJsonFileIntegrityTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @dataProvider i18nFileProvider
	 */
	public function testI18NJsonDecodeEncode( $file ) {

		$jsonFileReader = new JsonFileReader( $file );

		$this->assertInternalType(
			'integer',
			$jsonFileReader->getModificationTime()
		);

		$this->assertInternalType(
			'array',
			$jsonFileReader->getContents()
		);
	}

	public function i18nFileProvider() {

		$basepath = __DIR__ . '/../../../i18n/';

		$provider[] = array(
			$basepath . 'qqq.json'
		);

		$provider[] = array(
			$basepath . 'en.json'
		);

		return $provider;
	}

}

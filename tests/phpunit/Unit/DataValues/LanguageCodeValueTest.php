<?php

namespace SMW\Tests\DataValues;

use SMW\DataValues\LanguageCodeValue;

/**
 * @covers \SMW\DataValues\LanguageCodeValue
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.4
 *
 * @author mwjames
 */
class LanguageCodeValueTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$this->assertInstanceOf(
			'\SMW\DataValues\LanguageCodeValue',
			new LanguageCodeValue()
		);
	}

	public function testHasErrorForMissingLanguageCode() {

		$instance = new LanguageCodeValue();
		$instance->setUserValue( '' );

		$this->assertNotEmpty(
			$instance->getErrors()
		);
	}

	public function testHasErrorForInvalidLanguageCode() {

		if ( version_compare( $GLOBALS['wgVersion'], '1.20', '<' ) ) {
			$this->markTestSkipped( 'Skipping because `Language::isSupportedLanguage` is not supported on 1.19' );
		}

		$instance = new LanguageCodeValue();
		$instance->setUserValue( '-Foo' );

		$this->assertNotEmpty(
			$instance->getErrors()
		);
	}

	public function testNormalizationOnLanguageCodeOccurs() {

		$mixedCase = new LanguageCodeValue();
		$mixedCase->setUserValue( 'eN' );

		$upperCase = new LanguageCodeValue();
		$upperCase->setUserValue( 'EN' );

		$this->assertEquals(
			$mixedCase,
			$upperCase
		);

		$this->assertEquals(
			'en',
			$mixedCase->getDataItem()->getString()
		);

		$this->assertEquals(
			'en',
			$upperCase->getDataItem()->getString()
		);
	}

}

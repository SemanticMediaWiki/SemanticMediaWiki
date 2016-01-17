<?php

namespace SMW\Tests\DataValues;

use SMW\DataValues\MonolingualTextValue;
use SMW\Options;

/**
 * @covers \SMW\DataValues\MonolingualTextValue
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.4
 *
 * @author mwjames
 */
class MonolingualTextValueTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$this->assertInstanceOf(
			'\SMW\DataValues\MonolingualTextValue',
			new MonolingualTextValue()
		);
	}

	public function testErrorForMissingLanguageCode() {

		$instance = new MonolingualTextValue();

		$instance->setOptions(
			new Options( array( 'smwgDVFeatures' => SMW_DV_MLTV_LCODE ) )
		);

		$instance->setUserValue( 'Foo' );

		$this->assertNotEmpty(
			$instance->getErrors()
		);
	}

	public function testNoErrorForMissingLanguageCodeWhenFeatureIsDisabled() {

		$instance = new MonolingualTextValue();

		$instance->setOptions(
			new Options( array( 'smwgDVFeatures' => false ) )
		);

		$instance->setUserValue( 'Foo' );

		$this->assertEmpty(
			$instance->getErrors()
		);
	}

	public function testErrorForInvalidLanguageCode() {

		if ( version_compare( $GLOBALS['wgVersion'], '1.20', '<' ) ) {
			$this->markTestSkipped( 'Skipping because `Language::isSupportedLanguage` is not supported on 1.19' );
		}

		$instance = new MonolingualTextValue();
		$instance->setUserValue( 'Foo@foobar' );

		$this->assertNotEmpty(
			$instance->getErrors()
		);
	}

	public function testValidParsableUserValue() {

		$instance = new MonolingualTextValue();
		$instance->setUserValue( 'Foo@en' );

		$this->assertEmpty(
			$instance->getErrors()
		);

		$this->assertInstanceOf(
			'\SMWDIContainer',
			$instance->getDataItem()
		);

		foreach ( $instance->getDataItems() as $dataItem ) {
			$this->assertInstanceOf(
				'\SMWDIBlob',
				$dataItem
			);
		}

		$this->assertEquals(
			'Foo',
			$instance->getTextValueByLanguage( 'en' )->getDataItem()->getString()
		);
	}

	public function testTryToGetTextValueByLanguageForUnrecognizedLanguagCode() {

		$instance = new MonolingualTextValue();
		$instance->setUserValue( 'Foo@en' );

		$this->assertNull(
			$instance->getTextValueByLanguage( 'bar' )
		);
	}

	public function testGetWikiValueForValidMonolingualTextValue() {

		$instance = new MonolingualTextValue();
		$instance->setUserValue( 'Foo@en' );

		$this->assertEquals(
			'Foo (en)',
			$instance->getWikiValue()
		);
	}

	public function testGetWikiValueForInvalidMonolingualTextValue() {

		if ( version_compare( $GLOBALS['wgVersion'], '1.20', '<' ) ) {
			$this->markTestSkipped( 'Skipping because `Language::isSupportedLanguage` is not supported on 1.19' );
		}

		$instance = new MonolingualTextValue();
		$instance->setUserValue( 'Foo@foobar' );

		$this->assertContains(
			'class="smw-highlighter" data-type="4"',
			$instance->getWikiValue()
		);
	}

	public function testGetProperties() {

		$instance = new MonolingualTextValue();
		$properties = $instance->getPropertyDataItems();

		$this->assertEquals(
			'_TEXT',
			$properties[0]->getKey()
		);

		$this->assertEquals(
			'_LCODE',
			$properties[1]->getKey()
		);
	}

}

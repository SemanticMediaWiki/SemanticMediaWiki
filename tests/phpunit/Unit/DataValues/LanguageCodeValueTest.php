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
			$mixedCase->getDataItem(),
			$upperCase->getDataItem()
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

	public function testInvalidLanguageCode() {

		$instance = new LanguageCodeValue();
		$instance->setUserValue( 'Foo' );

		$this->assertContains(
			'[2,"smw-datavalue-languagecode-invalid","foo"]',
			$instance->getDataItem()->getString()
		);
	}

	public function testInvalidLanguageCodeIsAllowedInQueryContext() {

		$instance = new LanguageCodeValue();
		$instance->setOption( LanguageCodeValue::OPT_QUERY_CONTEXT, true );

		$instance->setUserValue( 'Foo' );

		$this->assertEquals(
			'foo',
			$instance->getDataItem()->getString()
		);
	}

}

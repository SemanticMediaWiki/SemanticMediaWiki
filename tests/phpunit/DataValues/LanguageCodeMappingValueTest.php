<?php

namespace SMW\Tests\DataValues;

use SMW\DataValues\LanguageCodeValue;
use SMW\Tests\PHPUnitCompat;

/**
 * @covers \SMW\DataValues\LanguageCodeValue
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 2.4
 *
 * @author mwjames
 * @reviewer thomas-topway-it
 */
class LanguageCodeMappingValueTest extends \PHPUnit\Framework\TestCase {

	use PHPUnitCompat;

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
		$mixedCase->setUserValue( 'DE-formal' );

		$upperCase = new LanguageCodeValue();
		$upperCase->setUserValue( 'DE-FORMAL' );

		$this->assertEquals(
			$mixedCase->getDataItem(),
			$upperCase->getDataItem()
		);

		$this->assertEquals(
			'de-formal',
			$mixedCase->getDataItem()->getString()
		);

		$this->assertEquals(
			'de-formal',
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

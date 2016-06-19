<?php

namespace SMW\Tests\DataValues;

use SMW\DataValues\ExternalFormatterUriValue;

/**
 * @covers \SMW\DataValues\ExternalFormatterUriValue
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class ExternalFormatterUriValueTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$this->assertInstanceOf(
			ExternalFormatterUriValue::class,
			new ExternalFormatterUriValue()
		);
	}

	public function testTryToParseUserValueOnInvalidUrlFormat() {

		$instance = new ExternalFormatterUriValue();
		$instance->setUserValue( 'foo' );

		$this->assertNotEmpty(
			$instance->getErrors()
		);
	}

	public function testTryToParseUserValueOnMissingPlaceholder() {

		$instance = new ExternalFormatterUriValue();
		$instance->setUserValue( 'http://example.org' );

		$this->assertNotEmpty(
			$instance->getErrors()
		);
	}

	public function testGetFormattedUri() {

		$instance = new ExternalFormatterUriValue();
		$instance->setUserValue( 'http://example.org/$1' );

		$this->assertEquals(
			'http://example.org/foo',
			$instance->getFormattedUriWith( 'foo' )
		);
	}

}

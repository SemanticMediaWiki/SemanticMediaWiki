<?php

namespace SMW\Tests\Query;

use PHPUnit\Framework\TestCase;
use SMW\DataItems\Property;
use SMW\Query\PrintRequest;
use SMW\Query\PrintRequestFactory;

/**
 * @covers \SMW\Query\PrintRequestFactory
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 2.1
 *
 * @author mwjames
 */
class PrintRequestFactoryTest extends TestCase {

	public function testCanConstruct() {
		$this->assertInstanceOf(
			PrintRequestFactory::class,
			new PrintRequestFactory()
		);
	}

	public function testCanConstructPrintRequestFromProperty() {
		$instance = new PrintRequestFactory();

		$printRequest = $instance->newFromProperty(
			new Property( 'Foo' )
		);

		$this->assertInstanceOf(
			PrintRequest::class,
			$printRequest
		);

		$this->assertEquals(
			'Foo',
			$printRequest->getLabel()
		);
	}

	public function testCanConstructPrintRequestFromText() {
		$instance = new PrintRequestFactory();

		$printRequest = $instance->newFromText(
			'Foo'
		);

		$this->assertInstanceOf(
			PrintRequest::class,
			$printRequest
		);
	}

	public function testPrintRequestFromTextToReturnNullOnInvalidText() {
		$instance = new PrintRequestFactory();

		$printRequest = $instance->newFromText(
			'--[[Foo',
			false
		);

		$this->assertNull(
			$printRequest
		);
	}

	public function testCanConstructThisPrintRequest() {
		$instance = new PrintRequestFactory();

		$printRequest = $instance->newThisPrintRequest(
			'Foo'
		);

		$this->assertTrue(
			$printRequest->isMode( PrintRequest::PRINT_THIS )
		);
	}

}

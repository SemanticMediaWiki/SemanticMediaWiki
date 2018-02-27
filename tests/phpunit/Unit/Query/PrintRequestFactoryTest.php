<?php

namespace SMW\Tests\Query;

use SMW\DIProperty;
use SMW\Query\PrintRequestFactory;
use SMW\Query\PrintRequest;

/**
 * @covers \SMW\Query\PrintRequestFactory
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.1
 *
 * @author mwjames
 */
class PrintRequestFactoryTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$this->assertInstanceOf(
			'\SMW\Query\PrintRequestFactory',
			new PrintRequestFactory()
		);
	}

	public function testCanConstructPrintRequestFromProperty() {

		$instance = new PrintRequestFactory();

		$printRequest = $instance->newPrintRequestByProperty(
			new DIProperty( 'Foo' )
		);

		$this->assertInstanceOf(
			'\SMW\Query\PrintRequest',
			$printRequest
		);

		$this->assertEquals(
			'Foo',
			$printRequest->getLabel()
		);
	}

	public function testCanConstructPrintRequestFromText() {

		$instance = new PrintRequestFactory();

		$printRequest = $instance->newPrintRequestFromText(
			'Foo'
		);

		$this->assertInstanceOf(
			'\SMW\Query\PrintRequest',
			$printRequest
		);
	}

	public function testPrintRequestFromTextToReturnNullOnInvalidText() {

		$instance = new PrintRequestFactory();

		$printRequest = $instance->newPrintRequestFromText(
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

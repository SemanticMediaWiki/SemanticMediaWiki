<?php

namespace SMW\Tests\Query;

use SMW\Query\PrintRequestFactory;
use SMW\DIProperty;

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

	public function testPropertyPrintRequest() {

		$instance = new PrintRequestFactory();
		$printRequest = $instance->newPropertyPrintRequest( new DIProperty( 'Foo' ) );

		$this->assertInstanceOf(
			'\SMW\Query\PrintRequest',
			$printRequest
		);

		$this->assertEquals(
			'Foo',
			$printRequest->getLabel()
		);
	}

}

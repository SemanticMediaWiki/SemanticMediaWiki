<?php

namespace SMW\Tests;

use SMW\NullLogger;

/**
 * @covers \SMW\NullLogger
 *
 * @group SMW
 * @group SMWExtension
 *
 * @license GNU GPL v2+
 * @since 2.1
 *
 * @author mwjames
 */
class NullLoggerTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$this->assertInstanceOf(
			'\SMW\NullLogger',
			new NullLogger()
		);
	}

	public function testNull() {

		$instance = new NullLogger();

		$this->assertNull(
			$instance->logToTable( 'Foo', 'Bar', 'Baz', 'Yui' )
		);

		$this->assertNull(
			$instance->log( 'Foo', 'Bar' )
		);
	}

}

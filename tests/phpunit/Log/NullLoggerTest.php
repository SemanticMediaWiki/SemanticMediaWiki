<?php

namespace SMW\Tests\Log;

use SMW\Log\NullLogger;

/**
 * @covers \SMW\Log\NullLogger
 *
 * @ingroup Test
 *
 * @group SMW
 * @group SMWExtension
 *
 * @licence GNU GPL v2+
 * @since 1.9.0.3
 *
 * @author mwjames
 */
class NullLoggerTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {
		$this->assertInstanceOf( 'SMW\Log\NullLogger', new NullLogger );
	}

	public function testLogNull() {
		$instance = new NullLogger;

		$this->assertNull( $instance->debug( 'Bob walks with his dog' )  );
	}

}
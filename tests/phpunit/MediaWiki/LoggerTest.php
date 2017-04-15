<?php

namespace SMW\Tests\MediaWiki;

use SMW\MediaWiki\Logger;

/**
 * @covers \SMW\MediaWiki\Logger
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
class LoggerTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {
		$this->assertInstanceOf( 'SMW\MediaWiki\Logger', new Logger );
	}

	public function testInvokeMethodsWithStringMessage() {

		$instance = new Logger;

		$methods = array(
			'emergency',
			'alert',
			'critical',
			'error',
			'warning',
			'notice',
			'info',
			'debug'
		);

		$this->assertMethodsWithoutContext( $instance, $methods );
		$this->assertMethodsWithContext( $instance, $methods );

	}

	public function assertMethodsWithoutContext( $instance, $methods ) {

		foreach ( $methods as $method ) {
			$result = call_user_func_array(
				array( $instance, $method ),
				array( 'logging a string' )
			);

			$this->assertEquals( 'logging a string', $result );
		}

	}

	public function assertMethodsWithContext( $instance, $methods ) {

		foreach ( $methods as $method ) {
			$result = call_user_func_array(
				array( $instance, $method ),
				array( 'logging with context', array( new \stdClass ) )
			);

			$this->assertInternalType( 'string', $result );
		}

	}

}

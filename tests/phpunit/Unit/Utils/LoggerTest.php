<?php

namespace SMW\Tests\Utils;

use SMW\Utils\Logger;

/**
 * @covers \SMW\Utils\Logger
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class LoggerTest extends \PHPUnit_Framework_TestCase {

	private $logger;

	protected function setUp() {

		$this->logger = $this->getMockBuilder( '\Psr\Log\LoggerInterface' )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			Logger::class,
			new Logger( $this->logger )
		);
	}

	/**
	 * @dataProvider logProvider
	 */
	public function testLog( $role, $message, $context ) {

		$this->logger->expects( $this->once() )
			->method( 'log' );

		$instance = new Logger( $this->logger, $role );
		$instance->log( 'Foo', $message, $context );
	}

	public function logProvider() {

		yield [
			Logger::ROLE_DEVELOPER,
			'Foo',
			[ 'Foo' ]
		];

		yield [
			Logger::ROLE_DEVELOPER,
			'Foo',
			[ 'Foo', [ 'Bar' => 123 ] ]
		];
	}

}

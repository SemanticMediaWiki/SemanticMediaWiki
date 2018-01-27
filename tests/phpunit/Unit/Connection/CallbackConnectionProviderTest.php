<?php

namespace SMW\Tests\Connection;

use SMW\Connection\CallbackConnectionProvider;

/**
 * @covers \SMW\Connection\CallbackConnectionProvider
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class CallbackConnectionProviderTest extends \PHPUnit_Framework_TestCase {

	private $conection;

	public function setUp() {

		$this->connection = $this->getMockBuilder( '\stdClass' )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {

		$callback = function() {};

		$this->assertInstanceOf(
			CallbackConnectionProvider::class,
			new CallbackConnectionProvider( $callback )
		);
	}

	public function getConnection() {
		return $this->connection;
	}

	public function testGetConnectionFormCallback() {

		$callback = function() {
			return $this->connection;
		};

		$instance = new CallbackConnectionProvider(
			$callback
		);

		$this->assertEquals(
			$this->connection,
			$instance->getConnection()
		);
	}

	public function testGetConnectionFormStaticCallback() {

		$instance = new CallbackConnectionProvider(
			[ $this, 'getConnection' ]
		);

		$this->assertEquals(
			$this->connection,
			$instance->getConnection()
		);
	}

}

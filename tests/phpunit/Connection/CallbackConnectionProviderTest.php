<?php

namespace SMW\Tests\Connection;

use SMW\Connection\CallbackConnectionProvider;

/**
 * @covers \SMW\Connection\CallbackConnectionProvider
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.0
 *
 * @author mwjames
 */
class CallbackConnectionProviderTest extends \PHPUnit\Framework\TestCase {

	private $connection;

	public function setUp(): void {
		$this->connection = $this->getMockBuilder( '\stdClass' )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {
		$callback = static function () {
		};

		$this->assertInstanceOf(
			CallbackConnectionProvider::class,
			new CallbackConnectionProvider( $callback )
		);
	}

	public function getConnection() {
		return $this->connection;
	}

	public function testGetConnectionFormCallback() {
		$callback = function () {
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

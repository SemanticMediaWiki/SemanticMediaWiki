<?php

namespace SMW\Tests\Elastic;

use SMW\Elastic\Config;
use SMW\Tests\PHPUnitCompat;

/**
 * @covers \SMW\Elastic\Config
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class ConfigTest extends \PHPUnit\Framework\TestCase {

	use PHPUnitCompat;

	public function testCanConstruct() {
		$this->assertInstanceOf(
			Config::class,
			new Config()
		);
	}

	public function testIsDefaultStore_False() {
		$instance = new Config(
			[ Config::DEFAULT_STORE => 'Foo' ]
		);

		$this->assertFalse(
			$instance->isDefaultStore()
		);
	}

	public function testIsDefaultStore_True() {
		$instance = new Config(
			[ Config::DEFAULT_STORE => 'SMWElasticStore' ]
		);

		$this->assertTrue(
			$instance->isDefaultStore()
		);
	}

	public function testReassignDeprectedKeys() {
		$instance = new Config(
			[
				'foo' => [
					'foo.bar' => 42
				]
			]
		);

		$instance->setDeprectedKeys(
			[
				'foo' => [
					'foo.bar' => 'foo_bar'
				]
			]
		);

		$instance->reassignDeprectedKeys();

		$this->assertEquals(
			42,
			$instance->dotGet( 'foo.foo_bar' )
		);

		$this->assertFalse(
						$instance->dotGet( 'foo.foo.bar' )
		);
	}

	public function testLoadFromJSON() {
		$instance = new Config();
		$instance->set( 'Foo', '123' );
		$instance->set( 'Bar', '456' );

		$instance->loadFromJSON( json_encode( [ 'Foo' => 456 ] ) );

		$this->assertEquals(
			456,
			$instance->dotGet( 'Foo' )
		);

		$this->assertSame(
			'456',
			$instance->dotGet( 'Bar' )
		);

		$instance->set( 'A', [ 'B' => [ 'C', 'D' ] ] );

		$this->assertEquals(
			[ 'C', 'D' ],
			$instance->dotGet( 'A.B' )
		);

		$instance->loadFromJSON( json_encode( [ 'A.B' => 'C' ] ) );

		$this->assertEquals(
			[ 'C', 'D' ],
			$instance->dotGet( 'A.B' )
		);

		$instance->loadFromJSON( json_encode( [ 'A' => [ 'B' => [ 'E' ] ] ] ) );

		$this->assertEquals(
			[ 'E' ],
			$instance->dotGet( 'A.B' )
		);

		$instance->loadFromJSON( json_encode( [ 'A' => [ 'B' => 'C' ] ] ) );

		$this->assertEquals(
			'C',
			$instance->dotGet( 'A.B' )
		);
	}

	public function testReadfile_InaccessibleFileThrowsException() {
		$instance = new Config();

		$this->expectException( 'RuntimeException' );
		$instance->readFile( 'Foo' );
	}

}

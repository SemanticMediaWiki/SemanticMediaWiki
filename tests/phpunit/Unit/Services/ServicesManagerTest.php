<?php

namespace SMW\Tests\Services;

use SMW\Services\ServicesManager;

/**
 * @covers \SMW\Services\ServicesManager
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class ServicesManagerTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$this->assertInstanceOf(
			ServicesManager::class,
			new ServicesManager()
		);
	}

	public function testNewFromArray() {

		$instance = ServicesManager::newFromArray( [ 'Foo' => [ $this, 'staticCallback' ] ] );

		$this->assertInstanceOf(
			ServicesManager::class,
			$instance
		);

		$this->assertTrue(
			$instance->has( 'FOO' )
		);

		$this->assertEquals(
			42,
			$instance->get( 'FOO' )
		);
	}

	public function testGetForUnknownServiceThrowsException() {

		$instance = ServicesManager::newFromArray( [] );

		$this->setExpectedException( '\SMW\Services\Exception\ServiceNotFoundException' );
		$instance->get( 'FOO' );
	}

	public function staticCallback() {
		return 42;
	}

}

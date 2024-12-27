<?php

namespace SMW\Tests\SQLStore\QueryEngine;

use SMW\SQLStore\QueryEngine\EngineOptions;
use SMW\Tests\PHPUnitCompat;

/**
 * @covers \SMW\SQLStore\QueryEngine\EngineOptions
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.2
 *
 * @author mwjames
 */
class EngineOptionsTest extends \PHPUnit\Framework\TestCase {

	use PHPUnitCompat;

	public function testCanConstruct() {
		$this->assertInstanceOf(
			'\SMW\SQLStore\QueryEngine\EngineOptions',
			new EngineOptions()
		);
	}

	public function testInitialState() {
		$instance = new EngineOptions();

		$this->assertIsBool(

			$instance->get( 'smwgIgnoreQueryErrors' )
		);

		$this->assertIsInt(

			$instance->get( 'smwgQSortFeatures' )
		);
	}

	public function testAddOption() {
		$instance = new EngineOptions();

		$this->assertFalse(
			$instance->has( 'Foo' )
		);

		$instance->set( 'Foo', 42 );

		$this->assertEquals(
			42,
			$instance->get( 'Foo' )
		);
	}

	public function testUnregisteredKeyThrowsException() {
		$instance = new EngineOptions();

		$this->expectException( 'InvalidArgumentException' );
		$instance->get( 'Foo' );
	}

}

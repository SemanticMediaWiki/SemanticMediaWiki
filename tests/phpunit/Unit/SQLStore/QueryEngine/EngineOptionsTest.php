<?php

namespace SMW\Tests\Unit\SQLStore\QueryEngine;

use PHPUnit\Framework\TestCase;
use SMW\SQLStore\QueryEngine\EngineOptions;

/**
 * @covers \SMW\SQLStore\QueryEngine\EngineOptions
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 2.2
 *
 * @author mwjames
 */
class EngineOptionsTest extends TestCase {

	public function testCanConstruct() {
		$this->assertInstanceOf(
			EngineOptions::class,
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

	public function testUseLegacyQueryDefaultValue() {
		$instance = new EngineOptions();

		$this->assertFalse(
			$instance->get( 'smwgQUseLegacyQuery' )
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

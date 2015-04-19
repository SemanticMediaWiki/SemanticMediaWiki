<?php

namespace SMW\Tests\SPARQLStore\QueryEngine;

use SMW\SPARQLStore\QueryEngine\EngineOptions;

/**
 * @covers \SMW\SPARQLStore\QueryEngine\EngineOptions
 *
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.1
 *
 * @author mwjames
 */
class EngineOptionsTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$this->assertInstanceOf(
			'\SMW\SPARQLStore\QueryEngine\EngineOptions',
			new EngineOptions()
		);
	}

	public function testInitialState() {

		$instance = new EngineOptions();

		$this->assertInternalType(
			'boolean',
			$instance->get( 'smwgIgnoreQueryErrors' )
		);

		$this->assertInternalType(
			'boolean',
			$instance->get( 'smwgQSortingSupport' )
		);

		$this->assertInternalType(
			'boolean',
			$instance->get( 'smwgQRandSortingSupport' )
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

		$this->setExpectedException( 'InvalidArgumentException' );
		$instance->get( 'Foo' );
	}

}


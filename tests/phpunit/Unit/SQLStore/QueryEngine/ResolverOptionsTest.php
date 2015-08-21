<?php

namespace SMW\Tests\SQLStore\QueryEngine;

use SMW\SQLStore\QueryEngine\ResolverOptions;

/**
 * @covers \SMW\SQLStore\QueryEngine\ResolverOptions
 *
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.2
 *
 * @author mwjames
 */
class ResolverOptionsTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$this->assertInstanceOf(
			'\SMW\SQLStore\QueryEngine\ResolverOptions',
			new ResolverOptions()
		);
	}

	public function testInitialState() {

		$instance = new ResolverOptions();

		$this->assertInternalType(
			'integer',
			$instance->get( 'smwgQSubpropertyDepth' )
		);

		$this->assertInternalType(
			'integer',
			$instance->get( 'smwgQSubcategoryDepth' )
		);
	}

	public function testAddOption() {

		$instance = new ResolverOptions();

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

		$instance = new ResolverOptions();

		$this->setExpectedException( 'InvalidArgumentException' );
		$instance->get( 'Foo' );
	}

}

<?php

namespace SMW\Tests\Utils;

use SMW\Utils\CircularReferenceGuard;

/**
 * @covers \SMW\Utils\CircularReferenceGuard
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.2
 *
 * @author mwjames
 */
class CircularReferenceGuardTest extends \PHPUnit\Framework\TestCase {

	public function testCanConstruct() {
		$this->assertInstanceOf(
			'\SMW\Utils\CircularReferenceGuard',
			new CircularReferenceGuard()
		);
	}

	public function testRoundtripForRegisteredNamespace() {
		$instance = new CircularReferenceGuard( 'bar' );
		$instance->setMaxRecursionDepth( 1 );

		$this->assertSame(
			0,
			$instance->get( 'Foo' )
		);

		$instance->mark( 'Foo' );
		$instance->mark( 'Foo' );

		$this->assertEquals(
			2,
			$instance->get( 'Foo' )
		);

		$this->assertTrue(
			$instance->isCircular( 'Foo' )
		);

		$instance->unmark( 'Foo' );

		$this->assertSame(
			1,
			$instance->get( 'Foo' )
		);

		$this->assertFalse(
			$instance->isCircular( 'Foo' )
		);

		$instance->unmark( 'notBeenMarkedBefore' );
	}

	/**
	 * @depends testRoundtripForRegisteredNamespace
	 */
	public function testVerifyRetainedReferenceFromPreviousInvocation() {
		$instance = new CircularReferenceGuard( 'bar' );

		$this->assertSame(
			1,
			$instance->get( 'Foo' )
		);

		$instance->reset( 'bar' );

		$this->assertSame(
			0,
			$instance->get( 'Foo' )
		);
	}

}

<?php

namespace SMW\Tests;

use SMW\CircularReferenceGuard;

/**
 * @covers \SMW\CircularReferenceGuard
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.2
 *
 * @author mwjames
 */
class CircularReferenceGuardTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$this->assertInstanceOf(
			'\SMW\CircularReferenceGuard',
			new CircularReferenceGuard()
		);
	}

	public function testRoundtripForRegisteredNamespace() {

		$instance = new CircularReferenceGuard( 'bar' );
		$instance->setMaxRecursionDepth( 1 );

		$this->assertEquals(
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
			$instance->isCircularByRecursionFor( 'Foo' )
		);

		$instance->unmark( 'Foo' );

		$this->assertEquals(
			1,
			$instance->get( 'Foo' )
		);

		$this->assertFalse(
			$instance->isCircularByRecursionFor( 'Foo' )
		);

		$instance->unmark( 'notBeenMarkedBefore' );
	}

	/**
	 * @depends testRoundtripForRegisteredNamespace
	 */
	public function testVerifyRetainedReferenceFromPreviousInvocation() {

		$instance = new CircularReferenceGuard( 'bar' );

		$this->assertEquals(
			1,
			$instance->get( 'Foo' )
		);

		$instance->reset( 'bar' );

		$this->assertEquals(
			0,
			$instance->get( 'Foo' )
		);
	}

}

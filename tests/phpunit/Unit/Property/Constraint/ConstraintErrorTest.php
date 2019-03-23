<?php

namespace SMW\Tests\Property\Constraint;

use SMW\Property\Constraint\ConstraintError;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\Property\Constraint\ConstraintError
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.1
 *
 * @author mwjames
 */
class ConstraintErrorTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$this->assertInstanceOf(
			ConstraintError::class,
			new ConstraintError( 'Foo' )
		);

		$this->assertInstanceOf(
			'\SMW\ProcessingError',
			new ConstraintError( 'Foo' )
		);
	}

	public function testGetType() {

		$instance = new ConstraintError( 'Foo' );

		$this->assertSame(
			'constraint',
			$instance->getType()
		);
	}

	public function testGetHash() {

		$instance = new ConstraintError( 'Foo' );

		$this->assertSame(
			'8da752ae5dbd5bc3ec4485dae8534271',
			$instance->getHash()
		);
	}

	public function testEncode() {

		$instance = new ConstraintError( 'Foo' );

		$this->assertSame(
			'[2,"Foo"]',
			$instance->encode()
		);
	}

}

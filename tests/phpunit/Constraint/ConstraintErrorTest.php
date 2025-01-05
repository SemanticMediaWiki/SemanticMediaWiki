<?php

namespace SMW\Tests\Constraint;

use SMW\Constraint\ConstraintError;

/**
 * @covers \SMW\Constraint\ConstraintError
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.1
 *
 * @author mwjames
 */
class ConstraintErrorTest extends \PHPUnit\Framework\TestCase {

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

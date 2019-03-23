<?php

namespace SMW\Tests\Property\Constraint\Constraints;

use SMW\Property\Constraint\Constraints\NullConstraint;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\Property\Constraint\Constraints\NullConstraint
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.1
 *
 * @author mwjames
 */
class NullConstraintTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$this->assertInstanceOf(
			NullConstraint::class,
			new NullConstraint()
		);
	}

	public function testIsType() {

		$instance = new NullConstraint();

		$this->assertTrue(
			$instance->isType( NullConstraint::TYPE_INSTANT )
		);
	}

	public function testHasViolation() {

		$instance = new NullConstraint();

		$this->assertFalse(
			$instance->hasViolation()
		);
	}

}

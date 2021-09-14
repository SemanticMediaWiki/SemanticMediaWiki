<?php

namespace SMW\Tests\Constraint\Constraints;

use SMW\Constraint\Constraints\NullConstraint;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\Constraint\Constraints\NullConstraint
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

	public function testGetType() {

		$instance = new NullConstraint();

		$this->assertEquals(
			NullConstraint::TYPE_INSTANT,
			$instance->getType()
		);
	}

	public function testHasViolation() {

		$instance = new NullConstraint();

		$this->assertFalse(
			$instance->hasViolation()
		);
	}

}

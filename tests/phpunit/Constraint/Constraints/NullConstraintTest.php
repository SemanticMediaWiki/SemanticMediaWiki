<?php

namespace SMW\Tests\Constraint\Constraints;

use SMW\Constraint\Constraints\NullConstraint;

/**
 * @covers \SMW\Constraint\Constraints\NullConstraint
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.1
 *
 * @author mwjames
 */
class NullConstraintTest extends \PHPUnit\Framework\TestCase {

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

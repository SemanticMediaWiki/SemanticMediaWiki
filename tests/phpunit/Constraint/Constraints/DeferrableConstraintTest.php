<?php

namespace SMW\Tests\Constraint\Constraints;

use SMW\Constraint\Constraints\DeferrableConstraint;

/**
 * @covers \SMW\Constraint\Constraints\DeferrableConstraint
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.1
 *
 * @author mwjames
 */
class DeferrableConstraintTest extends \PHPUnit\Framework\TestCase {

	public function testCanConstruct() {
		$deferrableConstraint = $this->getMockBuilder( DeferrableConstraint::class )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$this->assertInstanceOf(
			DeferrableConstraint::class,
			$deferrableConstraint
		);
	}

	public function testTypeChangeOnCommandLine() {
		$deferrableConstraint = $this->getMockBuilder( DeferrableConstraint::class )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$deferrableConstraint->isCommandLineMode(
			false
		);

		$this->assertEquals(
			DeferrableConstraint::TYPE_DEFERRED,
			$deferrableConstraint->getType()
		);

		$deferrableConstraint->isCommandLineMode(
			true
		);

		$this->assertEquals(
			DeferrableConstraint::TYPE_INSTANT,
			$deferrableConstraint->getType()
		);
	}

}

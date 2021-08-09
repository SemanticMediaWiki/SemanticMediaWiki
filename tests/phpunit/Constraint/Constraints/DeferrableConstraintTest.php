<?php

namespace SMW\Tests\Constraint\Constraints;

use SMW\Constraint\Constraints\DeferrableConstraint;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\Constraint\Constraints\DeferrableConstraint
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.1
 *
 * @author mwjames
 */
class DeferrableConstraintTest extends \PHPUnit_Framework_TestCase {

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

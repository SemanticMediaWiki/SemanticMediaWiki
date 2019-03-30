<?php

namespace SMW\Tests\Property\Constraint\Constraints;

use SMW\Property\Constraint\Constraints\DeferrableConstraint;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\Property\Constraint\Constraints\DeferrableConstraint
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

		$this->assertTrue(
			$deferrableConstraint->isType( DeferrableConstraint::TYPE_DEFERRED )
		);

		$deferrableConstraint->isCommandLineMode(
			true
		);

		$this->assertTrue(
			$deferrableConstraint->isType( DeferrableConstraint::TYPE_INSTANT )
		);
	}

}

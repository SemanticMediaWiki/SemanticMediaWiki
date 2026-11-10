<?php

namespace SMW\Tests\Unit\Utils;

use PHPUnit\Framework\TestCase;
use SMW\Utils\ConceptExpansionGuard;

/**
 * @covers \SMW\Utils\ConceptExpansionGuard
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 7.4.1
 */
class ConceptExpansionGuardTest extends TestCase {

	public function testAcquireSucceedsUpToTheBudgetThenFails() {
		$guard = new ConceptExpansionGuard( 3 );

		$this->assertTrue( $guard->acquire() );
		$this->assertTrue( $guard->acquire() );
		$this->assertTrue( $guard->acquire() );
		$this->assertFalse( $guard->acquire() );
	}

	public function testSiblingExpansionsShareOneBudget() {
		// A child expansion that is released does not refund the budget for a
		// sibling: the fan-out as a whole is bounded, not each branch.
		$guard = new ConceptExpansionGuard( 2 );

		$this->assertTrue( $guard->acquire() );  // top-level
		$this->assertTrue( $guard->acquire() );  // nested child
		$guard->release();                       // child done, build still active

		$this->assertFalse( $guard->acquire() );
	}

	public function testBudgetResetsOnlyAfterTheBuildFullyUnwinds() {
		$guard = new ConceptExpansionGuard( 2 );

		$this->assertTrue( $guard->acquire() );
		$this->assertTrue( $guard->acquire() );
		$this->assertFalse( $guard->acquire() );

		$guard->release();
		$guard->release();

		// The previous build has fully unwound, so the next query starts fresh.
		$this->assertTrue( $guard->acquire() );
	}

}

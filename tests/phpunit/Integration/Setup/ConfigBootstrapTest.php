<?php

namespace SMW\Tests\Integration\Setup;

use PHPUnit\Framework\TestCase;
use SMW\Setup\ConfigBootstrap;

/**
 * @covers \SMW\Setup\ConfigBootstrap
 *
 * @license GPL-2.0-or-later
 * @since 7.0.0
 */
class ConfigBootstrapTest extends TestCase {

	public function testSeedComputedDefaultsIsNoOpWhenEmpty(): void {
		// This test must be replaced when seedComputedDefaults() gains a
		// body in a subsequent PR (feature-flag constants, then class
		// constants). The replacement should assert provide-default
		// semantics — a pre-set $GLOBALS key survives, an unset key is
		// populated — rather than full-array equality.
		$snapshot = $GLOBALS;

		ConfigBootstrap::seedComputedDefaults();

		$this->assertSame( $snapshot, $GLOBALS );
	}

}

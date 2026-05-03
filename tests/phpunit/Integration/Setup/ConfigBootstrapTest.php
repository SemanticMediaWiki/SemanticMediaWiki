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
		$snapshot = $GLOBALS;

		ConfigBootstrap::seedComputedDefaults();

		$this->assertSame( $snapshot, $GLOBALS );
	}

}

<?php

namespace SMW\Tests\Integration;

use PHPUnit\Framework\TestCase;

/**
 * Regression coverage for the partial-write bug class behind #6649 and
 * #6726: LocalSettings.php writes a single nested key into a compound
 * `$smwg*` array, and the rest of the defaults must survive.
 *
 * Each test case simulates the documented partial-write pattern, triggers
 * the extension config-resolution path, and asserts that both (a) the
 * user-set value won and (b) untouched defaults are still present.
 *
 * Cases are added per compound global as subsequent PRs migrate them with
 * appropriate merge_strategy declarations in extension.json.
 *
 * @group medium
 *
 * @license GPL-2.0-or-later
 * @since 7.0.0
 */
class ConfigPartialWriteTest extends TestCase {

	public function testFrameworkLoads(): void {
		// Real cases land in subsequent migration PRs. This placeholder
		// exists so PHPUnit discovers the class and CI exercises the file
		// before there are real cases to run.
		$this->assertTrue( true );
	}

}

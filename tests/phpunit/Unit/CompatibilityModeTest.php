<?php

namespace SMW\Tests;

use SMW\CompatibilityMode;
use SMW\Tests\PHPUnitCompat;

/**
 * @covers \SMW\CompatibilityMode
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.4
 *
 * @author mwjames
 */
class CompatibilityModeTest extends \PHPUnit_Framework_TestCase {

	use PHPUnitCompat;

	public function testExtensionNotEnabled() {

		$this->assertInternalType(
			'boolean',
			CompatibilityMode::extensionNotEnabled()
		);
	}

}

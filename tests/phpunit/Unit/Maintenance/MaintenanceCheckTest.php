<?php

namespace SMW\Tests\Unit\Maintenance;

use PHPUnit\Framework\TestCase;
use SMW\Maintenance\MaintenanceCheck;

/**
 * @covers \SMW\Maintenance\MaintenanceCheck
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.2
 *
 * @author mwjames
 */
class MaintenanceCheckTest extends TestCase {

	public function testCanConstruct() {
		$this->assertInstanceOf(
			MaintenanceCheck::class,
			new MaintenanceCheck()
		);
	}

	public function testCanExecute() {
		$instance = new MaintenanceCheck();

		$this->assertIsBool(

			$instance->canExecute()
		);
	}

	/**
	 * Regression test for issue #6715: setting `$smwgIgnoreUpgradeKeyCheck`
	 * must allow maintenance scripts to run even when the schema is in an
	 * intermediate state — otherwise scripts like `populateHashField.php`
	 * cannot be used to recover a stalled upgrade.
	 *
	 * Uses an anonymous subclass so the schema-validity check returns
	 * `false` deterministically — `SetupFile::isGoodSchema` short-circuits
	 * to `true` under `MW_PHPUNIT_TEST`, so without this override the test
	 * would pass even with the bypass missing.
	 */
	public function testCanExecute_HonorsIgnoreUpgradeKeyCheck() {
		$previous = $GLOBALS['smwgIgnoreUpgradeKeyCheck'] ?? false;
		$GLOBALS['smwgIgnoreUpgradeKeyCheck'] = true;

		try {
			$instance = new class extends MaintenanceCheck {
				protected function isSchemaValid(): bool {
					return false;
				}
			};

			$this->assertTrue( $instance->canExecute() );
			$this->assertSame( '', $instance->getMessage() );
		} finally {
			$GLOBALS['smwgIgnoreUpgradeKeyCheck'] = $previous;
		}
	}

	/**
	 * Without the bypass flag and with an invalid schema,
	 * `canExecute()` must refuse and emit the compatibility notice.
	 * This is the inverse of `testCanExecute_HonorsIgnoreUpgradeKeyCheck`
	 * and proves the bypass flag — not just the test override — is what
	 * lets the previous case succeed.
	 */
	public function testCanExecute_BlocksWhenSchemaInvalidAndFlagUnset() {
		$previous = $GLOBALS['smwgIgnoreUpgradeKeyCheck'] ?? false;
		$GLOBALS['smwgIgnoreUpgradeKeyCheck'] = false;

		try {
			$instance = new class extends MaintenanceCheck {
				protected function isSchemaValid(): bool {
					return false;
				}
			};

			$this->assertFalse( $instance->canExecute() );
			$this->assertStringContainsString(
				"setup of Semantic MediaWiki wasn't finalized",
				$instance->getMessage()
			);
		} finally {
			$GLOBALS['smwgIgnoreUpgradeKeyCheck'] = $previous;
		}
	}

	public function testGetMessage() {
		$instance = new MaintenanceCheck();

		$this->assertIsString(

			$instance->getMessage()
		);
	}

}

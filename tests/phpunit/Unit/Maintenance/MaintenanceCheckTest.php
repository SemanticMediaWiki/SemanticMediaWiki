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

	public function testGetMessage() {
		$instance = new MaintenanceCheck();

		$this->assertIsString(

			$instance->getMessage()
		);
	}

}

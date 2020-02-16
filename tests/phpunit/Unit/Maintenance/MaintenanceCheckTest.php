<?php

namespace SMW\Tests\Maintenance;

use SMW\Maintenance\MaintenanceCheck;
use SMW\Tests\PHPUnitCompat;

/**
 * @covers \SMW\Maintenance\MaintenanceCheck
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.2
 *
 * @author mwjames
 */
class MaintenanceCheckTest extends \PHPUnit_Framework_TestCase {

	use PHPUnitCompat;

	public function testCanConstruct() {

		$this->assertInstanceOf(
			'\SMW\Maintenance\MaintenanceCheck',
			new MaintenanceCheck()
		);
	}

	public function testCanExecute() {

		$instance = new MaintenanceCheck();

		$this->assertInternalType(
			'bool',
			$instance->canExecute()
		);
	}

	public function testGetMessage() {

		$instance = new MaintenanceCheck();

		$this->assertInternalType(
			'string',
			$instance->getMessage()
		);
	}

}

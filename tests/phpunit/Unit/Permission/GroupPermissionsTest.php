<?php

namespace SMW\Tests\Permission;

use SMW\Permission\GroupPermissions;
use SMW\Tests\PHPUnitCompat;

/**
 * @covers \SMW\Permission\GroupPermissions
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.2
 *
 * @author mwjames
 */
class GroupPermissionsTest extends \PHPUnit_Framework_TestCase {

	use PHPUnitCompat;

	public function testInitPermissions() {

		$vars = [];

		( new GroupPermissions() )->initPermissions( $vars );

		$this->assertArrayHasKey(
			'smwadministrator',
			$vars['wgGroupPermissions']
		);

		$this->assertArrayHasKey(
			'smwcurator',
			$vars['wgGroupPermissions']
		);
	}

}

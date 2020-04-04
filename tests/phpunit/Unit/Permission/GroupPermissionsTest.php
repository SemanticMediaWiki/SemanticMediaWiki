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

	private $hookDispatcher;

	protected function setUp() : void {
		parent::setUp();

		$this->hookDispatcher = $this->getMockBuilder( '\SMW\MediaWiki\HookDispatcher' )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testInitPermissions() {

		$this->hookDispatcher->expects( $this->once() )
			->method( 'onGroupPermissionsBeforeInitializationComplete' );

		$vars = [];

		$instance =  new GroupPermissions();

		$instance->setHookDispatcher(
			$this->hookDispatcher
		);

		$instance->initPermissions( $vars );

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

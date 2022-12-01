<?php

namespace SMW\Tests;

use SMW\GroupPermissions;
use SMW\Tests\PHPUnitCompat;

/**
 * @covers \SMW\GroupPermissions
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

		$newVars = $instance->initPermissions( $vars );

		$this->assertArrayHasKey(
			'smwadministrator',
			$newVars['wgGroupPermissions']
		);

		$this->assertArrayHasKey(
			'smwcurator',
			$newVars['wgGroupPermissions']
		);

		$this->assertArrayHasKey(
			'smweditor',
			$newVars['wgGroupPermissions']
		);

		$this->assertArrayHasKey(
			'user',
			$newVars['wgGroupPermissions']
		);
	}

	public function testNoResetOfAlreadyRegisteredGroupPermissions() {

		// Avoid re-setting permissions, refs #1137
		$vars['wgGroupPermissions']['sysop']['smw-admin'] = false;
		$vars['wgGroupPermissions']['smwadministrator']['smw-admin'] = false;

		$instance =  new GroupPermissions();

		$instance->setHookDispatcher(
			$this->hookDispatcher
		);

		$newVars = $instance->initPermissions( $vars );

		$this->assertFalse(
			$newVars['wgGroupPermissions']['sysop']['smw-admin']
		);

		$this->assertFalse(
			$newVars['wgGroupPermissions']['smwadministrator']['smw-admin']
		);

	}

}

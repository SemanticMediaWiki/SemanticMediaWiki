<?php

namespace SMW\Tests\MediaWiki;

use MediaWiki\Permissions\PermissionManager as MwPermissionManager;
use SMW\MediaWiki\PermissionManager;
use Title;
use User;

/**
 * @covers \SMW\MediaWiki\PermissionManager
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since   3.2
 *
 * @author mwjames
 */
class PermissionManagerTest extends \PHPUnit\Framework\TestCase {

	public function testUserCan_PermissionManager() {
		$title = $this->createMock( Title::class );

		$mwPermissionManager = $this->createMock( MwPermissionManager::class );
		$mwPermissionManager->expects( $this->any() )
			->method( 'userCan' )
			->with( 'foo', $this->isInstanceOf( User::class ), $title, MwPermissionManager::RIGOR_SECURE )
			->willReturn( true );

		$instance = new PermissionManager( $mwPermissionManager );

		$this->assertTrue( $instance->userCan( 'foo', null, $title ) );
	}

	public function testUserCan_PermissionManager_UserPassed() {
		$user = $this->createMock( User::class );
		$title = $this->createMock( Title::class );

		$mwPermissionManager = $this->createMock( MwPermissionManager::class );
		$mwPermissionManager->expects( $this->any() )
			->method( 'userCan' )
			->with( 'foo', $user, $title, MwPermissionManager::RIGOR_SECURE )
			->willReturn( true );

		$instance = new PermissionManager( $mwPermissionManager );

		$this->assertTrue( $instance->userCan( 'foo', $user, $title ) );
	}

	public function testUserCan_PermissionManager_CustomRigor() {
		$title = $this->createMock( Title::class );

		$mwPermissionManager = $this->createMock( MwPermissionManager::class );
		$mwPermissionManager->expects( $this->any() )
			->method( 'userCan' )
			->with( 'foo', $this->isInstanceOf( User::class ), $title, MwPermissionManager::RIGOR_QUICK )
			->willReturn( true );

		$instance = new PermissionManager( $mwPermissionManager );

		$this->assertTrue( $instance->userCan( 'foo', null, $title, MwPermissionManager::RIGOR_QUICK ) );
	}

	public function testUserHasRight_PermissionManager() {
		$user = $this->createMock( User::class );

		$mwPermissionManager = $this->createMock( MwPermissionManager::class );
		$mwPermissionManager->expects( $this->any() )
			->method( 'userHasRight' )
			->with( $user, 'foo' )
			->willReturn( true );

		$instance = new PermissionManager( $mwPermissionManager );

		$this->assertTrue( $instance->userHasRight( $user, 'foo' ) );
	}

}

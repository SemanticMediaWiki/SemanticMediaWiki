<?php

namespace SMW\Tests\MediaWiki\Permission;

use MediaWiki\User\User;
use PHPUnit\Framework\TestCase;
use SMW\MediaWiki\Permission\PermissionExaminer;
use SMW\MediaWiki\PermissionManager;

/**
 * @covers \SMW\MediaWiki\Permission\PermissionExaminer
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.2
 *
 * @author mwjames
 */
class PermissionExaminerTest extends TestCase {

	private $permissionManager;
	private $user;

	protected function setUp(): void {
		parent::setUp();

		$this->permissionManager = $this->getMockBuilder( PermissionManager::class )
			->disableOriginalConstructor()
			->getMock();

		$this->user = $this->getMockBuilder( User::class )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			PermissionExaminer::class,
			new PermissionExaminer( $this->permissionManager )
		);
	}

	public function testHasPermissionOf() {
		$this->permissionManager->expects( $this->any() )
			->method( 'userHasRight' )
			->with(
				$this->user,
				'foo' )
			->willReturn( false );

		$instance = new PermissionExaminer(
			$this->permissionManager
		);

		$instance->setUser( $this->user );

		$this->assertIsBool(

			$instance->hasPermissionOf( 'foo' )
		);
	}

}

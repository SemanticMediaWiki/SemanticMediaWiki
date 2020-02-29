<?php

namespace SMW\Tests\MediaWiki;

use ParserOutput;
use SMW\MediaWiki\PermissionExaminer;
use SMW\Tests\PHPUnitCompat;

/**
 * @covers \SMW\MediaWiki\PermissionExaminer
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since   3.2
 *
 * @author mwjames
 */
class PermissionExaminerTest extends \PHPUnit_Framework_TestCase {

	use PHPUnitCompat;

	public function testCanConstruct() {

		$this->assertInstanceOf(
			PermissionExaminer::class,
			new PermissionExaminer()
		);
	}

	public function testUserCan_Title() {

		if ( method_exists( 'MediaWiki\Permissions\PermissionManager', 'userCan' ) ) {
			$this->markTestSkipped( 'Using the PermissionManager::userCan' );
		}

		$title = $this->getMockBuilder( '\Title' )
			->disableOriginalConstructor()
			->getMock();

		$title->expects( $this->any() )
			->method(  'userCan' )
			->will( $this->returnValue( true ) );

		$instance = new PermissionExaminer();

		$this->assertInternalType(
			'bool',
			$instance->userCan( 'foo', null, $title )
		);
	}

	public function testUserCan_PermissionManager() {

		if ( !method_exists( 'MediaWiki\Permissions\PermissionManager', 'userCan' ) ) {
			$this->markTestSkipped( 'PermissionManager::userCan is unknown' );
		}

		$title = $this->getMockBuilder( '\Title' )
			->disableOriginalConstructor()
			->getMock();

		$permissionManager = $this->getMockBuilder( '\MediaWiki\Permissions\PermissionManager' )
			->disableOriginalConstructor()
			->getMock();

		$permissionManager->expects( $this->any() )
			->method(  'userCan' )
			->will( $this->returnValue( true ) );

		$instance = new PermissionExaminer(
			$permissionManager
		);

		$this->assertInternalType(
			'bool',
			$instance->userCan( 'foo', null, $title )
		);
	}

	public function testUserHasRight_User() {

		if ( method_exists( 'MediaWiki\Permissions\PermissionManager', 'userHasRight' ) ) {
			$this->markTestSkipped( 'Using PermissionManager::userHasRight' );
		}

		$user = $this->getMockBuilder( '\User' )
			->disableOriginalConstructor()
			->getMock();

		$user->expects( $this->any() )
			->method(  'isAllowed' )
			->will( $this->returnValue( true ) );

		$instance = new PermissionExaminer();

		$this->assertInternalType(
			'bool',
			$instance->userHasRight( $user, 'foo' )
		);
	}

	public function testUserHasRight_PermissionManager() {

		if ( !method_exists( 'MediaWiki\Permissions\PermissionManager', 'userHasRight' ) ) {
			$this->markTestSkipped( 'PermissionManager::userHasRight is unknown' );
		}

		$user = $this->getMockBuilder( '\User' )
			->disableOriginalConstructor()
			->getMock();

		$permissionManager = $this->getMockBuilder( '\MediaWiki\Permissions\PermissionManager' )
			->disableOriginalConstructor()
			->getMock();

		$permissionManager->expects( $this->any() )
			->method(  'userHasRight' )
			->will( $this->returnValue( true ) );

		$instance = new PermissionExaminer(
			$permissionManager
		);

		$this->assertInternalType(
			'bool',
			$instance->userHasRight( $user, 'foo' )
		);
	}

}

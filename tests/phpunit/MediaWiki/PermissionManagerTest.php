<?php

namespace SMW\Tests\MediaWiki;

use SMW\MediaWiki\PermissionManager;
use SMW\Tests\PHPUnitCompat;

/**
 * @covers \SMW\MediaWiki\PermissionManager
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since   3.2
 *
 * @author mwjames
 */
class PermissionManagerTest extends \PHPUnit_Framework_TestCase {

	use PHPUnitCompat;

	public function testCanConstruct() {
		$this->assertInstanceOf(
			PermissionManager::class,
			new PermissionManager( $this->getMwPermissionManagerMock() )
		);
	}

	public function testUserCan_PermissionManager() {
		$title = $this->getMockBuilder( '\Title' )
			->disableOriginalConstructor()
			->getMock();		

		$mwPermissionManager = $this->getMwPermissionManagerMock(
			[ 'userCan' => true ]
		);

		$instance = new PermissionManager( $mwPermissionManager );

		$this->assertInternalType(
			'bool',
			$instance->userCan( 'foo', null, $title )
		);
	}	

	public function testUserHasRight_PermissionManager() {
		$user = $this->getMockBuilder( '\User' )
			->disableOriginalConstructor()
			->getMock();

		$mwPermissionManager = $this->getMwPermissionManagerMock(
			[ 'userHasRight' => true ]
		);		

		$instance = new PermissionManager( $mwPermissionManager );

		$this->assertInternalType(
			'bool',
			$instance->userHasRight( $user, 'foo' )
		);
	}

	private function getMwPermissionManagerMock( array $methodMocks = [] ) {
		$permissionManager = $this->getMockBuilder( '\MediaWiki\Permissions\PermissionManager' )
			->disableOriginalConstructor()
			->getMock();

		foreach ( $methodMocks as $method => $returnValue ) {
			$permissionManager->expects( $this->any() )
				->method(  $method )
				->will( $this->returnValue( $returnValue ) );
		}

		return $permissionManager;
	}

}

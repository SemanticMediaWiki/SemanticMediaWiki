<?php

namespace SMW\Tests\MediaWiki\Permission;

use SMW\MediaWiki\Permission\PermissionExaminer;
use SMW\Tests\PHPUnitCompat;

/**
 * @covers \SMW\MediaWiki\Permission\PermissionExaminer
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.2
 *
 * @author mwjames
 */
class PermissionExaminerTest extends \PHPUnit_Framework_TestCase {

	use PHPUnitCompat;

	private $permissionManager;
	private $user;

	protected function setUp() : void {
		parent::setUp();

		$this->permissionManager = $this->getMockBuilder( '\SMW\MediaWiki\PermissionManager' )
			->disableOriginalConstructor()
			->getMock();

		$this->user = $this->getMockBuilder( '\User' )
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
				$this->equalTo( $this->user ),
				$this->equalTo( 'foo' ) )
			->will( $this->returnValue( false ) );

		$instance = new PermissionExaminer(
			$this->permissionManager
		);

		$instance->setUser( $this->user );

		$this->assertInternalType(
			'bool',
			$instance->hasPermissionOf( 'foo' )
		);
	}

}

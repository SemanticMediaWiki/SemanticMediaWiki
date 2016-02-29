<?php

namespace SMW\Tests;

use SMW\PermissionPthValidator;
use Title;

/**
 * @covers \SMW\PermissionPthValidator
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since  2.4
 *
 * @author mwjames
 */
class PermissionPthValidatorTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$this->assertInstanceOf(
			'\SMW\PermissionPthValidator',
			new PermissionPthValidator()
		);
	}

	public function testGrantPermissionToMainNamespace() {

		$title = Title::newFromText( 'Foo', NS_MAIN );

		$user = $this->getMockBuilder( '\User' )
			->disableOriginalConstructor()
			->getMock();

		$result = '';

		$instance = new PermissionPthValidator();

		$this->assertTrue(
			$instance->checkUserCanPermissionFor( $title, $user, 'edit', $result )
		);

		$this->assertEmpty(
			$result
		);
	}

	public function testToReturnFalseOnMwNamespaceEditPermissionCheckForInappropriatePermision() {

		$title = Title::newFromText( 'Smw_allows_pattern', NS_MEDIAWIKI );

		$user = $this->getMockBuilder( '\User' )
			->disableOriginalConstructor()
			->getMock();

		$user->expects( $this->once() )
			->method( 'isAllowed' )
			->with( $this->equalTo( 'smw-patternedit' ) )
			->will( $this->returnValue( false ) );

		$result = '';

		$instance = new PermissionPthValidator();

		$this->assertFalse(
			$instance->checkUserCanPermissionFor( $title, $user, 'edit', $result )
		);

		$this->assertFalse(
			$result
		);
	}

}

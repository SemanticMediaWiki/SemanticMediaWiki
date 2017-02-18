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

	private $editProtectionValidator;

	protected function setUp() {
		parent::setUp();

		$this->editProtectionValidator = $this->getMockBuilder( '\SMW\Protection\EditProtectionValidator' )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			'\SMW\PermissionPthValidator',
			new PermissionPthValidator( $this->editProtectionValidator  )
		);
	}

	public function testGrantPermissionToMainNamespace() {

		$title = Title::newFromText( 'Foo', NS_MAIN );

		$user = $this->getMockBuilder( '\User' )
			->disableOriginalConstructor()
			->getMock();

		$result = array();

		$instance = new PermissionPthValidator(
			$this->editProtectionValidator
		);

		$this->assertTrue(
			$instance->checkUserPermissionOn( $title, $user, 'edit', $result )
		);

		$this->assertEmpty(
			$result
		);
	}

	/**
	 * @dataProvider titleProvider
	 */
	public function testToReturnFalseOnMwNamespacePermissionCheck( $title, $permission, $action, $expected ) {

		$this->editProtectionValidator ->expects( $this->any() )
			->method( 'hasEditProtection' )
			->will( $this->returnValue( true ) );

		$this->editProtectionValidator ->expects( $this->any() )
			->method( 'hasProtectionOnNamespace' )
			->will( $this->returnValue( true ) );

		$user = $this->getMockBuilder( '\User' )
			->disableOriginalConstructor()
			->getMock();

		$user->expects( $this->once() )
			->method( 'isAllowed' )
			->with( $this->equalTo( $permission ) )
			->will( $this->returnValue( false ) );

		$result = array();

		$instance = new PermissionPthValidator(
			$this->editProtectionValidator
		);

		$this->assertFalse(
			$instance->checkUserPermissionOn( $title, $user, $action, $result )
		);

		$this->assertEquals(
			$expected,
			$result
		);
	}

	public function testToReturnFalseOnNamespaceWithEditPermissionCheck() {

		$editProtectionRight = 'Foo';

		$title = $this->getMockBuilder( '\Title' )
			->disableOriginalConstructor()
			->getMock();

		$title->expects( $this->any() )
			->method( 'getDBKey' )
			->will( $this->returnValue( 'PermissionTest' ) );

		$title->expects( $this->any() )
			->method( 'exists' )
			->will( $this->returnValue( true ) );

		$title->expects( $this->any() )
			->method( 'getNamespace' )
			->will( $this->returnValue( SMW_NS_PROPERTY ) );

		$this->editProtectionValidator->expects( $this->any() )
			->method( 'hasProtection' )
			->will( $this->returnValue( true ) );

		$this->editProtectionValidator->expects( $this->any() )
			->method( 'hasProtectionOnNamespace' )
			->will( $this->returnValue( true ) );

		$user = $this->getMockBuilder( '\User' )
			->disableOriginalConstructor()
			->getMock();

		$user->expects( $this->once() )
			->method( 'isAllowed' )
			->with( $this->equalTo( $editProtectionRight ) )
			->will( $this->returnValue( false ) );

		$result = array();

		$instance = new PermissionPthValidator(
			$this->editProtectionValidator
		);

		$instance->setEditProtectionRight(
			$editProtectionRight
		);

		$this->assertFalse(
			$instance->checkUserPermissionOn( $title, $user, 'edit', $result )
		);

		$this->assertEquals(
			array( array( 'smw-edit-protection', $editProtectionRight ) ),
			$result
		);
	}

	public function testFalseEditProtectionRightToNeverCheckPermissionOnNonMwNamespace() {

		$editProtectionRight = false;

		$title = $this->getMockBuilder( '\Title' )
			->disableOriginalConstructor()
			->getMock();

		$title->expects( $this->any() )
			->method( 'getDBKey' )
			->will( $this->returnValue( 'PermissionTest' ) );

		$title->expects( $this->any() )
			->method( 'exists' )
			->will( $this->returnValue( true ) );

		$title->expects( $this->any() )
			->method( 'getNamespace' )
			->will( $this->returnValue( SMW_NS_PROPERTY ) );

		$this->editProtectionValidator->expects( $this->never() )
			->method( 'hasProtectionOnNamespace' )
			->will( $this->returnValue( true ) );

		$user = $this->getMockBuilder( '\User' )
			->disableOriginalConstructor()
			->getMock();

		$result = '';

		$instance = new PermissionPthValidator(
			$this->editProtectionValidator
		);

		$instance->setEditProtectionRight(
			$editProtectionRight
		);

		$this->assertTrue(
			$instance->checkUserPermissionOn( $title, $user, 'edit', $result )
		);
	}

	public function titleProvider() {

		$provider[] = array(
			Title::newFromText( 'Smw_allows_pattern', NS_MEDIAWIKI ),
			'smw-patternedit',
			'edit',
			array( array( 'smw-patternedit-protection', 'smw-patternedit' ) )
		);

		$provider[] = array(
			Title::newFromText( 'Smw_allows_pattern', NS_MEDIAWIKI ),
			'smw-patternedit',
			'delete',
			array( array( 'smw-patternedit-protection', 'smw-patternedit' ) )
		);

		$provider[] = array(
			Title::newFromText( 'Smw_allows_pattern', NS_MEDIAWIKI ),
			'smw-patternedit',
			'move',
			array( array( 'smw-patternedit-protection', 'smw-patternedit' ) )
		);

		return $provider;
	}

}

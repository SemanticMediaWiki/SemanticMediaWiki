<?php

namespace SMW\Tests\MediaWiki\Permission;

use SMW\MediaWiki\Permission\TitlePermissions;
use Title;

/**
 * @covers \SMW\MediaWiki\Permission\TitlePermissions
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since  2.4
 *
 * @author mwjames
 */
class TitlePermissionsTest extends \PHPUnit_Framework_TestCase {

	private $protectionValidator;
	private $permissionManager;

	protected function setUp() : void {
		parent::setUp();

		$this->protectionValidator = $this->getMockBuilder( '\SMW\Protection\ProtectionValidator' )
			->disableOriginalConstructor()
			->getMock();

		$this->permissionManager = $this->getMockBuilder( '\SMW\MediaWiki\PermissionManager' )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			TitlePermissions::class,
			new TitlePermissions( $this->protectionValidator, $this->permissionManager )
		);
	}

	public function testGrantPermissionToMainNamespace() {

		$title = Title::newFromText( 'Foo', NS_MAIN );

		$user = $this->getMockBuilder( '\User' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new TitlePermissions(
			$this->protectionValidator,
			$this->permissionManager
		);

		$this->assertTrue(
			$instance->hasUserPermission( $title, $user, 'edit' )
		);

		$this->assertEmpty(
			$instance->getErrors()
		);
	}

	/**
	 * @dataProvider titleProvider
	 */
	public function testToReturnFalseOnMwNamespacePermissionCheck( $title, $permission, $action, $expected ) {

		$this->protectionValidator ->expects( $this->any() )
			->method( 'hasEditProtection' )
			->will( $this->returnValue( true ) );

		$user = $this->getMockBuilder( '\User' )
			->disableOriginalConstructor()
			->getMock();

		$this->permissionManager->expects( $this->once() )
			->method( 'userHasRight' )
			->with(
				$this->equalTo( $user ),
				$this->equalTo( $permission ) )
			->will( $this->returnValue( false ) );

		$result = [];

		$instance = new TitlePermissions(
			$this->protectionValidator,
			$this->permissionManager
		);

		$this->assertFalse(
			$instance->hasUserPermission( $title, $user, $action )
		);

		$this->assertEquals(
			$expected,
			$instance->getErrors()
		);
	}

	public function testNoUserPermissionOnNamespaceWithEditPermissionCheck() {

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

		$this->protectionValidator->expects( $this->any() )
			->method( 'hasProtection' )
			->will( $this->returnValue( true ) );

		$this->protectionValidator->expects( $this->any() )
			->method( 'getEditProtectionRight' )
			->will( $this->returnValue( $editProtectionRight ) );

		$this->protectionValidator->expects( $this->any() )
			->method( 'hasEditProtectionOnNamespace' )
			->will( $this->returnValue( true ) );

		$user = $this->getMockBuilder( '\User' )
			->disableOriginalConstructor()
			->getMock();

		$this->permissionManager->expects( $this->once() )
			->method( 'userHasRight' )
			->with(
				$this->equalTo( $user ),
				$this->equalTo( $editProtectionRight ) )
			->will( $this->returnValue( false ) );

		$instance = new TitlePermissions(
			$this->protectionValidator,
			$this->permissionManager
		);

		$this->assertFalse(
			$instance->hasUserPermission( $title, $user, 'edit' )
		);

		$this->assertEquals(
			[ [ 'smw-edit-protection', $editProtectionRight ] ],
			$instance->getErrors()
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
			->will( $this->returnValue( false ) );

		$title->expects( $this->any() )
			->method( 'getNamespace' )
			->will( $this->returnValue( SMW_NS_PROPERTY ) );

		$this->protectionValidator->expects( $this->any() )
			->method( 'getEditProtectionRight' )
			->will( $this->returnValue( $editProtectionRight ) );

		$user = $this->getMockBuilder( '\User' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new TitlePermissions(
			$this->protectionValidator,
			$this->permissionManager
		);

		$this->assertTrue(
			$instance->hasUserPermission( $title, $user, 'edit' )
		);
	}

	public function testNoUserPermissionOnPropertyNamespaceWithCreateProtectionCheck() {

		$createProtectionRight = 'Foo';

		$title = $this->getMockBuilder( '\Title' )
			->disableOriginalConstructor()
			->getMock();

		$title->expects( $this->any() )
			->method( 'getDBKey' )
			->will( $this->returnValue( 'PermissionTest' ) );

		$title->expects( $this->any() )
			->method( 'exists' )
			->will( $this->returnValue( false ) );

		$title->expects( $this->any() )
			->method( 'getNamespace' )
			->will( $this->returnValue( SMW_NS_PROPERTY ) );

		$this->protectionValidator->expects( $this->any() )
			->method( 'getCreateProtectionRight' )
			->will( $this->returnValue( $createProtectionRight ) );

		$user = $this->getMockBuilder( '\User' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new TitlePermissions(
			$this->protectionValidator,
			$this->permissionManager
		);

		$this->assertFalse(
			$instance->hasUserPermission( $title, $user, 'edit' )
		);

		$this->assertEquals(
			[ [ 'smw-create-protection', null, 'Foo' ] ],
			$instance->getErrors()
		);
	}

	public function testNoUserPermissionOnPropertyNamespaceWithCreateProtectionCheck_TitleExists() {

		$createProtectionRight = 'Foo';

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

		$this->protectionValidator->expects( $this->any() )
			->method( 'getCreateProtectionRight' )
			->will( $this->returnValue( $createProtectionRight ) );

		$user = $this->getMockBuilder( '\User' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new TitlePermissions(
			$this->protectionValidator,
			$this->permissionManager
		);

		$this->assertFalse(
			$instance->hasUserPermission( $title, $user, 'edit' )
		);

		$this->assertEquals(
			[ [ 'smw-create-protection-exists', null, 'Foo' ] ],
			$instance->getErrors()
		);
	}

	public function testNoUserPermissionOnCategoryNamespaceWithChangePropagationProtectionCheck() {

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
			->will( $this->returnValue( NS_CATEGORY ) );

		$this->protectionValidator->expects( $this->any() )
			->method( 'hasChangePropagationProtection' )
			->will( $this->returnValue( true ) );

		$user = $this->getMockBuilder( '\User' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new TitlePermissions(
			$this->protectionValidator,
			$this->permissionManager
		);

		$this->assertFalse(
			$instance->hasUserPermission( $title, $user, 'edit' )
		);

		$this->assertEquals(
			[
				[ 'smw-change-propagation-protection' ]
			],
			$instance->getErrors()
		);
	}

	public function testUserPermissionOnCategoryNamespaceWithChangePropagationProtectionCheck() {

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
			->will( $this->returnValue( NS_CATEGORY ) );

		$this->protectionValidator->expects( $this->any() )
			->method( 'hasChangePropagationProtection' )
			->will( $this->returnValue( false ) );

		$user = $this->getMockBuilder( '\User' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new TitlePermissions(
			$this->protectionValidator,
			$this->permissionManager
		);

		$this->assertTrue(
			$instance->hasUserPermission( $title, $user, 'edit' )
		);

		$this->assertEquals(
			[],
			$instance->getErrors()
		);
	}

	public function testNoUserEditPermissionOnMissingRight_SchemaNamespace() {

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
			->will( $this->returnValue( SMW_NS_SCHEMA ) );

		$user = $this->getMockBuilder( '\User' )
			->disableOriginalConstructor()
			->getMock();

		$this->permissionManager->expects( $this->once() )
			->method( 'userHasRight' )
			->with(
				$this->equalTo( $user ),
				$this->equalTo( 'smw-schemaedit' ) )
			->will( $this->returnValue( false ) );

		$instance = new TitlePermissions(
			$this->protectionValidator,
			$this->permissionManager
		);

		$this->assertFalse(
			$instance->hasUserPermission( $title, $user, 'edit' )
		);

		$this->assertEquals(
			[
				[ 'smw-schema-namespace-edit-protection', 'smw-schemaedit' ]
			],
			$instance->getErrors()
		);
	}

	public function testEditPermissionOnImportPerformer_SchemaNamespace() {

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
			->will( $this->returnValue( SMW_NS_SCHEMA ) );

		$user = $this->getMockBuilder( '\User' )
			->disableOriginalConstructor()
			->getMock();

		$this->permissionManager->expects( $this->once() )
			->method( 'userHasRight' )
			->with(
				$this->equalTo( $user ),
				$this->equalTo( 'smw-schemaedit' ) )
			->will( $this->returnValue( true ) );

		$this->protectionValidator->expects( $this->once() )
			->method( 'isClassifiedAsImportPerformerProtected' )
			->will( $this->returnValue( true ) );

		$instance = new TitlePermissions(
			$this->protectionValidator,
			$this->permissionManager
		);

		$this->assertFalse(
			$instance->hasUserPermission( $title, $user, 'edit' )
		);

		$this->assertEquals(
			[
				[ 'smw-schema-namespace-edit-protection-by-import-performer' ]
			],
			$instance->getErrors()
		);
	}

	public function testNoEditcontentmodelPermissionForAnyUser_SchemaNamespace() {

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
			->will( $this->returnValue( SMW_NS_SCHEMA ) );

		$user = $this->getMockBuilder( '\User' )
			->disableOriginalConstructor()
			->getMock();

		$this->permissionManager->expects( $this->once() )
			->method( 'userHasRight' )
			->with(
				$this->equalTo( $user ),
				$this->equalTo( 'smw-schemaedit' ) )
			->will( $this->returnValue( true ) );

		$instance = new TitlePermissions(
			$this->protectionValidator,
			$this->permissionManager
		);

		$this->assertFalse(
			$instance->hasUserPermission( $title, $user, 'editcontentmodel' )
		);

		$this->assertEquals(
			[
				[ 'smw-schema-namespace-editcontentmodel-disallowed' ]
			],
			$instance->getErrors()
		);
	}

	public function titleProvider() {

		$provider[] = [
			Title::newFromText( 'Smw_allows_pattern', NS_MEDIAWIKI ),
			'smw-patternedit',
			'edit',
			[ [ 'smw-patternedit-protection', 'smw-patternedit' ] ]
		];

		$provider[] = [
			Title::newFromText( 'Smw_allows_pattern', NS_MEDIAWIKI ),
			'smw-patternedit',
			'delete',
			[ [ 'smw-patternedit-protection', 'smw-patternedit' ] ]
		];

		$provider[] = [
			Title::newFromText( 'Smw_allows_pattern', NS_MEDIAWIKI ),
			'smw-patternedit',
			'move',
			[ [ 'smw-patternedit-protection', 'smw-patternedit' ] ]
		];

		return $provider;
	}

}

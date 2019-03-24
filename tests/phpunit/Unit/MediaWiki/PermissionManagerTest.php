<?php

namespace SMW\Tests\MediaWiki;

use SMW\MediaWiki\PermissionManager;
use Title;

/**
 * @covers \SMW\MediaWiki\PermissionManager
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since  2.4
 *
 * @author mwjames
 */
class PermissionManagerTest extends \PHPUnit_Framework_TestCase {

	private $protectionValidator;

	protected function setUp() {
		parent::setUp();

		$this->protectionValidator = $this->getMockBuilder( '\SMW\Protection\ProtectionValidator' )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			PermissionManager::class,
			new PermissionManager( $this->protectionValidator  )
		);
	}

	public function testGrantPermissionToMainNamespace() {

		$title = Title::newFromText( 'Foo', NS_MAIN );

		$user = $this->getMockBuilder( '\User' )
			->disableOriginalConstructor()
			->getMock();

		$result = [];

		$instance = new PermissionManager(
			$this->protectionValidator
		);

		$this->assertTrue(
			$instance->hasUserPermission( $title, $user, 'edit', $result )
		);

		$this->assertEmpty(
			$result
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

		$user->expects( $this->once() )
			->method( 'isAllowed' )
			->with( $this->equalTo( $permission ) )
			->will( $this->returnValue( false ) );

		$result = [];

		$instance = new PermissionManager(
			$this->protectionValidator
		);

		$this->assertFalse(
			$instance->hasUserPermission( $title, $user, $action, $result )
		);

		$this->assertEquals(
			$expected,
			$result
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

		$user->expects( $this->once() )
			->method( 'isAllowed' )
			->with( $this->equalTo( $editProtectionRight ) )
			->will( $this->returnValue( false ) );

		$result = [];

		$instance = new PermissionManager(
			$this->protectionValidator
		);

		$this->assertFalse(
			$instance->hasUserPermission( $title, $user, 'edit', $result )
		);

		$this->assertEquals(
			[ [ 'smw-edit-protection', $editProtectionRight ] ],
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

		$result = '';

		$instance = new PermissionManager(
			$this->protectionValidator
		);

		$this->assertTrue(
			$instance->hasUserPermission( $title, $user, 'edit', $result )
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

		$result = [];

		$instance = new PermissionManager(
			$this->protectionValidator
		);

		$this->assertFalse(
			$instance->hasUserPermission( $title, $user, 'edit', $result )
		);

		$this->assertEquals(
			[ [ 'smw-create-protection', null, 'Foo' ] ],
			$result
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

		$result = [];

		$instance = new PermissionManager(
			$this->protectionValidator
		);

		$this->assertFalse(
			$instance->hasUserPermission( $title, $user, 'edit', $result )
		);

		$this->assertEquals(
			[ [ 'smw-create-protection-exists', null, 'Foo' ] ],
			$result
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

		$result = [];

		$instance = new PermissionManager(
			$this->protectionValidator
		);

		$this->assertFalse(
			$instance->hasUserPermission( $title, $user, 'edit', $result )
		);

		$this->assertEquals(
			[
				[ 'smw-change-propagation-protection' ]
			],
			$result
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

		$result = [];

		$instance = new PermissionManager(
			$this->protectionValidator
		);

		$this->assertTrue(
			$instance->hasUserPermission( $title, $user, 'edit', $result )
		);

		$this->assertEquals(
			[],
			$result
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

		$user->expects( $this->once() )
			->method( 'isAllowed' )
			->with( $this->equalTo( 'smw-schemaedit' ) )
			->will( $this->returnValue( false ) );

		$result = [];

		$instance = new PermissionManager(
			$this->protectionValidator
		);

		$this->assertFalse(
			$instance->hasUserPermission( $title, $user, 'edit', $result )
		);

		$this->assertEquals(
			[
				[ 'smw-schema-namespace-edit-protection', 'smw-schemaedit' ]
			],
			$result
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

		$user->expects( $this->once() )
			->method( 'isAllowed' )
			->with( $this->equalTo( 'smw-schemaedit' ) )
			->will( $this->returnValue( true ) );

		$result = [];

		$instance = new PermissionManager(
			$this->protectionValidator
		);

		$this->assertFalse(
			$instance->hasUserPermission( $title, $user, 'editcontentmodel', $result )
		);

		$this->assertEquals(
			[
				[ 'smw-schema-namespace-editcontentmodel-disallowed' ]
			],
			$result
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

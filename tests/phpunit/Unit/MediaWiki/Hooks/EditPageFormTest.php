<?php

namespace SMW\Tests\Unit\MediaWiki\Hooks;

use MediaWiki\EditPage\EditPage;
use MediaWiki\Output\OutputPage;
use MediaWiki\Title\Title;
use MediaWiki\User\Options\UserOptionsLookup;
use MediaWiki\User\User;
use PHPUnit\Framework\TestCase;
use SMW\GroupPermissions;
use SMW\Localizer\MessageLocalizer;
use SMW\MediaWiki\Hooks\EditPageForm;
use SMW\MediaWiki\PermissionManager;
use SMW\NamespaceExaminer;
use SMW\Settings;

/**
 * @covers \SMW\MediaWiki\Hooks\EditPageForm
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 2.1
 *
 * @author mwjames
 */
class EditPageFormTest extends TestCase {

	private $namespaceExaminer;
	private $userOptionsLookup;
	private $settings;
	private $permissionManager;
	private $messageLocalizer;

	protected function setUp(): void {
		parent::setUp();

		$this->namespaceExaminer = $this->createMock( NamespaceExaminer::class );
		$this->userOptionsLookup = $this->createMock( UserOptionsLookup::class );
		$this->settings = $this->createMock( Settings::class );
		$this->permissionManager = $this->createMock( PermissionManager::class );
		$this->messageLocalizer = $this->createMock( MessageLocalizer::class );
	}

	private function newInstance(): EditPageForm {
		return new EditPageForm(
			$this->namespaceExaminer,
			$this->userOptionsLookup,
			$this->settings,
			$this->permissionManager
		);
	}

	private function newOutputFor( User $user ): OutputPage {
		$out = $this->createMock( OutputPage::class );
		$out->method( 'getUser' )->willReturn( $user );
		return $out;
	}

	public function testCanConstruct() {
		$this->assertInstanceOf( EditPageForm::class, $this->newInstance() );
	}

	public function testDisabledHelp() {
		$this->settings->method( 'get' )
			->with( 'smwgEnabledEditPageHelp' )
			->willReturn( false );

		$user = $this->createMock( User::class );
		$out = $this->newOutputFor( $user );

		$editPage = $this->createMock( EditPage::class );
		$editPage->editFormPageTop = '';

		$this->assertTrue(
			$this->newInstance()->onEditPage__showEditForm_initial( $editPage, $out )
		);

		$this->assertSame( '', $editPage->editFormPageTop );
	}

	public function testDisabledOnUserPreference() {
		$this->settings->method( 'get' )
			->with( 'smwgEnabledEditPageHelp' )
			->willReturn( true );

		$user = $this->createMock( User::class );

		$this->permissionManager->method( 'userHasRight' )
			->with( $user, GroupPermissions::VIEW_EDITPAGE_INFO )
			->willReturn( true );

		$this->userOptionsLookup->method( 'getOption' )
			->with( $user, 'smw-prefs-general-options-disable-editpage-info', false )
			->willReturn( true );

		$out = $this->newOutputFor( $user );

		$editPage = $this->createMock( EditPage::class );
		$editPage->editFormPageTop = '';

		$this->newInstance()->onEditPage__showEditForm_initial( $editPage, $out );

		$this->assertSame( '', $editPage->editFormPageTop );
	}

	/**
	 * @dataProvider titleProvider
	 */
	public function testExtendEditFormPageTop( $title, $namespaces, $isSemanticEnabled, $expected ) {
		$this->settings->method( 'get' )
			->with( 'smwgEnabledEditPageHelp' )
			->willReturn( true );

		$user = $this->createMock( User::class );

		$this->permissionManager->method( 'userHasRight' )
			->with( $user, GroupPermissions::VIEW_EDITPAGE_INFO )
			->willReturn( true );

		$this->userOptionsLookup->method( 'getOption' )
			->with( $user, 'smw-prefs-general-options-disable-editpage-info', false )
			->willReturn( false );

		$this->namespaceExaminer->method( 'isSemanticEnabled' )
			->with( $namespaces )
			->willReturn( $isSemanticEnabled );

		$out = $this->newOutputFor( $user );

		$editPage = $this->createMock( EditPage::class );
		$editPage->method( 'getTitle' )->willReturn( $title );
		$editPage->editFormPageTop = '';

		$instance = $this->newInstance();
		$instance->setMessageLocalizer( $this->messageLocalizer );

		$instance->onEditPage__showEditForm_initial( $editPage, $out );

		$this->assertStringContainsString( $expected, $editPage->editFormPageTop );
	}

	public function titleProvider() {
		return [
			[ Title::newFromText( 'Foo', SMW_NS_PROPERTY ), SMW_NS_PROPERTY, true, 'smw-editpage-property-annotation-enabled' ],
			[ Title::newFromText( 'Modification date', SMW_NS_PROPERTY ), SMW_NS_PROPERTY, true, 'smw-editpage-property-annotation-disabled' ],
			[ Title::newFromText( 'Foo', SMW_NS_CONCEPT ), SMW_NS_CONCEPT, true, 'smw-editpage-concept-annotation-enabled' ],
			[ Title::newFromText( 'Foo', NS_MAIN ), NS_MAIN, true, 'smw-editpage-annotation-enabled' ],
			[ Title::newFromText( 'Foo', NS_MAIN ), NS_MAIN, false, 'smw-editpage-annotation-disabled' ],
		];
	}

}

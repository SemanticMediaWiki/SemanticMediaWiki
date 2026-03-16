<?php

namespace SMW\Tests\MediaWiki\Hooks;

use MediaWiki\EditPage\EditPage;
use MediaWiki\Title\Title;
use PHPUnit\Framework\TestCase;
use SMW\Localizer\MessageLocalizer;
use SMW\MediaWiki\Hooks\EditPageForm;
use SMW\MediaWiki\Permission\PermissionExaminer;
use SMW\MediaWiki\Preference\PreferenceExaminer;
use SMW\NamespaceExaminer;

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
	private $permissionExaminer;
	private $preferenceExaminer;
	private $messageLocalizer;

	protected function setUp(): void {
		parent::setUp();

		$this->namespaceExaminer = $this->getMockBuilder( NamespaceExaminer::class )
			->disableOriginalConstructor()
			->getMock();

		$this->permissionExaminer = $this->getMockBuilder( PermissionExaminer::class )
			->disableOriginalConstructor()
			->getMock();

		$this->preferenceExaminer = $this->getMockBuilder( PreferenceExaminer::class )
			->disableOriginalConstructor()
			->getMock();

		$this->messageLocalizer = $this->getMockBuilder( MessageLocalizer::class )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			EditPageForm::class,
			new EditPageForm( $this->namespaceExaminer, $this->permissionExaminer, $this->preferenceExaminer )
		);
	}

	public function testDisabledHelp() {
		$editPage = $this->getMockBuilder( EditPage::class )
			->disableOriginalConstructor()
			->getMock();

		$instance = new EditPageForm(
			$this->namespaceExaminer,
			$this->permissionExaminer,
			$this->preferenceExaminer
		);

		$instance->setOptions(
			[
				'smwgEnabledEditPageHelp' => false
			]
		);

		$this->assertTrue(
			$instance->process( $editPage )
		);
	}

	public function testDisabledOnUserPreference() {
		$this->permissionExaminer->expects( $this->once() )
			->method( 'hasPermissionOf' )
			->willReturn( true );

		$this->preferenceExaminer->expects( $this->once() )
			->method( 'hasPreferenceOf' )
			->with( 'smw-prefs-general-options-disable-editpage-info' )
			->willReturn( true );

		$editPage = $this->getMockBuilder( EditPage::class )
			->disableOriginalConstructor()
			->getMock();

		$instance = new EditPageForm(
			$this->namespaceExaminer,
			$this->permissionExaminer,
			$this->preferenceExaminer
		);

		$instance->setOptions(
			[
				'smwgEnabledEditPageHelp' => true
			]
		);

		$editPage->editFormPageTop = '';

		$instance->process( $editPage );

		$this->assertEmpty(
			$editPage->editFormPageTop
		);
	}

	/**
	 * @dataProvider titleProvider
	 */
	public function testExtendEditFormPageTop( $title, $namespaces, $isSemanticEnabled, $expected ) {
		$this->permissionExaminer->expects( $this->once() )
			->method( 'hasPermissionOf' )
			->willReturn( true );

		$this->preferenceExaminer->expects( $this->once() )
			->method( 'hasPreferenceOf' )
			->with( 'smw-prefs-general-options-disable-editpage-info' )
			->willReturn( false );

		$this->namespaceExaminer->expects( $this->any() )
			->method( 'isSemanticEnabled' )
			->with( $namespaces )
			->willReturn( $isSemanticEnabled );

		$editPage = $this->getMockBuilder( EditPage::class )
			->disableOriginalConstructor()
			->getMock();

		$editPage->expects( $this->any() )
			->method( 'getTitle' )
			->willReturn( $title );

		$editPage->editFormPageTop = '';

		$instance = new EditPageForm(
			$this->namespaceExaminer,
			$this->permissionExaminer,
			$this->preferenceExaminer
		);

		$instance->setMessageLocalizer(
			$this->messageLocalizer
		);

		$instance->setOptions(
			[
				'smwgEnabledEditPageHelp' => true
			]
		);

		$instance->process( $editPage );

		$this->assertStringContainsString(
			$expected,
			$editPage->editFormPageTop
		);
	}

	public function titleProvider() {
		$provider[] = [
			Title::newFromText( 'Foo', SMW_NS_PROPERTY ),
			SMW_NS_PROPERTY,
			true,
			'smw-editpage-property-annotation-enabled'
		];

		$provider[] = [
			Title::newFromText( 'Modification date', SMW_NS_PROPERTY ),
			SMW_NS_PROPERTY,
			true,
			'smw-editpage-property-annotation-disabled'
		];

		$provider[] = [
			Title::newFromText( 'Foo', SMW_NS_CONCEPT ),
			SMW_NS_CONCEPT,
			true,
			'smw-editpage-concept-annotation-enabled'
		];

		$provider[] = [
			Title::newFromText( 'Foo', NS_MAIN ),
			NS_MAIN,
			true,
			'smw-editpage-annotation-enabled'
		];

		$provider[] = [
			Title::newFromText( 'Foo', NS_MAIN ),
			NS_MAIN,
			false,
			'smw-editpage-annotation-disabled'
		];

		return $provider;
	}

}

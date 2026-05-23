<?php

namespace SMW\Tests\Unit\MediaWiki\Hooks;

use MediaWiki\EditPage\EditPage;
use MediaWiki\Output\OutputPage;
use MediaWiki\User\Options\UserOptionsLookup;
use MediaWiki\User\User;
use PHPUnit\Framework\TestCase;
use SMW\MediaWiki\Hooks\EditPageForm;
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

	protected function setUp(): void {
		parent::setUp();

		$this->namespaceExaminer = $this->getMockBuilder( NamespaceExaminer::class )
			->disableOriginalConstructor()
			->getMock();

		$this->userOptionsLookup = $this->getMockBuilder( UserOptionsLookup::class )
			->disableOriginalConstructor()
			->getMock();

		$this->settings = $this->createMock( Settings::class );
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			EditPageForm::class,
			new EditPageForm( $this->namespaceExaminer, $this->userOptionsLookup, $this->settings )
		);
	}

	public function testDisabledHelp() {
		$this->settings->method( 'get' )
			->with( 'smwgEnabledEditPageHelp' )
			->willReturn( false );

		$user = $this->getMockBuilder( User::class )
			->disableOriginalConstructor()
			->getMock();

		$out = $this->getMockBuilder( OutputPage::class )
			->disableOriginalConstructor()
			->getMock();

		$out->expects( $this->any() )
			->method( 'getUser' )
			->willReturn( $user );

		$editPage = $this->getMockBuilder( EditPage::class )
			->disableOriginalConstructor()
			->getMock();

		$editPage->editFormPageTop = '';

		$instance = new EditPageForm(
			$this->namespaceExaminer,
			$this->userOptionsLookup,
			$this->settings
		);

		$this->assertTrue(
			$instance->onEditPage__showEditForm_initial( $editPage, $out )
		);

		$this->assertSame(
			'',
			$editPage->editFormPageTop
		);
	}

}

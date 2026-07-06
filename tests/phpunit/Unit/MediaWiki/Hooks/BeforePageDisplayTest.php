<?php

namespace SMW\Tests\Unit\MediaWiki\Hooks;

use MediaWiki\Context\RequestContext;
use MediaWiki\Output\OutputPage;
use MediaWiki\Request\WebRequest;
use MediaWiki\Title\Title;
use MediaWiki\User\Options\UserOptionsLookup;
use MediaWiki\User\User;
use PHPUnit\Framework\TestCase;
use Skin;
use SMW\MediaWiki\Hooks\BeforePageDisplay;
use SMW\Settings;
use SMW\SetupFile;

/**
 * @covers \SMW\MediaWiki\Hooks\BeforePageDisplay
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 1.9
 *
 * @author mwjames
 */
class BeforePageDisplayTest extends TestCase {

	private $outputPage;
	private $request;
	private $skin;
	private $title;
	private $settings;

	private UserOptionsLookup $userOptionsLookup;
	private SetupFile $setupFile;

	protected function setUp(): void {
		$this->title = $this->getMockBuilder( Title::class )
			->disableOriginalConstructor()
			->getMock();

		$this->request = $this->getMockBuilder( WebRequest::class )
			->disableOriginalConstructor()
			->getMock();

		$requestContext = $this->getMockBuilder( RequestContext::class )
			->disableOriginalConstructor()
			->getMock();

		$requestContext->expects( $this->any() )
			->method( 'getRequest' )
			->willReturn( $this->request );

		$this->outputPage = $this->getMockBuilder( OutputPage::class )
			->disableOriginalConstructor()
			->getMock();

		$this->outputPage->expects( $this->any() )
			->method( 'getTitle' )
			->willReturn( $this->title );

		$this->skin = $this->getMockBuilder( Skin::class )
			->disableOriginalConstructor()
			->getMock();

		$this->skin->expects( $this->any() )
			->method( 'getContext' )
			->willReturn( $requestContext );

		$this->userOptionsLookup = $this->createMock( UserOptionsLookup::class );
		$this->settings = $this->createMock( Settings::class );
		$this->setupFile = $this->createMock( SetupFile::class );
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			BeforePageDisplay::class,
			new BeforePageDisplay( $this->userOptionsLookup, $this->settings, $this->setupFile )
		);
	}

	public function testInformAboutExtensionAvailability() {
		if ( defined( 'SMW_EXTENSION_LOADED' ) ) {
			$this->markTestSkipped( 'SMW_EXTENSION_LOADED is defined globally' );
		}

		$this->title->expects( $this->once() )
			->method( 'isSpecial' )
			->with( 'Version' )
			->willReturn( true );

		$this->outputPage->expects( $this->once() )
			->method( 'prependHTML' );

		BeforePageDisplay::informAboutExtensionAvailability( $this->outputPage );
	}

	public function testIgnoreInformAboutExtensionAvailability() {
		$GLOBALS['smwgIgnoreExtensionRegistrationCheck'] = true;

		$this->outputPage->expects( $this->never() )
			->method( 'prependHTML' );

		BeforePageDisplay::informAboutExtensionAvailability( $this->outputPage );

		$GLOBALS['smwgIgnoreExtensionRegistrationCheck'] = false;
	}

	public function testModules() {
		$user = $this->getMockBuilder( User::class )
			->disableOriginalConstructor()
			->getMock();

		$this->userOptionsLookup->expects( $this->any() )
			->method( 'getOption' )
			->with( $user, 'smw-prefs-general-options-suggester-textinput' )
			->willReturn( true );

		$this->outputPage->expects( $this->once() )
			->method( 'addModules' );

		$this->outputPage->expects( $this->atLeastOnce() )
			->method( 'getUser' )
			->willReturn( $user );

		$instance = new BeforePageDisplay( $this->userOptionsLookup, $this->settings, $this->setupFile );

		$instance->onBeforePageDisplay( $this->outputPage, $this->skin );
	}

	/**
	 * @dataProvider titleDataProvider
	 */
	public function testProcess( $setup, $expected ) {
		$expected = $expected['result'] ? $this->atLeastOnce() : $this->never();

		$user = $this->getMockBuilder( User::class )
			->disableOriginalConstructor()
			->getMock();

		$this->outputPage = $this->getMockBuilder( OutputPage::class )
			->disableOriginalConstructor()
			->getMock();

		$this->outputPage->expects( $this->atLeastOnce() )
			->method( 'getUser' )
			->willReturn( $user );

		$this->outputPage->expects( $this->atLeastOnce() )
			->method( 'getTitle' )
			->willReturn( $setup['title'] );

		$this->outputPage->expects( $expected )
			->method( 'addLink' );

		$this->settings->method( 'get' )
			->with( 'smwgEnableExportRDFLink' )
			->willReturn( true );

		$instance = new BeforePageDisplay( $this->userOptionsLookup, $this->settings, $this->setupFile );

		$instance->onBeforePageDisplay( $this->outputPage, $this->skin );
	}

	public function titleDataProvider() {
		# 0 Standard title
		$title = $this->getMockBuilder( Title::class )
			->disableOriginalConstructor()
			->getMock();

		$title->expects( $this->atLeastOnce() )
			->method( 'getPrefixedText' )
			->willReturn( 'Foo' );

		$title->expects( $this->atLeastOnce() )
			->method( 'isSpecialPage' )
			->willReturn( false );

		$provider[] = [
			[
				'title'  => $title
			],
			[
				'result' => true
			]
		];

		# 1 as SpecialPage
		$title = $this->getMockBuilder( Title::class )
			->disableOriginalConstructor()
			->getMock();

		$title->expects( $this->atLeastOnce() )
			->method( 'isSpecialPage' )
			->willReturn( true );

		$provider[] = [
			[
				'title'  => $title
			],
			[
				'result' => false
			]
		];

		return $provider;
	}

}

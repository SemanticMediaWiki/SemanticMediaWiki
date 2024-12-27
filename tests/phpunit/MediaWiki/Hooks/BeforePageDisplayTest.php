<?php

namespace SMW\Tests\MediaWiki\Hooks;

use MediaWiki\User\UserOptionsLookup;
use SMW\MediaWiki\Hooks\BeforePageDisplay;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\MediaWiki\Hooks\BeforePageDisplay
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class BeforePageDisplayTest extends \PHPUnit\Framework\TestCase {

	private $outputPage;
	private $request;
	private $skin;
	private $title;

	private UserOptionsLookup $userOptionsLookup;
	private TestEnvironment $testEnvironment;

	protected function setUp(): void {
		$this->title = $this->getMockBuilder( '\Title' )
			->disableOriginalConstructor()
			->getMock();

		$this->request = $this->getMockBuilder( '\WebRequest' )
			->disableOriginalConstructor()
			->getMock();

		$requestContext = $this->getMockBuilder( '\RequestContext' )
			->disableOriginalConstructor()
			->getMock();

		$requestContext->expects( $this->any() )
			->method( 'getRequest' )
			->willReturn( $this->request );

		$this->outputPage = $this->getMockBuilder( '\OutputPage' )
			->disableOriginalConstructor()
			->getMock();

		$this->outputPage->expects( $this->any() )
			->method( 'getTitle' )
			->willReturn( $this->title );

		$this->skin = $this->getMockBuilder( '\Skin' )
			->disableOriginalConstructor()
			->getMock();

		$this->skin->expects( $this->any() )
			->method( 'getContext' )
			->willReturn( $requestContext );

		$this->userOptionsLookup = $this->createMock( UserOptionsLookup::class );
		$this->testEnvironment = new TestEnvironment();
		$this->testEnvironment->registerObject( 'UserOptionsLookup', $this->userOptionsLookup );
	}

	protected function tearDown(): void {
		$this->testEnvironment->tearDown();
		parent::tearDown();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			BeforePageDisplay::class,
			new BeforePageDisplay()
		);
	}

	public function testInformAboutExtensionAvailability() {
		$this->title->expects( $this->once() )
			->method( 'isSpecial' )
			->with( 'Version' )
			->willReturn( true );

		$this->outputPage->expects( $this->once() )
			->method( 'prependHTML' );

		$instance = new BeforePageDisplay();

		$instance->setOptions(
			[
				'SMW_EXTENSION_LOADED' => false
			]
		);

		$instance->informAboutExtensionAvailability( $this->outputPage );
	}

	public function testIgnoreInformAboutExtensionAvailability() {
		$this->outputPage->expects( $this->never() )
			->method( 'prependHTML' );

		$instance = new BeforePageDisplay();

		$instance->setOptions(
			[
				'SMW_EXTENSION_LOADED' => false,
				'smwgIgnoreExtensionRegistrationCheck' => true
			]
		);

		$instance->informAboutExtensionAvailability( $this->outputPage );
	}

	public function testModules() {
		$user = $this->getMockBuilder( '\User' )
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

		$instance = new BeforePageDisplay();

		$instance->process( $this->outputPage, $this->skin );
	}

	public function testPrependHTML_IncompleteTasks() {
		$user = $this->getMockBuilder( '\User' )
			->disableOriginalConstructor()
			->getMock();

		$this->outputPage->expects( $this->atLeastOnce() )
			->method( 'prependHTML' );

		$this->outputPage->expects( $this->atLeastOnce() )
			->method( 'getUser' )
			->willReturn( $user );

		$instance = new BeforePageDisplay();

		$instance->setOptions(
			[
				'incomplete_tasks' => [ 'Foo', 'Bar' ]
			]
		);

		$instance->process( $this->outputPage, $this->skin );
	}

	public function testEmptyPrependHTML_IncompleteTasks_DisallowedSpecialPages() {
		$user = $this->getMockBuilder( '\User' )
			->disableOriginalConstructor()
			->getMock();

		$this->title->expects( $this->any() )
			->method( 'isSpecialPage' )
			->willReturn( true );

		$this->title->expects( $this->any() )
			->method( 'isSpecial' )
			->with( 'Userlogin' )
			->willReturn( true );

		$this->outputPage->expects( $this->once() )
			->method( 'prependHTML' )
			->with( '' );

		$this->outputPage->expects( $this->atLeastOnce() )
			->method( 'getUser' )
			->willReturn( $user );

		$instance = new BeforePageDisplay();

		$instance->setOptions(
			[
				'incomplete_tasks' => [ 'Foo', 'Bar' ]
			]
		);

		$instance->process( $this->outputPage, $this->skin );
	}

	/**
	 * @dataProvider titleDataProvider
	 */
	public function testProcess( $setup, $expected ) {
		$expected = $expected['result'] ? $this->atLeastOnce() : $this->never();

		$user = $this->getMockBuilder( '\User' )
			->disableOriginalConstructor()
			->getMock();

		$this->outputPage = $this->getMockBuilder( '\OutputPage' )
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

		$instance = new BeforePageDisplay();

		$instance->setOptions(
			[
				'smwgEnableExportRDFLink' => true
			]
		);

		$this->assertTrue(
			$instance->process( $this->outputPage, $this->skin )
		);
	}

	public function titleDataProvider() {
		# 0 Standard title
		$title = $this->getMockBuilder( '\Title' )
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
		$title = $this->getMockBuilder( '\Title' )
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

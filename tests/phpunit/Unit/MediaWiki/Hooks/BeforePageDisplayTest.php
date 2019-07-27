<?php

namespace SMW\Tests\MediaWiki\Hooks;

use SMW\MediaWiki\Hooks\BeforePageDisplay;

/**
 * @covers \SMW\MediaWiki\Hooks\BeforePageDisplay
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class BeforePageDisplayTest extends \PHPUnit_Framework_TestCase {

	private $outputPage;
	private $request;
	private $skin;
	private $title;

	protected function setUp() {

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
			->will( $this->returnValue( $this->request ) );

		$this->outputPage = $this->getMockBuilder( '\OutputPage' )
			->disableOriginalConstructor()
			->getMock();

		$this->outputPage->expects( $this->any() )
			->method( 'getTitle' )
			->will( $this->returnValue( $this->title ) );

		$this->skin = $this->getMockBuilder( '\Skin' )
			->disableOriginalConstructor()
			->getMock();

		$this->skin->expects( $this->any() )
			->method( 'getContext' )
			->will( $this->returnValue( $requestContext ) );
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
			->with( $this->equalTo( 'Version' ) )
			->will( $this->returnValue( true ) );

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

	public function testModules() {

		$user = $this->getMockBuilder( '\User' )
			->disableOriginalConstructor()
			->getMock();

		$user->expects( $this->any() )
			->method( 'getOption' )
			->with( $this->equalTo( 'smw-prefs-general-options-suggester-textinput' ) )
			->will( $this->returnValue( true ) );

		$this->outputPage->expects( $this->exactly( 1 ) )
			->method( 'addModules' );

		$this->outputPage->expects( $this->atLeastOnce() )
			->method( 'getUser' )
			->will( $this->returnValue( $user ) );

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
			->will( $this->returnValue( $user ) );

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
			->will( $this->returnValue( $user ) );

		$this->outputPage->expects( $this->atLeastOnce() )
			->method( 'getTitle' )
			->will( $this->returnValue( $setup['title'] ) );

		$this->outputPage->expects( $expected )
			->method( 'addLink' );

		$instance = new BeforePageDisplay();

		$this->assertTrue(
			$instance->process( $this->outputPage, $this->skin )
		);
	}

	public function titleDataProvider() {

		#0 Standard title
		$title = $this->getMockBuilder( '\Title' )
			->disableOriginalConstructor()
			->getMock();

		$title->expects( $this->atLeastOnce() )
			->method( 'getPrefixedText' )
			->will( $this->returnValue( 'Foo' ) );

		$title->expects( $this->atLeastOnce() )
			->method( 'isSpecialPage' )
			->will( $this->returnValue( false ) );

		$provider[] = [
			[
				'title'  => $title
			],
			[
				'result' => true
			]
		];

		#1 as SpecialPage
		$title = $this->getMockBuilder( '\Title' )
			->disableOriginalConstructor()
			->getMock();

		$title->expects( $this->atLeastOnce() )
			->method( 'isSpecialPage' )
			->will( $this->returnValue( true ) );

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

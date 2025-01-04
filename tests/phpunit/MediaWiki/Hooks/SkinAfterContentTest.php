<?php

namespace SMW\Tests\MediaWiki\Hooks;

use SMW\Factbox\FactboxText;
use SMW\Services\ServicesFactory as ApplicationFactory;
use SMW\MediaWiki\Hooks\SkinAfterContent;
use SMW\Settings;
use SMW\Tests\Utils\Mock\MockTitle;

/**
 * @covers \SMW\MediaWiki\Hooks\SkinAfterContent
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class SkinAfterContentTest extends \PHPUnit\Framework\TestCase {

	private $applicationFactory;
	private FactboxText $factboxText;

	protected function setUp(): void {
		parent::setUp();

		$this->applicationFactory = ApplicationFactory::getInstance();

		$settings = Settings::newFromArray( [
			'smwgFactboxFeatures'  => SMW_FACTBOX_CACHE,
			'smwgMainCacheType'        => 'hash',
			'smwgShowFactboxEdit' => false,
			'smwgShowFactbox' => false
		] );

		$this->applicationFactory->registerObject( 'Settings', $settings );

		$this->factboxText = $this->applicationFactory->getFactboxText();
	}

	protected function tearDown(): void {
		$this->applicationFactory->clear();

		parent::tearDown();
	}

	public function testCanConstruct() {
		$skin = $this->getMockBuilder( '\Skin' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			'\SMW\MediaWiki\Hooks\SkinAfterContent',
			new SkinAfterContent( $skin )
		);
	}

	public function testTryToPerformUpdateOnNullSkin() {
		$data = '';
		$instance = new SkinAfterContent( null );

		$instance->setOption( 'SMW_EXTENSION_LOADED', true );

		$this->assertTrue(
			$instance->performUpdate( $data )
		);
	}

	/**
	 * @dataProvider outputDataProvider
	 */
	public function testperformUpdateFactboxPresenterIntegration( $parameters, $expected ) {
		$data = '';

		$this->factboxText->setText( $parameters['text'] );

		$instance = new SkinAfterContent( $parameters['skin'] );

		$instance->setOption( 'SMW_EXTENSION_LOADED', true );

		// Replace CachedFactbox instance
		if ( isset( $parameters['title'] ) ) {

			$cachedFactbox = $this->applicationFactory->create( 'FactboxFactory' )->newCachedFactbox();

			$cachedFactbox->addContentToCache(
				$cachedFactbox->makeCacheKey( $parameters['title'] ),
				$parameters['text']
			);

			$factboxFactory = $this->getMockBuilder( '\SMW\Factbox\FactboxFactory' )
				->disableOriginalConstructor()
				->getMock();

			$factboxFactory->expects( $this->once() )
				->method( 'newCachedFactbox' )
				->willReturn( $cachedFactbox );

			$this->applicationFactory->registerObject( 'FactboxFactory', $factboxFactory );
		}

		$this->assertTrue(
			$instance->performUpdate( $data )
		);

		$this->assertEquals(
			$expected['text'],
			$data
		);
	}

	public function outputDataProvider() {
		$text = __METHOD__ . 'text-0';

		# 0 Retrieve content from outputPage property
		$title = MockTitle::buildMock( __METHOD__ . 'from-property' );

		$title->expects( $this->atLeastOnce() )
			->method( 'exists' )
			->willReturn( true );

		$title->expects( $this->atLeastOnce() )
			->method( 'getArticleID' )
			->willReturn( 10001 );

		$outputPage = $this->getMockBuilder( '\OutputPage' )
			->disableOriginalConstructor()
			->getMock();

		$outputPage->expects( $this->any() )
			->method( 'getTitle' )
			->willReturn( $title );

		$skin = $this->getMockBuilder( '\Skin' )
			->disableOriginalConstructor()
			->getMock();

		$skin->expects( $this->any() )
			->method( 'getTitle' )
			->willReturn( null );

		$skin->expects( $this->atLeastOnce() )
			->method( 'getOutput' )
			->willReturn( $outputPage );

		$requestContext = new \RequestContext();
		$requestContext->setLanguage( 'en' );

		$skin->expects( $this->atLeastOnce() )
			->method( 'getContext' )
			->willReturn( $requestContext );

		$provider[] = [
			[ 'skin' => $skin, 'text' => $text ],
			[ 'text' => $text ]
		];

		# 1 Retrieve content from cache
		$title = MockTitle::buildMock( __METHOD__ . 'from-cache' );

		$title->expects( $this->atLeastOnce() )
			->method( 'exists' )
			->willReturn( true );

		$title->expects( $this->atLeastOnce() )
			->method( 'getArticleID' )
			->willReturn( 10002 );

		$outputPage = $this->getMockBuilder( '\OutputPage' )
			->disableOriginalConstructor()
			->getMock();

		$outputPage->expects( $this->atLeastOnce() )
			->method( 'getTitle' )
			->willReturn( $title );

		$requestContext = new \RequestContext();
		$requestContext->setLanguage( 'en' );

		$outputPage->expects( $this->atLeastOnce() )
			->method( 'getContext' )
			->willReturn( $requestContext );

		$text = __METHOD__ . 'text-1';

		$skin = $this->getMockBuilder( '\Skin' )
			->disableOriginalConstructor()
			->getMock();

		$skin->expects( $this->any() )
			->method( 'getTitle' )
			->willReturn( $title );

		$skin->expects( $this->atLeastOnce() )
			->method( 'getOutput' )
			->willReturn( $outputPage );

		$skin->expects( $this->atLeastOnce() )
			->method( 'getContext' )
			->willReturn( $requestContext );

		$provider[] = [
			[ 'skin' => $skin, 'text' => $text, 'title' => $outputPage->getTitle() ],
			[ 'text' => $text ]
		];

		// #2 Special page
		$text  = __METHOD__ . 'text-2';

		$title = MockTitle::buildMock( __METHOD__ . 'specialpage' );

		$title->expects( $this->atLeastOnce() )
			->method( 'isSpecialPage' )
			->willReturn( true );

		$outputPage = $this->getMockBuilder( '\OutputPage' )
			->disableOriginalConstructor()
			->getMock();

		$outputPage->expects( $this->atLeastOnce() )
			->method( 'getTitle' )
			->willReturn( $title );

		$skin = $this->getMockBuilder( '\Skin' )
			->disableOriginalConstructor()
			->getMock();

		$skin->expects( $this->any() )
			->method( 'getTitle' )
			->willReturn( $title );

		$skin->expects( $this->atLeastOnce() )
			->method( 'getOutput' )
			->willReturn( $outputPage );

		$skin->expects( $this->atLeastOnce() )
			->method( 'getContext' )
			->willReturn( new \RequestContext() );

		$provider[] = [
			[ 'skin' => $skin, 'text' => $text ],
			[ 'text' => '' ]
		];

		// #3 "edit" request
		$text   = __METHOD__ . 'text-3';

		$title = MockTitle::buildMock( __METHOD__ . 'edit-request' );

		$title->expects( $this->atLeastOnce() )
			->method( 'exists' )
			->willReturn( true );

		$title->expects( $this->atLeastOnce() )
			->method( 'getArticleID' )
			->willReturn( 10003 );

		$outputPage = $this->getMockBuilder( '\OutputPage' )
			->disableOriginalConstructor()
			->getMock();

		$outputPage->expects( $this->atLeastOnce() )
			->method( 'getTitle' )
			->willReturn( $title );

		$skin = $this->getMockBuilder( '\Skin' )
			->disableOriginalConstructor()
			->getMock();

		$skin->expects( $this->any() )
			->method( 'getTitle' )
			->willReturn( $title );

		$skin->expects( $this->atLeastOnce() )
			->method( 'getOutput' )
			->willReturn( $outputPage );

		$context = new \RequestContext();
		$context->setRequest( new \FauxRequest( [ 'action' => 'edit' ], true ) );

		$skin->expects( $this->atLeastOnce() )
			->method( 'getContext' )
			->willReturn( $context );

		$provider[] = [
			[ 'skin' => $skin, 'text' => $text ],
			[ 'text' => $text ]
		];

		// #4 "delete" request
		$text   = __METHOD__ . 'text-4';

		$title = MockTitle::buildMock( __METHOD__ . 'delete-request' );

		$outputPage = $this->getMockBuilder( '\OutputPage' )
			->disableOriginalConstructor()
			->getMock();

		$outputPage->expects( $this->atLeastOnce() )
			->method( 'getTitle' )
			->willReturn( $title );

		$skin = $this->getMockBuilder( '\Skin' )
			->disableOriginalConstructor()
			->getMock();

		$skin->expects( $this->any() )
			->method( 'getTitle' )
			->willReturn( $title );

		$skin->expects( $this->any() )
			->method( 'getOutput' )
			->willReturn( $outputPage );

		$context = new \RequestContext();
		$context->setRequest( new \FauxRequest( [ 'action' => 'delete' ], true ) );

		$skin->expects( $this->atLeastOnce() )
			->method( 'getContext' )
			->willReturn( $context );

		$provider[] = [
			[ 'skin' => $skin, 'text' => $text ],
			[ 'text' => '' ]
		];

		// #5 "purge" request
		$text   = __METHOD__ . 'text-purge';

		$title = MockTitle::buildMock( __METHOD__ . 'purge-request' );

		$outputPage = $this->getMockBuilder( '\OutputPage' )
			->disableOriginalConstructor()
			->getMock();

		$outputPage->expects( $this->atLeastOnce() )
			->method( 'getTitle' )
			->willReturn( $title );

		$skin = $this->getMockBuilder( '\Skin' )
			->disableOriginalConstructor()
			->getMock();

		$skin->expects( $this->any() )
			->method( 'getTitle' )
			->willReturn( $title );

		$skin->expects( $this->any() )
			->method( 'getOutput' )
			->willReturn( $outputPage );

		$context = new \RequestContext();
		$context->setRequest( new \FauxRequest( [ 'action' => 'purge' ], true ) );

		$skin->expects( $this->atLeastOnce() )
			->method( 'getContext' )
			->willReturn( $context );

		$provider[] = [
			[ 'skin' => $skin, 'text' => $text ],
			[ 'text' => '' ]
		];

		return $provider;
	}

}

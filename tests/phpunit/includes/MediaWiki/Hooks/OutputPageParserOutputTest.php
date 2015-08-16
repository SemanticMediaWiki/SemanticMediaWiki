<?php

namespace SMW\Tests\MediaWiki\Hooks;

use SMW\Tests\Utils\Mock\MockTitle;

use SMW\MediaWiki\Hooks\OutputPageParserOutput;

use SMW\Settings;
use SMW\ApplicationFactory;
use SMW\DIWikiPage;
use SMW\DIProperty;

use ParserOutput;
use Language;

/**
 * @covers \SMW\MediaWiki\Hooks\OutputPageParserOutput
 * @group semantic-mediawiki
 * @group medium
 *
 * @license GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class OutputPageParserOutputTest extends \PHPUnit_Framework_TestCase {

	private $applicationFactory;

	protected function setUp() {
		parent::setUp();

		$this->applicationFactory = ApplicationFactory::getInstance();

		$settings = array(
			'smwgShowFactbox'      => SMW_FACTBOX_NONEMPTY,
			'smwgFactboxUseCache'  => true,
			'smwgCacheType'        => 'hash',
			'smwgLinksInValues'    => false,
			'smwgInlineErrors'     => true,
		);

		foreach ( $settings as $key => $value ) {
			$this->applicationFactory->getSettings()->set( $key, $value );
		}
	}

	protected function tearDown() {
		$this->applicationFactory->clear();

		parent::tearDown();
	}

	public function testCanConstruct() {

		$outputPage = $this->getMockBuilder( '\OutputPage' )
			->disableOriginalConstructor()
			->getMock();

		$parserOutput = $this->getMockBuilder( '\ParserOutput' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			'\SMW\MediaWiki\Hooks\OutputPageParserOutput',
			new OutputPageParserOutput( $outputPage, $parserOutput )
		);
	}

	/**
	 * @dataProvider outputDataProvider
	 */
	public function testProcess( $parameters, $expected ) {

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$this->applicationFactory->registerObject( 'Store', $store );

		$this->applicationFactory->getSettings()->set(
			'smwgNamespacesWithSemanticLinks',
			$parameters['smwgNamespacesWithSemanticLinks']
		);

		$outputPage   = $parameters['outputPage'];
		$parserOutput = $parameters['parserOutput'];

		$instance = new OutputPageParserOutput( $outputPage, $parserOutput );

		$cachedFactbox = $this->applicationFactory->newFactboxFactory()->newCachedFactbox();

		$factboxFactory = $this->getMockBuilder( '\SMW\Factbox\FactboxFactory' )
			->disableOriginalConstructor()
			->setMethods( array( 'newCachedFactbox' ) )
			->getMock();

		$factboxFactory->expects( $this->any() )
			->method( 'newCachedFactbox' )
			->will( $this->returnValue( $cachedFactbox ) );

		$this->applicationFactory->registerObject( 'FactboxFactory', $factboxFactory );

		$this->assertEmpty(
			$cachedFactbox->retrieveContent( $outputPage )
		);

		$instance->process();

		if ( $expected['text'] == '' ) {
			return $this->assertFalse( isset( $outputPage->mSMWFactboxText ) );
		}

		// For expected content continue to verify that the outputPage was amended and
		// that the content is also available via the CacheStore
		$text = $outputPage->mSMWFactboxText;

		$this->assertContains( $expected['text'], $text );

		$this->assertEquals(
			$text,
			$cachedFactbox->retrieveContent( $outputPage ),
			'Asserts that retrieveContent() returns an expected text'
		);

		// Deliberately clear the outputPage Property to retrieve
		// content from the CacheStore
		unset( $outputPage->mSMWFactboxText );

		$this->assertEquals(
			$text,
			$cachedFactbox->retrieveContent( $outputPage ),
			'Asserts that retrieveContent() is returning text from cache'
		);
	}

	public function outputDataProvider() {

		$language = Language::factory( 'en' );

		$title = MockTitle::buildMockForMainNamespace( __METHOD__ . 'mock-subject' );

		$title->expects( $this->atLeastOnce() )
			->method( 'exists' )
			->will( $this->returnValue( true ) );

		$subject = DIWikiPage::newFromTitle( $title );

		$semanticData = $this->getMockBuilder( '\SMW\SemanticData' )
			->disableOriginalConstructor()
			->getMock();

		$semanticData->expects( $this->atLeastOnce() )
			->method( 'getSubject' )
			->will( $this->returnValue( $subject ) );

		$semanticData->expects( $this->atLeastOnce() )
			->method( 'hasVisibleProperties' )
			->will( $this->returnValue( true ) );

		$semanticData->expects( $this->atLeastOnce() )
			->method( 'getPropertyValues' )
			->will( $this->returnValue( array( DIWikiPage::newFromTitle( $title ) ) ) );

		$semanticData->expects( $this->atLeastOnce() )
			->method( 'getProperties' )
			->will( $this->returnValue( array( new DIProperty(  __METHOD__ . 'property' ) ) ) );

		#0 Simple factbox build, returning content
		$title = MockTitle::buildMock( __METHOD__ . 'title-with-content' );

		$title->expects( $this->atLeastOnce() )
			->method( 'exists' )
			->will( $this->returnValue( true ) );

		$title->expects( $this->atLeastOnce() )
			->method( 'getNamespace' )
			->will( $this->returnValue( NS_MAIN ) );

		$title->expects( $this->atLeastOnce() )
			->method( 'getPageLanguage' )
			->will( $this->returnValue( $language ) );

		$title->expects( $this->atLeastOnce() )
			->method( 'getArticleID' )
			->will( $this->returnValue( 9098 ) );

		$outputPage = $this->getMockBuilder( '\OutputPage' )
			->disableOriginalConstructor()
			->getMock();

		$outputPage->expects( $this->atLeastOnce() )
			->method( 'getTitle' )
			->will( $this->returnValue( $title ) );

		$outputPage->expects( $this->atLeastOnce() )
			->method( 'getContext' )
			->will( $this->returnValue( new \RequestContext() ) );

		$provider[] = array(
			array(
				'smwgNamespacesWithSemanticLinks' => array( NS_MAIN => true ),
				'outputPage'   => $outputPage,
				'parserOutput' => $this->makeParserOutput( $semanticData ),
			),
			array(
				'text'         => $subject->getDBKey()
			)
		);

		#1 Disabled namespace, no return value expected
		$title = MockTitle::buildMock( __METHOD__ . 'title-ns-disabled' );

		$title->expects( $this->atLeastOnce() )
			->method( 'getNamespace' )
			->will( $this->returnValue( NS_MAIN ) );

		$title->expects( $this->atLeastOnce() )
			->method( 'getPageLanguage' )
			->will( $this->returnValue( $language ) );

		$title->expects( $this->atLeastOnce() )
			->method( 'getArticleID' )
			->will( $this->returnValue( 90000 ) );

		$outputPage = $this->getMockBuilder( '\OutputPage' )
			->disableOriginalConstructor()
			->getMock();

		$outputPage->expects( $this->atLeastOnce() )
			->method( 'getTitle' )
			->will( $this->returnValue( $title ) );

		$outputPage->expects( $this->atLeastOnce() )
			->method( 'getContext' )
			->will( $this->returnValue( new \RequestContext() ) );

		$provider[] = array(
			array(
				'smwgNamespacesWithSemanticLinks' => array( NS_MAIN => false ),
				'outputPage'   => $outputPage,
				'parserOutput' => $this->makeParserOutput( $semanticData ),
			),
			array(
				'text'         => ''
			)
		);

		// #2 Specialpage, no return value expected
		$title = MockTitle::buildMock( __METHOD__ . 'mock-specialpage' );

		$title->expects( $this->atLeastOnce() )
			->method( 'getPageLanguage' )
			->will( $this->returnValue( $language ) );

		$title->expects( $this->atLeastOnce() )
			->method( 'isSpecialPage' )
			->will( $this->returnValue( true ) );

		$outputPage = $this->getMockBuilder( '\OutputPage' )
			->disableOriginalConstructor()
			->getMock();

		$outputPage->expects( $this->atLeastOnce() )
			->method( 'getTitle' )
			->will( $this->returnValue( $title ) );

		$outputPage->expects( $this->atLeastOnce() )
			->method( 'getContext' )
			->will( $this->returnValue( new \RequestContext() ) );

		$provider[] = array(
			array(
				'smwgNamespacesWithSemanticLinks' => array( NS_MAIN => true ),
				'outputPage'   => $outputPage,
				'parserOutput' => $this->makeParserOutput( $semanticData ),
			),
			array(
				'text'         => ''
			)
		);

		// #3 Redirect, no return value expected
		$title = MockTitle::buildMock( __METHOD__ . 'mock-redirect' );

		$title->expects( $this->atLeastOnce() )
			->method( 'getPageLanguage' )
			->will( $this->returnValue( $language ) );

		$title->expects( $this->atLeastOnce() )
			->method( 'isRedirect' )
			->will( $this->returnValue( true ) );

		$outputPage = $this->getMockBuilder( '\OutputPage' )
			->disableOriginalConstructor()
			->getMock();

		$outputPage->expects( $this->atLeastOnce() )
			->method( 'getTitle' )
			->will( $this->returnValue( $title ) );

		$outputPage->expects( $this->atLeastOnce() )
			->method( 'getContext' )
			->will( $this->returnValue( new \RequestContext() ) );

		$provider[] = array(
			array(
				'smwgNamespacesWithSemanticLinks' => array( NS_MAIN => true ),
				'outputPage'   => $outputPage,
				'parserOutput' => $this->makeParserOutput( $semanticData ),
			),
			array(
				'text'         => ''
			)
		);

		// #4 Oldid
		$title = MockTitle::buildMockForMainNamespace( __METHOD__ . 'mock-oldid' );

		$title->expects( $this->atLeastOnce() )
			->method( 'exists' )
			->will( $this->returnValue( true ) );

		$title->expects( $this->atLeastOnce() )
			->method( 'getPageLanguage' )
			->will( $this->returnValue( $language ) );

		$outputPage = $this->getMockBuilder( '\OutputPage' )
			->disableOriginalConstructor()
			->getMock();

		$outputPage->expects( $this->atLeastOnce() )
			->method( 'getTitle' )
			->will( $this->returnValue( $title ) );

		$context = new \RequestContext( );
		$context->setRequest( new \FauxRequest( array( 'oldid' => 9001 ), true ) );

		$outputPage->expects( $this->atLeastOnce() )
			->method( 'getContext' )
			->will( $this->returnValue( $context ) );

		$provider[] = array(
			array(
				'smwgNamespacesWithSemanticLinks' => array( NS_MAIN => true ),
				'outputPage'   => $outputPage,
				'parserOutput' => $this->makeParserOutput( $semanticData ),
			),
			array(
				'text'         => $subject->getDBKey()
			)
		);

		return $provider;
	}

	protected function makeParserOutput( $data ) {

		$parserOutput = new ParserOutput();

		if ( method_exists( $parserOutput, 'setExtensionData' ) ) {
			$parserOutput->setExtensionData( 'smwdata', $data );
		} else {
			$parserOutput->mSMWData = $data;
		}

		return $parserOutput;
	}

}

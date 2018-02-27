<?php

namespace SMW\Test\MediaWiki\Hooks;

use LinksUpdate;
use ParserOutput;
use SMW\MediaWiki\Hooks\LinksUpdateConstructed;
use SMW\Tests\TestEnvironment;
use Title;

/**
 * @covers \SMW\MediaWiki\Hooks\LinksUpdateConstructed
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class LinksUpdateConstructedTest extends \PHPUnit_Framework_TestCase {

	private $testEnvironment;

	protected function setUp() {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment();

		$idTable = $this->getMockBuilder( '\stdClass' )
			->setMethods( array( 'exists' ) )
			->getMock();

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->setMethods( array( 'getObjectIds' ) )
			->getMock();

		$store->expects( $this->any() )
			->method( 'getObjectIds' )
			->will( $this->returnValue( $idTable ) );

		$this->testEnvironment->registerObject( 'Store', $store );
	}

	protected function tearDown() {
		$this->testEnvironment->tearDown();
		parent::tearDown();
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			'\SMW\MediaWiki\Hooks\LinksUpdateConstructed',
			new LinksUpdateConstructed()
		);
	}

	public function testProcess() {

		$title = $this->getMockBuilder( '\Title' )
			->disableOriginalConstructor()
			->getMock();

		$title->expects( $this->any() )
			->method( 'getArticleID' )
			->will( $this->returnValue( 11001 ) );

		$title->expects( $this->any() )
			->method( 'getDBKey' )
			->will( $this->returnValue( __METHOD__ ) );

		$title->expects( $this->any() )
			->method( 'getPrefixedText' )
			->will( $this->returnValue( __METHOD__ ) );

		$title->expects( $this->any() )
			->method( 'getNamespace' )
			->will( $this->returnValue( NS_MAIN ) );

		$title->expects( $this->any() )
			->method( 'isSpecialPage' )
			->will( $this->returnValue( false ) );

		$parserOutput = new ParserOutput();
		$parserOutput->setTitleText( $title->getPrefixedText() );

		$idTable = $this->getMockBuilder( '\stdClass' )
			->setMethods( array( 'exists' ) )
			->getMock();

		$idTable->expects( $this->atLeastOnce() )
			->method( 'exists' )
			->will( $this->returnValue( true ) );

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->setMethods( array( 'clearData', 'getObjectIds' ) )
			->getMock();

		$store->expects( $this->any() )
			->method( 'getObjectIds' )
			->will( $this->returnValue( $idTable ) );

		$store->expects( $this->atLeastOnce() )
			->method( 'clearData' );

		$this->testEnvironment->registerObject( 'Store', $store );

		$instance = new LinksUpdateConstructed();
		$instance->disableDeferredUpdate();

		$this->assertTrue(
			$instance->process( new LinksUpdate( $title, $parserOutput ) )
		);
	}

	public function testNoExtraParsingForNotEnabledNamespace() {

		$this->testEnvironment->addConfiguration(
			'smwgNamespacesWithSemanticLinks',
			array( NS_HELP => false )
		);

		$title = Title::newFromText( __METHOD__, NS_HELP );
		$parserOutput = new ParserOutput();

		$parserData = $this->getMockBuilder( '\SMW\ParserData' )
			->disableOriginalConstructor()
			->getMock();

		$parserData->expects( $this->never() )
			->method( 'getSemanticData' );

		$parserData->expects( $this->once() )
			->method( 'updateStore' );

		$this->testEnvironment->registerObject( 'ParserData', $parserData );

		$linksUpdate = $this->getMockBuilder( '\LinksUpdate' )
			->disableOriginalConstructor()
			->getMock();

		$linksUpdate->expects( $this->any() )
			->method( 'getTitle' )
			->will( $this->returnValue( $title ) );

		$linksUpdate->expects( $this->atLeastOnce() )
			->method( 'getParserOutput' )
			->will( $this->returnValue( $parserOutput ) );

		$instance = new LinksUpdateConstructed();

		$this->assertTrue(
			$instance->process( $linksUpdate )
		);
	}

	public function testTemplateUpdate() {

		$this->testEnvironment->addConfiguration(
			'smwgNamespacesWithSemanticLinks',
			[ NS_HELP => false ]
		);

		$title = Title::newFromText( __METHOD__, NS_HELP );
		$parserOutput = new ParserOutput();

		$parserData = $this->getMockBuilder( '\SMW\ParserData' )
			->disableOriginalConstructor()
			->setMethods( [ 'getSemanticData', 'updateStore', 'markUpdate' ] )
			->getMock();

		$parserData->expects( $this->never() )
			->method( 'getSemanticData' );

		$parserData->expects( $this->once() )
			->method( 'updateStore' );

		$this->testEnvironment->registerObject( 'ParserData', $parserData );

		$linksUpdate = $this->getMockBuilder( '\LinksUpdate' )
			->disableOriginalConstructor()
			->getMock();

		$linksUpdate->expects( $this->any() )
			->method( 'getTitle' )
			->will( $this->returnValue( $title ) );

		$linksUpdate->expects( $this->atLeastOnce() )
			->method( 'getParserOutput' )
			->will( $this->returnValue( $parserOutput ) );

		$linksUpdate->mTemplates = [ 'Foo' ];
		$linksUpdate->mRecursive = false;

		$instance = new LinksUpdateConstructed();
		$instance->process( $linksUpdate );

		$this->assertTrue(
			$parserData->getOption( $parserData::OPT_FORCED_UPDATE )
		);
	}

}

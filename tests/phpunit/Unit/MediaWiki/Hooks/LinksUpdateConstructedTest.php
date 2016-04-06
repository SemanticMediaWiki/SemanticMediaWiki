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
			->setMethods( array( 'hasIDFor' ) )
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

		$title = Title::newFromText( __METHOD__ );
		$title->resetArticleID( 10001 );

		$linksUpdate = $this->getMockBuilder( '\LinksUpdate' )
			->disableOriginalConstructor()
			->getMock();

		$linksUpdate->expects( $this->any() )
			->method( 'getTitle' )
			->will( $this->returnValue( $title ) );

		$this->assertInstanceOf(
			'\SMW\MediaWiki\Hooks\LinksUpdateConstructed',
			new LinksUpdateConstructed( $linksUpdate )
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
			->setMethods( array( 'hasIDFor' ) )
			->getMock();

		$idTable->expects( $this->atLeastOnce() )
			->method( 'hasIDFor' )
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

		$instance = new LinksUpdateConstructed(
			new LinksUpdate( $title, $parserOutput )
		);

		$instance->disableDeferredUpdate();

		$this->assertTrue(
			$instance->process()
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

		$linksUpdate->expects( $this->once() )
			->method( 'getParserOutput' )
			->will( $this->returnValue( $parserOutput ) );

		$instance = new LinksUpdateConstructed( $linksUpdate );

		$this->assertTrue(
			$instance->process()
		);
	}

}

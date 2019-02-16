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
	private $namespaceExaminer;

	protected function setUp() {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment();

		$this->namespaceExaminer = $this->getMockBuilder( '\SMW\NamespaceExaminer' )
			->disableOriginalConstructor()
			->getMock();

		$idTable = $this->getMockBuilder( '\stdClass' )
			->setMethods( [ 'exists', 'findAssociatedRev' ] )
			->getMock();

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->setMethods( [ 'getObjectIds' ] )
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
			LinksUpdateConstructed::class,
			new LinksUpdateConstructed( $this->namespaceExaminer )
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
			->setMethods( [ 'exists', 'findAssociatedRev' ] )
			->getMock();

		$idTable->expects( $this->atLeastOnce() )
			->method( 'exists' )
			->will( $this->returnValue( true ) );

		$idTable->expects( $this->atLeastOnce() )
			->method( 'findAssociatedRev' )
			->will( $this->returnValue( 42 ) );

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->setMethods( [ 'clearData', 'getObjectIds' ] )
			->getMock();

		$store->expects( $this->any() )
			->method( 'getObjectIds' )
			->will( $this->returnValue( $idTable ) );

		$store->expects( $this->atLeastOnce() )
			->method( 'clearData' );

		$this->testEnvironment->registerObject( 'Store', $store );

		$instance = new LinksUpdateConstructed(
			$this->namespaceExaminer
		);

		$instance->setLogger( $this->testEnvironment->newSpyLogger() );
		$instance->disableDeferredUpdate();

		$this->assertTrue(
			$instance->process( new LinksUpdate( $title, $parserOutput ) )
		);
	}

	public function testNoExtraParsingForNotEnabledNamespace() {

		$this->testEnvironment->addConfiguration(
			'smwgNamespacesWithSemanticLinks',
			[ NS_HELP => false ]
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

		$instance = new LinksUpdateConstructed(
			$this->namespaceExaminer
		);

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

		$instance = new LinksUpdateConstructed(
			$this->namespaceExaminer
		);

		$instance->process( $linksUpdate );

		$this->assertTrue(
			$parserData->getOption( $parserData::OPT_FORCED_UPDATE )
		);
	}

	public function testIsReadOnly_DoNothing() {

		$linksUpdate = $this->getMockBuilder( '\LinksUpdate' )
			->disableOriginalConstructor()
			->getMock();

		$linksUpdate->expects( $this->never() )
			->method( 'getTitle' );

		$instance = new LinksUpdateConstructed(
			$this->namespaceExaminer
		);

		$instance->isReadOnly( true );

		$this->assertFalse(
			$instance->process( $linksUpdate )
		);
	}

}

<?php

namespace SMW\Tests\Unit\MediaWiki\Hooks;

use MediaWiki\Deferred\LinksUpdate\LinksUpdate;
use MediaWiki\MediaWikiServices;
use MediaWiki\Parser\ParserOutput;
use MediaWiki\Title\Title;
use PHPUnit\Framework\TestCase;
use SMW\MediaWiki\Hooks\LinksUpdateComplete;
use SMW\MediaWiki\RevisionGuard;
use SMW\NamespaceExaminer;
use SMW\ParserData;
use SMW\SQLStore\SQLStore;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\MediaWiki\Hooks\LinksUpdateComplete
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 1.9
 *
 * @author mwjames
 */
class LinksUpdateCompleteTest extends TestCase {

	private $testEnvironment;
	private $namespaceExaminer;
	private $spyLogger;
	private $revisionGuard;

	protected function setUp(): void {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment();
		$this->spyLogger = $this->testEnvironment->newSpyLogger();

		$this->revisionGuard = $this->getMockBuilder( RevisionGuard::class )
			->disableOriginalConstructor()
			->getMock();

		$this->revisionGuard->expects( $this->any() )
			->method( 'isSkippableUpdate' )
			->willReturn( false );

		$this->namespaceExaminer = $this->getMockBuilder( NamespaceExaminer::class )
			->disableOriginalConstructor()
			->getMock();

		$idTable = $this->getMockBuilder( '\stdClass' )
			->setMethods( [ 'exists', 'findAssociatedRev' ] )
			->getMock();

		$store = $this->getMockBuilder( SQLStore::class )
			->disableOriginalConstructor()
			->setMethods( [ 'getObjectIds' ] )
			->getMock();

		$store->expects( $this->any() )
			->method( 'getObjectIds' )
			->willReturn( $idTable );

		$this->testEnvironment->registerObject( 'Store', $store );
		$this->testEnvironment->registerObject( 'RevisionGuard', $this->revisionGuard );
	}

	protected function tearDown(): void {
		$this->testEnvironment->tearDown();
		parent::tearDown();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			LinksUpdateComplete::class,
			new LinksUpdateComplete( $this->namespaceExaminer )
		);
	}

	public function testProcess() {
		$title = $this->getMockBuilder( Title::class )
			->disableOriginalConstructor()
			->getMock();

		$title->expects( $this->any() )
			->method( 'getArticleID' )
			->willReturn( 11001 );

		$title->expects( $this->any() )
			->method( 'getLatestRevID' )
			->willReturn( 9999 );

		$title->expects( $this->any() )
			->method( 'getDBKey' )
			->willReturn( __METHOD__ );

		$title->expects( $this->any() )
			->method( 'getPrefixedText' )
			->willReturn( __METHOD__ );

		$title->expects( $this->any() )
			->method( 'getNamespace' )
			->willReturn( NS_MAIN );

		$title->expects( $this->any() )
			->method( 'isSpecialPage' )
			->willReturn( false );

		$parserOutput = new ParserOutput();
		$parserOutput->setTitleText( $title->getPrefixedText() );

		$idTable = $this->getMockBuilder( '\stdClass' )
			->setMethods( [ 'exists', 'findAssociatedRev' ] )
			->getMock();

		$idTable->expects( $this->atLeastOnce() )
			->method( 'exists' )
			->willReturn( true );

		$idTable->expects( $this->atLeastOnce() )
			->method( 'findAssociatedRev' )
			->willReturn( 42 );

		$store = $this->getMockBuilder( SQLStore::class )
			->disableOriginalConstructor()
			->setMethods( [ 'clearData', 'getObjectIds' ] )
			->getMock();

		$store->expects( $this->any() )
			->method( 'getObjectIds' )
			->willReturn( $idTable );

		$store->expects( $this->atLeastOnce() )
			->method( 'clearData' );

		$this->testEnvironment->registerObject( 'Store', $store );

		$instance = new LinksUpdateComplete(
			$this->namespaceExaminer
		);

		$instance->setLogger( $this->spyLogger );

		$instance->setRevisionGuard(
			$this->revisionGuard
		);

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

		$title = MediaWikiServices::getInstance()->getTitleFactory()->newFromText( __METHOD__, NS_HELP );
		$parserOutput = new ParserOutput();

		$parserData = $this->getMockBuilder( ParserData::class )
			->disableOriginalConstructor()
			->getMock();

		$parserData->expects( $this->never() )
			->method( 'getSemanticData' );

		$parserData->expects( $this->once() )
			->method( 'updateStore' );

		$this->testEnvironment->registerObject( 'ParserData', $parserData );

		$linksUpdate = $this->createMock( LinksUpdate::class );

		$linksUpdate->expects( $this->any() )
			->method( 'getTitle' )
			->willReturn( $title );

		$linksUpdate->expects( $this->atLeastOnce() )
			->method( 'getParserOutput' )
			->willReturn( $parserOutput );

		$instance = new LinksUpdateComplete(
			$this->namespaceExaminer
		);

		$instance->setRevisionGuard(
			$this->revisionGuard
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

		$title = MediaWikiServices::getInstance()->getTitleFactory()->newFromText( __METHOD__, NS_HELP );
		$parserOutput = $this->createMock( ParserOutput::class );
		$parserOutput->expects( $this->any() )
			->method( 'getLinkList' )
			->willReturn( [ NS_TEMPLATE => [ 'Foo' => 1 ] ] );

		$parserData = $this->getMockBuilder( ParserData::class )
			->disableOriginalConstructor()
			->setMethods( [ 'getSemanticData', 'updateStore', 'markUpdate' ] )
			->getMock();

		$parserData->expects( $this->never() )
			->method( 'getSemanticData' );

		$parserData->expects( $this->once() )
			->method( 'updateStore' );

		$this->testEnvironment->registerObject( 'ParserData', $parserData );

		$linksUpdate = $this->createMock( LinksUpdate::class );

		$linksUpdate->expects( $this->any() )
			->method( 'getTitle' )
			->willReturn( $title );

		$linksUpdate->expects( $this->atLeastOnce() )
			->method( 'getParserOutput' )
			->willReturn( $parserOutput );

		$linksUpdate->expects( $this->any() )
			->method( 'isRecursive' )
			->willReturn( false );

		$instance = new LinksUpdateComplete(
			$this->namespaceExaminer
		);

		$instance->setRevisionGuard(
			$this->revisionGuard
		);

		$instance->process( $linksUpdate );

		$this->assertTrue(
			$parserData->getOption( $parserData::OPT_FORCED_UPDATE )
		);
	}

	public function testIsNotReady_DoNothing() {
		$linksUpdate = $this->createMock( LinksUpdate::class );

		$linksUpdate->expects( $this->never() )
			->method( 'getTitle' );

		$instance = new LinksUpdateComplete(
			$this->namespaceExaminer
		);

		$instance->setLogger( $this->spyLogger );

		$instance->isReady( false );

		$this->assertFalse(
			$instance->process( $linksUpdate )
		);
	}

}

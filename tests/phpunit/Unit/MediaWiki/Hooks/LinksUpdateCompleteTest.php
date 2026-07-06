<?php

namespace SMW\Tests\Unit\MediaWiki\Hooks;

use MediaWiki\Deferred\LinksUpdate\LinksUpdate;
use MediaWiki\MediaWikiServices;
use MediaWiki\Parser\ParserOutput;
use MediaWiki\Title\Title;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use SMW\MediaWiki\Hooks\LinksUpdateComplete;
use SMW\MediaWiki\Jobs\ContentParserFactory;
use SMW\MediaWiki\RevisionGuard;
use SMW\NamespaceExaminer;
use SMW\SiteReadiness;
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
	private $contentParserFactory;
	private $siteReadiness;

	protected function setUp(): void {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment();
		$this->spyLogger = $this->testEnvironment->newSpyLogger();

		$this->revisionGuard = $this->createMock( RevisionGuard::class );
		$this->revisionGuard->method( 'isSkippableUpdate' )->willReturn( false );

		$this->namespaceExaminer = $this->createMock( NamespaceExaminer::class );

		$this->contentParserFactory = $this->createMock( ContentParserFactory::class );

		$this->siteReadiness = $this->createMock( SiteReadiness::class );
		$this->siteReadiness->method( 'isReady' )->willReturn( true );
	}

	protected function tearDown(): void {
		$this->testEnvironment->tearDown();
		parent::tearDown();
	}

	private function newInstance(): LinksUpdateComplete {
		return new LinksUpdateComplete(
			$this->namespaceExaminer,
			$this->contentParserFactory,
			$this->revisionGuard,
			$this->siteReadiness,
			$this->spyLogger
		);
	}

	public function testCanConstruct() {
		$logger = $this->createMock( LoggerInterface::class );

		$this->assertInstanceOf(
			LinksUpdateComplete::class,
			new LinksUpdateComplete(
				$this->namespaceExaminer,
				$this->contentParserFactory,
				$this->revisionGuard,
				$this->siteReadiness,
				$logger
			)
		);
	}

	public function testProcess() {
		$title = $this->createMock( Title::class );
		$title->method( 'getArticleID' )->willReturn( 11001 );
		$title->method( 'getLatestRevID' )->willReturn( 9999 );
		$title->method( 'getDBKey' )->willReturn( __METHOD__ );
		$title->method( 'getPrefixedText' )->willReturn( __METHOD__ );
		$title->method( 'getNamespace' )->willReturn( NS_MAIN );
		$title->method( 'isSpecialPage' )->willReturn( false );

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

		$store->method( 'getObjectIds' )->willReturn( $idTable );
		$store->expects( $this->atLeastOnce() )->method( 'clearData' );

		$this->testEnvironment->registerObject( 'Store', $store );

		$instance = $this->newInstance();
		$instance->disableDeferredUpdate();

		$this->assertTrue(
			$instance->onLinksUpdateComplete( new LinksUpdate( $title, $parserOutput ), null )
		);
	}

	private function registerStoreWithEmptyIdTable(): void {
		$idTable = $this->getMockBuilder( '\stdClass' )
			->setMethods( [ 'exists', 'findAssociatedRev' ] )
			->getMock();
		$idTable->method( 'exists' )->willReturn( false );
		$idTable->method( 'findAssociatedRev' )->willReturn( 0 );

		$store = $this->getMockBuilder( SQLStore::class )
			->disableOriginalConstructor()
			->setMethods( [ 'getObjectIds', 'clearData' ] )
			->getMock();
		$store->method( 'getObjectIds' )->willReturn( $idTable );

		$this->testEnvironment->registerObject( 'Store', $store );
	}

	public function testNoExtraParsingForNotEnabledNamespace() {
		$this->testEnvironment->addConfiguration(
			'smwgNamespacesWithSemanticLinks',
			[ NS_HELP => false ]
		);

		$title = MediaWikiServices::getInstance()->getTitleFactory()->newFromText( __METHOD__, NS_HELP );
		$parserOutput = new ParserOutput();

		$linksUpdate = $this->createMock( LinksUpdate::class );
		$linksUpdate->method( 'getTitle' )->willReturn( $title );
		$linksUpdate->method( 'getParserOutput' )->willReturn( $parserOutput );

		$this->registerStoreWithEmptyIdTable();

		// `getSemanticData()` is only consulted when the namespace IS semantic-enabled;
		// for NS_HELP (disabled), the branch that calls it must be skipped.
		$this->namespaceExaminer->method( 'isSemanticEnabled' )->willReturn( false );

		$instance = $this->newInstance();
		$instance->disableDeferredUpdate();

		$this->assertTrue(
			$instance->onLinksUpdateComplete( $linksUpdate, null )
		);
	}

	public function testTemplateUpdate_doesNotErrorOut() {
		// Note: this test previously asserted that `OPT_FORCED_UPDATE` was set
		// on the `ParserData` after the template-link-with-non-recursive
		// branch fired. `ParserData` is now constructed inline, so the flag is
		// not reachable through a mock. The narrower behavioural contract
		// (`OPT_FORCED_UPDATE=true` bypasses `DataUpdater::isSkippable`) is
		// covered end-to-end by integration tests under
		// `tests/phpunit/Integration/MediaWiki/Hooks/`.
		$this->testEnvironment->addConfiguration(
			'smwgNamespacesWithSemanticLinks',
			[ NS_HELP => false ]
		);

		$title = MediaWikiServices::getInstance()->getTitleFactory()->newFromText( __METHOD__, NS_HELP );
		$parserOutput = $this->createMock( ParserOutput::class );
		$parserOutput->method( 'getLinkList' )->willReturn( [ NS_TEMPLATE => [ 'Foo' => 1 ] ] );

		$linksUpdate = $this->createMock( LinksUpdate::class );
		$linksUpdate->method( 'getTitle' )->willReturn( $title );
		$linksUpdate->method( 'getParserOutput' )->willReturn( $parserOutput );
		$linksUpdate->method( 'isRecursive' )->willReturn( false );

		$this->registerStoreWithEmptyIdTable();

		$this->namespaceExaminer->method( 'isSemanticEnabled' )->willReturn( false );

		$instance = $this->newInstance();
		$instance->disableDeferredUpdate();

		$this->assertTrue(
			$instance->onLinksUpdateComplete( $linksUpdate, null )
		);
	}

	public function testIsNotReady_DoNothing() {
		$this->siteReadiness = $this->createMock( SiteReadiness::class );
		$this->siteReadiness->method( 'isReady' )->willReturn( false );

		$linksUpdate = $this->createMock( LinksUpdate::class );
		$linksUpdate->expects( $this->never() )->method( 'getTitle' );

		$this->assertFalse(
			$this->newInstance()->onLinksUpdateComplete( $linksUpdate, null )
		);
	}

}

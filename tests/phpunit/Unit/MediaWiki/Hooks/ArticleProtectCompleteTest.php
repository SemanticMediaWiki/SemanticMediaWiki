<?php

namespace SMW\Tests\Unit\MediaWiki\Hooks;

use MediaWiki\Parser\ParserOutput;
use MediaWiki\Revision\RevisionRecord;
use MediaWiki\Title\Title;
use MediaWiki\User\User;
use PHPUnit\Framework\TestCase;
use SMW\DataItemFactory;
use SMW\Localizer\Message;
use SMW\MediaWiki\EditInfo;
use SMW\MediaWiki\Hooks\ArticleProtectComplete;
use SMW\MediaWiki\MwCollaboratorFactory;
use SMW\MediaWiki\RevisionGuard;
use SMW\Property\Annotators\EditProtectedPropertyAnnotator;
use SMW\Settings;
use SMW\Tests\TestEnvironment;
use WikiPage;

/**
 * @covers \SMW\MediaWiki\Hooks\ArticleProtectComplete
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 2.5
 *
 * @author mwjames
 */
class ArticleProtectCompleteTest extends TestCase {

	private $spyLogger;
	private $testEnvironment;
	private $settings;
	private $mwCollaboratorFactory;
	private $revisionGuard;
	private $dataItemFactory;
	private $editInfo;
	private $semanticDataFactory;

	protected function setUp(): void {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment();
		$this->dataItemFactory = new DataItemFactory();
		$this->semanticDataFactory = $this->testEnvironment->getUtilityFactory()->newSemanticDataFactory();
		$this->spyLogger = $this->testEnvironment->getUtilityFactory()->newSpyLogger();

		$this->settings = $this->createMock( Settings::class );

		$this->editInfo = $this->createMock( EditInfo::class );

		$this->mwCollaboratorFactory = $this->createMock( MwCollaboratorFactory::class );
		$this->mwCollaboratorFactory->method( 'newEditInfo' )->willReturn( $this->editInfo );

		$this->revisionGuard = $this->createMock( RevisionGuard::class );
		$this->revisionGuard->method( 'newRevisionFromPage' )->willReturn( $this->createMock( RevisionRecord::class ) );
	}

	protected function tearDown(): void {
		$this->testEnvironment->tearDown();
		parent::tearDown();
	}

	private function newInstance(): ArticleProtectComplete {
		return new ArticleProtectComplete(
			$this->settings,
			$this->spyLogger,
			$this->mwCollaboratorFactory,
			$this->revisionGuard,
			$this->dataItemFactory
		);
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			ArticleProtectComplete::class,
			$this->newInstance()
		);
	}

	public function testProcessOnSelfInvokedReason() {
		$wikiPage = $this->createMock( WikiPage::class );
		$user = $this->createMock( User::class );

		$protections = [];
		$reason = Message::get( 'smw-edit-protection-auto-update' );

		$this->assertTrue(
			$this->newInstance()->onArticleProtectComplete( $wikiPage, $user, $protections, $reason )
		);

		$this->assertStringContainsString(
			'No changes required, invoked by own process',
			$this->spyLogger->getMessagesAsString()
		);
	}

	public function testProcessOnMissingParserOutput() {
		$this->editInfo->expects( $this->once() )
			->method( 'getOutput' )
			->willReturn( null );

		$wikiPage = $this->createMock( WikiPage::class );
		$user = $this->createMock( User::class );

		$this->assertTrue(
			$this->newInstance()->onArticleProtectComplete( $wikiPage, $user, [ 'edit' => 'sysop' ], 'foo' )
		);

		$this->assertStringContainsString(
			'Missing ParserOutput',
			$this->spyLogger->getMessagesAsString()
		);
	}

	public function testProcessOnMatchableEditProtectionToAddAnnotation() {
		$semanticData = $this->semanticDataFactory->newEmptySemanticData(
			$this->dataItemFactory->newDIWikiPage( __METHOD__, NS_SPECIAL )
		);

		$parserOutput = $this->createMock( ParserOutput::class );
		$parserOutput->method( 'getExtensionData' )->willReturn( $semanticData );

		$title = $this->createMock( Title::class );
		$title->method( 'getDBKey' )->willReturn( 'Foo' );
		$title->method( 'getNamespace' )->willReturn( NS_SPECIAL );
		$title->method( 'getLatestRevID' )->willReturn( 9900 );

		$wikiPage = $this->createMock( WikiPage::class );
		$wikiPage->method( 'getTitle' )->willReturn( $title );

		$user = $this->createMock( User::class );

		$this->editInfo->expects( $this->once() )
			->method( 'getOutput' )
			->willReturn( $parserOutput );

		$this->settings->method( 'get' )
			->with( 'smwgEditProtectionRight' )
			->willReturn( 'Foo' );

		$protections = [ 'edit' => 'Foo' ];

		$this->newInstance()->onArticleProtectComplete( $wikiPage, $user, $protections, '' );

		$this->assertStringContainsString(
			'ArticleProtectComplete addProperty `Is edit protected`',
			$this->spyLogger->getMessagesAsString()
		);
	}

	public function testProcessOnUnmatchableEditProtectionToRemoveAnnotation() {
		$semanticData = $this->semanticDataFactory->newEmptySemanticData(
			$this->dataItemFactory->newDIWikiPage( __METHOD__, NS_SPECIAL )
		);

		$dataItem = $this->dataItemFactory->newDIBoolean( true );
		$dataItem->setOption( EditProtectedPropertyAnnotator::SYSTEM_ANNOTATION, true );

		$semanticData->addPropertyObjectValue(
			$this->dataItemFactory->newDIProperty( '_EDIP' ),
			$dataItem
		);

		$parserOutput = $this->createMock( ParserOutput::class );
		$parserOutput->method( 'getExtensionData' )->willReturn( $semanticData );

		$title = $this->createMock( Title::class );
		$title->method( 'getDBKey' )->willReturn( 'Foo' );
		$title->method( 'getNamespace' )->willReturn( NS_SPECIAL );
		$title->method( 'getLatestRevID' )->willReturn( 9901 );

		$wikiPage = $this->createMock( WikiPage::class );
		$wikiPage->method( 'getTitle' )->willReturn( $title );

		$user = $this->createMock( User::class );

		$this->editInfo->expects( $this->once() )
			->method( 'getOutput' )
			->willReturn( $parserOutput );

		$this->settings->method( 'get' )
			->with( 'smwgEditProtectionRight' )
			->willReturn( 'Foo2' );

		$protections = [ 'edit' => 'Foo' ];

		$this->newInstance()->onArticleProtectComplete( $wikiPage, $user, $protections, '' );

		$this->assertStringContainsString(
			'ArticleProtectComplete removeProperty `Is edit protected`',
			$this->spyLogger->getMessagesAsString()
		);
	}

}

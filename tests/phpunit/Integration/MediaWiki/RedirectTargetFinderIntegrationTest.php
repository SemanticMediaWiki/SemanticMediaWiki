<?php

namespace SMW\Tests\Integration\MediaWiki;

use SMW\Services\ServicesFactory as ApplicationFactory;
use SMW\DIProperty;
use SMW\DIWikiPage;
use SMW\Tests\SMWIntegrationTestCase;
use SMW\Tests\Utils\UtilityFactory;
use SMW\Tests\PHPUnitCompat;
use Title;

/**
 * @group semantic-mediawiki-integration
 * @group mediawiki-databas
 * @group Database
 * @group medium
 *
 * @license GNU GPL v2+
 * @since   2.1
 *
 * @author mwjames
 */
class RedirectTargetFinderIntegrationTest extends SMWIntegrationTestCase {

	use PHPUnitCompat;

	private $deletePoolOfPages = [];
	private $semanticDataValidator;

	protected function setUp(): void {
		parent::setUp();

		$this->testEnvironment->addConfiguration(
			'smwgEnabledDeferredUpdate',
			false
		);

		$utilityFactory = UtilityFactory::getInstance();
		$this->semanticDataValidator = $utilityFactory->newValidatorFactory()->newSemanticDataValidator();

		$utilityFactory->newMwHooksHandler()->invokeHooksFromRegistry();
	}

	protected function tearDown(): void {
		parent::tearDown();
	}

	public function testRedirectParseUsingManualRedirect() {
		$target = Title::newFromText( 'RedirectParseUsingManualRedirect' );

		$wikiPage = parent::getNonexistingTestPage( __METHOD__ );
		parent::editPage( $wikiPage, '#REDIRECT [[RedirectParseUsingManualRedirect]]' );

		$expected = [
			new DIProperty( '_REDI' )
		];

		$this->semanticDataValidator->assertHasProperties(
			$expected,
			$this->getStore()->getInProperties( DIWikiPage::newFromTitle( $target ) )
		);

		$this->deletePoolOfPages = [
			__METHOD__,
			'RedirectParseUsingManualRedirect'
		];
	}

	public function testManualRemovalOfRedirectTarget() {
		$source = DIWikiPage::newFromTitle(
			Title::newFromText( __METHOD__ )
		);

		$target  = DIWikiPage::newFromTitle(
			Title::newFromText( 'ManualRemovalOfRedirectTarget' )
		);

		$target->getSortKey();

		$wikiPage = parent::getNonexistingTestPage( $source->getTitle() );
		parent::editPage( $wikiPage, '#REDIRECT [[Property:ManualRemovalOfRedirectTarget-NotTheRealTarget]]' );
		parent::editPage( $wikiPage, '#REDIRECT [[ManualRemovalOfRedirectTarget]]' );

		$expected = [
			new DIProperty( '_REDI' )
		];

		$this->assertEquals(
			$target,
			$this->getStore()->getRedirectTarget( $source )
		);

		$this->semanticDataValidator->assertHasProperties(
			$expected,
			$this->getStore()->getInProperties( $target )
		);

		$wikiPage = parent::getExistingTestPage( $source->getTitle() );
		parent::editPage( $wikiPage, 'removed redirect target' );

		$this->assertEquals(
			$source,
			$this->getStore()->getRedirectTarget( $source )
		);

		$this->assertEmpty(
			$this->getStore()->getInProperties( $target )
		);

		$this->deletePoolOfPages = [
			__METHOD__,
			'ManualRemovalOfRedirectTarget'
		];
	}

	
	public function testRedirectParseUsingMoveToPage() {
		$target = Title::newFromText( 'RedirectParseUsingMoveToPage' );

		$wikiPage = parent::getExistingTestPage( __METHOD__ );
		$title = $wikiPage->getTitle();

		// ---- taken from mediawiki/tests/phpunit/includes/page/MovePageTest.php
		$createRedirect = true;
		$pageId = $title->getArticleID();
		$status = $this->getServiceContainer()
			->getMovePageFactory()
			->newMovePage( $title, $target )
			->move( $this->getTestUser()->getUser(), 'move reason', $createRedirect );
		$this->assertStatusOK( $status );
		// ====

		$this->testEnvironment->executePendingDeferredUpdates();

		$expected = [
			new DIProperty( '_REDI' )
		];

		$this->semanticDataValidator->assertHasProperties(
			$expected,
			$this->getStore()->getInProperties( DIWikiPage::newFromTitle( $target ) )
		);

		$this->deletePoolOfPages = [
			__METHOD__,
			'RedirectParseUsingMoveToPage'
		];
	}

	public function testDeepRedirectTargetResolverToFindTarget() {
		$source = Title::newFromText( 'DeepRedirectTargetResolverToFindTarget' );

		$wikiPage = parent::getNonexistingTestPage( $source );
		parent::editPage( $wikiPage, '#REDIRECT [[DeepRedirectTargetResolverToFindTarget/1]]' );

		$wikiPage = parent::getNonexistingTestPage( Title::newFromText( 'DeepRedirectTargetResolverToFindTarget/1' ) );
		parent::editPage( $wikiPage, '#REDIRECT [[DeepRedirectTargetResolverToFindTarget/2]]' );

		$wikiPage = parent::getNonexistingTestPage( Title::newFromText( 'DeepRedirectTargetResolverToFindTarget/2' ) );
		parent::editPage( $wikiPage, '#REDIRECT [[DeepRedirectTargetResolverToFindTarget/3]]' );

		$target = Title::newFromText( 'DeepRedirectTargetResolverToFindTarget/3' );

		$deepRedirectTargetResolver = ApplicationFactory::getInstance()
			->newMwCollaboratorFactory()
			->newDeepRedirectTargetResolver();

		$this->assertEquals(
			$target->getDBKey(),
			$deepRedirectTargetResolver->findRedirectTargetFor( $source )->getDBKey()
		);

		$this->assertEquals(
			$target->getDBKey(),
			$this->getStore()->getRedirectTarget( DIWikiPage::newFromTitle( $source ) )->getDBKey()
		);

		$this->deletePoolOfPages = [
			'DeepRedirectTargetResolverToFindTarget',
			'DeepRedirectTargetResolverToFindTarget/1',
			'DeepRedirectTargetResolverToFindTarget/2',
			'DeepRedirectTargetResolverToFindTarget/3'
		];
	}

	public function testDeepRedirectTargetResolverToDetectCircularTarget() {
		$source = Title::newFromText( 'DeepRedirectTargetResolverToDetectCircularTarget' );

		$wikiPage = parent::getNonexistingTestPage( $source );
		parent::editPage( $wikiPage, '#REDIRECT [[DeepRedirectTargetResolverToDetectCircularTarget/1]]' );

		$wikiPage = parent::getNonexistingTestPage( Title::newFromText( 'DeepRedirectTargetResolverToDetectCircularTarget/1' ) );
		parent::editPage( $wikiPage, '#REDIRECT [[DeepRedirectTargetResolverToDetectCircularTarget/2]]' );

		// Create circular redirect
		$wikiPage = parent::getNonexistingTestPage( Title::newFromText( 'DeepRedirectTargetResolverToDetectCircularTarget/2' ) );
		parent::editPage( $wikiPage, '#REDIRECT [[DeepRedirectTargetResolverToDetectCircularTarget/1]]' );

		$deepRedirectTargetResolver = ApplicationFactory::getInstance()
			->newMwCollaboratorFactory()
			->newDeepRedirectTargetResolver();

		// Store will point towards the correct target
		$expectedRedirect = DIWikiPage::newFromTitle(
			Title::newFromText( 'DeepRedirectTargetResolverToDetectCircularTarget/1' )
		);

		$this->assertEquals(
			$expectedRedirect->getDBKey(),
			$this->getStore()->getRedirectTarget( DIWikiPage::newFromTitle( $source ) )->getDBKey()
		);

		// Resolver will raise an exception as actions can not act on
		// a circular redirect oppose to a possible annotation created by the
		// store
		$this->expectException( 'RuntimeException' );
		$deepRedirectTargetResolver->findRedirectTargetFor( $source );

		$this->deletePoolOfPages = [
			'DeepRedirectTargetResolverToDetectCircularTarget',
			'DeepRedirectTargetResolverToDetectCircularTarget/1',
			'DeepRedirectTargetResolverToDetectCircularTarget/2'
		];
	}
}

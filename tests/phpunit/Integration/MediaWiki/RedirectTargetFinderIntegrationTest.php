<?php

namespace SMW\Tests\Integration\MediaWiki;

use SMW\ApplicationFactory;
use SMW\DIProperty;
use SMW\DIWikiPage;
use SMW\Tests\MwDBaseUnitTestCase;
use SMW\Tests\Utils\UtilityFactory;
use SMW\Tests\PHPUnitCompat;
use Title;

/**
 * @group semantic-mediawiki-integration
 * @group mediawiki-databas
 *
 * @group medium
 *
 * @license GNU GPL v2+
 * @since   2.1
 *
 * @author mwjames
 */
class RedirectTargetFinderIntegrationTest extends MwDBaseUnitTestCase {

	use PHPUnitCompat;

	private $deletePoolOfPages = [];

	private $pageCreator;
	private $semanticDataValidator;

	protected function setUp() {
		parent::setUp();

		$this->testEnvironment->addConfiguration(
			'smwgEnabledDeferredUpdate',
			false
		);

		$this->pageCreator = UtilityFactory::getInstance()->newPageCreator();
		$this->semanticDataValidator = UtilityFactory::getInstance()->newValidatorFactory()->newSemanticDataValidator();

		// #3414
		// NameTableAccessException: Expected unused ID from database insert for
		// 'mw-changed-redirect-target'  into 'change_tag_def',
		$this->testEnvironment->resetMediaWikiService( 'NameTableStoreFactory' );
	}

	protected function tearDown() {

		$pageDeleter = UtilityFactory::getInstance()->newPageDeleter();

		$pageDeleter
			->doDeletePoolOfPages( $this->deletePoolOfPages );

		parent::tearDown();
	}

	public function testRedirectParseUsingManualRedirect() {

		$target = Title::newFromText( 'RedirectParseUsingManualRedirect' );

		$this->pageCreator
			->createPage( Title::newFromText( __METHOD__ ) )
			->doEdit( '#REDIRECT [[RedirectParseUsingManualRedirect]]' );

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

	public function testRedirectParseUsingMoveToPage() {

		$target = Title::newFromText( 'RedirectParseUsingMoveToPage' );

		$this->pageCreator
			->createPage( Title::newFromText( __METHOD__ ) );

		$this->pageCreator
			->getPage()
			->getTitle()
			->moveTo( $target, false, 'test', true );

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

	public function testManualRemovalOfRedirectTarget() {

		$source = DIWikiPage::newFromTitle(
			Title::newFromText( __METHOD__ )
		);

		$target  = DIWikiPage::newFromTitle(
			Title::newFromText( 'ManualRemovalOfRedirectTarget' )
		);

		$target->getSortKey();

		$this->pageCreator
			->createPage( $source->getTitle() )
			->doEdit( '#REDIRECT [[Property:ManualRemovalOfRedirectTarget-NotTheRealTarget]]' )
			->doEdit( '#REDIRECT [[ManualRemovalOfRedirectTarget]]' );

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

		$this->pageCreator
			->createPage( $source->getTitle() )
			->doEdit( 'removed redirect target' );

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

	public function testDeepRedirectTargetResolverToFindTarget() {

		$this->skipTestForMediaWikiVersionLowerThan(
			'1.20',
			"Skipping test because expected target isn't resolved correctly on 1.19"
		);

		$source = Title::newFromText( 'DeepRedirectTargetResolverToFindTarget' );

		$this->pageCreator
			->createPage( $source )
			->doEdit( '#REDIRECT [[DeepRedirectTargetResolverToFindTarget/1]]' );

		$intermediateTarget = Title::newFromText( 'DeepRedirectTargetResolverToFindTarget/2' );

		$this->pageCreator
			->createPage( Title::newFromText( 'DeepRedirectTargetResolverToFindTarget/1' ) )
			->getPage()
			->getTitle()
			->moveTo( $intermediateTarget, false, 'redirect test', true );

		$this->pageCreator
			->createPage( Title::newFromText( 'DeepRedirectTargetResolverToFindTarget/2' ) )
			->doEdit( '#REDIRECT [[DeepRedirectTargetResolverToFindTarget/3]]' );

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

		$this->skipTestForMediaWikiVersionLowerThan(
			'1.20',
			"Skipping test because circular target (RuntimeException) isn't found on 1.19"
		);

		$source = Title::newFromText( 'DeepRedirectTargetResolverToDetectCircularTarget' );

		$this->pageCreator
			->createPage( $source )
			->doEdit( '#REDIRECT [[DeepRedirectTargetResolverToDetectCircularTarget/1]]' );

		$this->pageCreator
			->createPage( Title::newFromText( 'DeepRedirectTargetResolverToDetectCircularTarget/1' ) )
			->doEdit( '#REDIRECT [[DeepRedirectTargetResolverToDetectCircularTarget/2]]' );

		// Create circular redirect
		$this->pageCreator
			->createPage( Title::newFromText( 'DeepRedirectTargetResolverToDetectCircularTarget/2' ) )
			->doEdit( '#REDIRECT [[DeepRedirectTargetResolverToDetectCircularTarget/1]]' );

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
		$this->setExpectedException( 'RuntimeException' );
		$deepRedirectTargetResolver->findRedirectTargetFor( $source );

		$this->deletePoolOfPages = [
			'DeepRedirectTargetResolverToDetectCircularTarget',
			'DeepRedirectTargetResolverToDetectCircularTarget/1',
			'DeepRedirectTargetResolverToDetectCircularTarget/2'
		];
	}

}

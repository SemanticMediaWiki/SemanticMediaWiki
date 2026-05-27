<?php

namespace SMW\Tests\Integration\MediaWiki\Hooks;

use MediaWiki\MediaWikiServices;
use MediaWiki\Title\Title;
use SMW\DataItems\Property;
use SMW\DataItems\WikiPage;
use SMW\Query\Language\SomeProperty;
use SMW\Query\Language\ValueDescription;
use SMW\Query\Query;
use SMW\Services\ServicesFactory as ApplicationFactory;
use SMW\Tests\SMWIntegrationTestCase;
use SMW\Tests\Utils\SMWDeclarativeHookReseater;
use SMW\Tests\Utils\UtilityFactory;

/**
 * @group semantic-mediawiki
 * @group Database
 * @group medium
 *
 * @license GPL-2.0-or-later
 * @since   2.1
 *
 * @author mwjames
 */
class PageMoveCompleteIntegrationTest extends SMWIntegrationTestCase {

	private $queryResultValidator;
	private $applicationFactory;
	private $toBeDeleted = [];
	private $pageCreator;
	private $revisionGuard;
	private SMWDeclarativeHookReseater $reseater;

	protected function setUp(): void {
		parent::setUp();
		$utilityFactory = $this->testEnvironment->getUtilityFactory();

		$this->applicationFactory = ApplicationFactory::getInstance();
		$this->queryResultValidator = $utilityFactory->newValidatorFactory()->newQueryResultValidator();

		// Disable every SMW declarative hook, then re-register only the
		// PageMoveComplete handler this test exercises. Individual tests
		// re-enable further hooks they need (see testPageMoveWithRemovalOfOldPage
		// and testPredefinedPropertyPageIsNotMovable below). Other SMW
		// handlers must stay off so they cannot interfere with the assertions.
		$this->reseater = new SMWDeclarativeHookReseater(
			MediaWikiServices::getInstance()->getHookContainer()
		);
		foreach ( $this->reseater->getDeclarativeHookNames() as $hook ) {
			$this->clearHook( $hook );
		}
		$this->setTemporaryHook(
			'PageMoveComplete',
			$this->reseater->buildSmwHandlerFor( 'PageMoveComplete' )
		);

		$this->pageCreator = $utilityFactory->newPageCreator();

		$this->testEnvironment->addConfiguration(
			'smwgEnabledDeferredUpdate',
			false
		);

		$this->revisionGuard = $this->applicationFactory->singleton( 'RevisionGuard' );
	}

	protected function tearDown(): void {
		$this->testEnvironment->tearDown();

		$pageDeleter = UtilityFactory::getInstance()->newPageDeleter();
		$pageDeleter->doDeletePoolOfPages( $this->toBeDeleted );

		parent::tearDown();
	}

	public function testPageMoveWithCreationOfRedirectTarget() {
		$oldTitle = Title::newFromText( __METHOD__ . '-old' );
		$expectedNewTitle = Title::newFromText( __METHOD__ . '-new' );

		$this->assertNull(
			$this->newRevisionFromTitle( $expectedNewTitle )
		);

		$this->pageCreator->createPage( $oldTitle );
		$result = $this->pageCreator->doMoveTo( $expectedNewTitle, true );

		$this->assertTrue( $result );

		$this->assertNotNull(
			$this->newRevisionFromTitle( $oldTitle )
		);

		$this->assertNotNull(
			$this->newRevisionFromTitle( $expectedNewTitle )
		);

		$this->toBeDeleted = [
			$oldTitle,
			$expectedNewTitle
		];
	}

	public function testPageMoveWithRemovalOfOldPage() {
		// Further hooks required to ensure in-text annotations can be used for queries
		foreach ( [ 'InternalParseBeforeLinks', 'LinksUpdateComplete' ] as $hook ) {
			$this->clearHook( $hook );
			$this->setTemporaryHook( $hook, $this->reseater->buildSmwHandlerFor( $hook ) );
		}

		$title = Title::newFromText( __METHOD__ . '-old' );
		$expectedNewTitle = Title::newFromText( __METHOD__ . '-new' );

		$this->assertNull(
			$this->newRevisionFromTitle( $expectedNewTitle )
		);

		$this->pageCreator
			->createPage( $title )
			->doEdit( '[[Has function hook test::PageCompleteMove]]' );

		$this->pageCreator->doMoveTo( $expectedNewTitle, false );

		$this->testEnvironment->executePendingDeferredUpdates();

		$this->assertNull(
			$this->newRevisionFromTitle( $title )
		);

		$this->assertNotNull(
			$this->newRevisionFromTitle( $expectedNewTitle )
		);

		/**
		 * @query {{#ask: [[Has function hook test::PageCompleteMove]] }}
		 */
		$description = new SomeProperty(
			Property::newFromUserLabel( 'Has function hook test' ),
			new ValueDescription( new WikiPage( 'PageCompleteMove', 0 ), null, SMW_CMP_EQ )
		);

		$query = new Query(
			$description,
			false,
			true
		);

		$query->querymode = Query::MODE_INSTANCES;

		$queryResult = $this->getStore()->getQueryResult( $query );

		// #566
		$this->assertCount(
			1,
			$queryResult->getResults()
		);

		$this->queryResultValidator->assertThatQueryResultHasSubjects(
			[ WikiPage::newFromTitle( $expectedNewTitle ) ],
			$queryResult
		);

		$this->toBeDeleted = [
			$title,
			$expectedNewTitle
		];
	}

	public function testPredefinedPropertyPageIsNotMovable() {
		$this->clearHook( 'TitleIsMovable' );
		$this->setTemporaryHook(
			'TitleIsMovable',
			$this->reseater->buildSmwHandlerFor( 'TitleIsMovable' )
		);

		$title = Title::newFromText( 'Modification date', SMW_NS_PROPERTY );
		$expectedNewTitle = Title::newFromText( __METHOD__, SMW_NS_PROPERTY );

		$this->pageCreator->createPage( $title );
		$this->pageCreator->doMoveTo( $expectedNewTitle, true );

		$this->assertNotNull(
			$this->newRevisionFromTitle( $title )
		);

		$this->assertNull(
			$this->newRevisionFromTitle( $expectedNewTitle )
		);

		$this->toBeDeleted = [
			$title,
			$expectedNewTitle
		];
	}

	private function newRevisionFromTitle( $title ) {
		return $this->revisionGuard->newRevisionFromPage(
			$this->applicationFactory->newPageCreator()->createPage( $title )
		);
	}
}

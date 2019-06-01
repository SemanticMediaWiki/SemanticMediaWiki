<?php

namespace SMW\Tests\Integration\MediaWiki\Hooks;

use SMW\ApplicationFactory;
use SMW\DIProperty;
use SMW\DIWikiPage;
use SMW\Query\Language\SomeProperty;
use SMW\Query\Language\ValueDescription;
use SMW\Tests\MwDBaseUnitTestCase;
use SMW\Tests\Utils\UtilityFactory;
use SMWQuery as Query;
use Title;
use WikiPage;

/**
 * @group semantic-mediawiki
 * @group medium
 *
 * @license GNU GPL v2+
 * @since   2.1
 *
 * @author mwjames
 */
class TitleMoveCompleteIntegrationTest extends MwDBaseUnitTestCase {

	private $mwHooksHandler;
	private $queryResultValidator;
	private $applicationFactory;
	private $toBeDeleted = [];
	private $pageCreator;

	protected function setUp() {
		parent::setUp();

		$utilityFactory = $this->testEnvironment->getUtilityFactory();

		$this->applicationFactory = ApplicationFactory::getInstance();
		$this->queryResultValidator = $utilityFactory->newValidatorFactory()->newQueryResultValidator();

		$this->mwHooksHandler = $utilityFactory->newMwHooksHandler();
		$this->mwHooksHandler->deregisterListedHooks();

		$this->mwHooksHandler->register(
			'TitleMoveComplete',
			$this->mwHooksHandler->getHookRegistry()->getHandlerFor( 'TitleMoveComplete' )
		);

		$this->pageCreator = $utilityFactory->newPageCreator();

		$this->testEnvironment->addConfiguration(
			'smwgEnabledDeferredUpdate',
			false
		);
	}

	protected function tearDown() {

		$this->mwHooksHandler->restoreListedHooks();
		$this->testEnvironment->tearDown();

		$pageDeleter = UtilityFactory::getInstance()->newPageDeleter();
		$pageDeleter->doDeletePoolOfPages( $this->toBeDeleted );

		parent::tearDown();
	}

	public function testPageMoveWithCreationOfRedirectTarget() {

		$oldTitle = Title::newFromText( __METHOD__ . '-old' );
		$expectedNewTitle = Title::newFromText( __METHOD__ . '-new' );

		$this->assertNull(
			WikiPage::factory( $expectedNewTitle )->getRevision()
		);

		$this->pageCreator->createPage( $oldTitle );
		$result = $this->pageCreator->doMoveTo( $expectedNewTitle, true );

		$this->assertTrue( $result );

		$this->assertNotNull(
			WikiPage::factory( $oldTitle )->getRevision()
		);

		$this->assertNotNull(
			WikiPage::factory( $expectedNewTitle )->getRevision()
		);

		$this->toBeDeleted = [
			$oldTitle,
			$expectedNewTitle
		];
	}

	public function testPageMoveWithRemovalOfOldPage() {

		// Further hooks required to ensure in-text annotations can be used for queries
		$this->mwHooksHandler->register(
			'InternalParseBeforeLinks',
			$this->mwHooksHandler->getHookRegistry()->getHandlerFor( 'InternalParseBeforeLinks' )
		);

		$this->mwHooksHandler->register(
			'LinksUpdateConstructed',
			$this->mwHooksHandler->getHookRegistry()->getHandlerFor( 'LinksUpdateConstructed' )
		);

		$title = Title::newFromText( __METHOD__ . '-old' );
		$expectedNewTitle = Title::newFromText( __METHOD__ . '-new' );

		$this->assertNull(
			WikiPage::factory( $expectedNewTitle )->getRevision()
		);

		$this->pageCreator
			->createPage( $title )
			->doEdit( '[[Has function hook test::PageCompleteMove]]' );

		$this->pageCreator->doMoveTo( $expectedNewTitle, false );

		$this->testEnvironment->executePendingDeferredUpdates();

		$this->assertNull(
			WikiPage::factory( $title )->getRevision()
		);

		$this->assertNotNull(
			WikiPage::factory( $expectedNewTitle )->getRevision()
		);

		/**
		 * @query {{#ask: [[Has function hook test::PageCompleteMove]] }}
		 */
		$description = new SomeProperty(
			DIProperty::newFromUserLabel( 'Has function hook test' ),
			new ValueDescription( new DIWikiPage( 'PageCompleteMove', 0 ), null, SMW_CMP_EQ )
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
			[ DIWikiPage::newFromTitle( $expectedNewTitle ) ],
			$queryResult
		);

		$this->toBeDeleted = [
			$title,
			$expectedNewTitle
		];
	}

	public function testPredefinedPropertyPageIsNotMovable() {

		$this->mwHooksHandler->register(
			'TitleIsMovable',
			$this->mwHooksHandler->getHookRegistry()->getHandlerFor( 'TitleIsMovable' )
		);

		$title = Title::newFromText( 'Modification date', SMW_NS_PROPERTY );
		$expectedNewTitle = Title::newFromText( __METHOD__, SMW_NS_PROPERTY );

		$this->pageCreator->createPage( $title );
		$this->pageCreator->doMoveTo( $expectedNewTitle, true );

		$this->assertNotNull(
			WikiPage::factory( $title )->getRevision()
		);

		// Required due to how MoveTo/Title uses the `TitleIsMovable` hook
		if ( version_compare(MW_VERSION, '1.34', '>=' ) ) {
			$this->assertNull(
				WikiPage::factory( $expectedNewTitle )->getRevision()
			);
		}

		$this->toBeDeleted = [
			$title,
			$expectedNewTitle
		];
	}

}

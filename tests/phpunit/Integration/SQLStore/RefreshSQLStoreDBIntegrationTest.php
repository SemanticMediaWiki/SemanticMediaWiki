<?php

namespace SMW\Tests\Integration\SQLStore;

use MediaWiki\MediaWikiServices;
use MediaWiki\Parser\Parser;
use MediaWiki\Title\Title;
use SMW\DataItems\Property;
use SMW\DataItems\WikiPage;
use SMW\DataModel\SemanticData;
use SMW\Tests\SMWIntegrationTestCase;
use SMW\Tests\Utils\PageCreator;
use SMW\Tests\Utils\PageDeleter;

/**
 * @group SMW
 * @group SMWExtension
 * @group semantic-mediawiki-integration
 * @group mediawiki-database
 * @group Database
 * @group medium
 *
 * @license GPL-2.0-or-later
 * @since 1.9
 *
 * @author mwjames
 */
class RefreshSQLStoreDBIntegrationTest extends SMWIntegrationTestCase {

	private $title;
	private $pageDeleter;
	private $pageCreator;
	private array $parsedPages = [];

	protected function setUp(): void {
		parent::setUp();

		$this->pageDeleter = new PageDeleter();
		$this->pageCreator = new PageCreator();
	}

	public function tearDown(): void {
		if ( $this->title !== null ) {
			$this->pageDeleter->deletePage( $this->title );
		}

		parent::tearDown();
	}

	/**
	 * @dataProvider titleProvider
	 */
	public function testAfterPageCreation_StoreHasDataToRefreshWithoutJobs( $ns, $name, $iw ) {
		$this->title = MediaWikiServices::getInstance()->getTitleFactory()->newFromText( $name );

		$this->pageCreator->createPage( $this->title );

		$this->assertStoreHasDataToRefresh( false );
	}

	/**
	 * @dataProvider titleProvider
	 */
	public function testAfterPageCreation_StoreHasDataToRefreshWitJobs( $ns, $name, $iw ) {
		$this->title = MediaWikiServices::getInstance()->getTitleFactory()->newFromText( $name );

		$this->pageCreator->createPage( $this->title );

		$this->assertStoreHasDataToRefresh( true );
	}

	public function testExistingPageIsNotParsedWhenRebuildingItsEntityId(): void {
		$this->title = Title::makeTitle( NS_MAIN, 'Rebuild dispatch existing page' );
		$this->pageCreator->createPage( $this->title );
		$subject = WikiPage::newFromTitle( $this->title );
		$this->storeLinkFrom( $subject );
		$this->recordParsedPages();

		$this->rebuildEntityIdOf( $subject );

		$this->assertNotContains( $this->title->getDBkey(), $this->parsedPages );
	}

	public function testDataOfEntityWithoutPageIsClearedWhenRebuildingItsEntityId(): void {
		$subject = new WikiPage( 'Rebuild_dispatch_missing_page', NS_MAIN );
		$this->storeLinkFrom( $subject );

		$this->rebuildEntityIdOf( $subject );

		$this->assertSame(
			[],
			$this->getStore()->getSemanticData( $subject )->getPropertyValues( $this->newLinkProperty() )
		);
	}

	private function storeLinkFrom( WikiPage $subject ): void {
		$semanticData = new SemanticData( $subject );
		$semanticData->addPropertyObjectValue( $this->newLinkProperty(), new WikiPage( 'Link_target', NS_MAIN ) );

		$this->getStore()->updateData( $semanticData );
	}

	private function newLinkProperty(): Property {
		return new Property( 'Rebuild_dispatch_link' );
	}

	private function recordParsedPages(): void {
		$this->setTemporaryHook(
			'ParserAfterTidy',
			function ( Parser $parser ) {
				$this->parsedPages[] = $parser->getPage()?->getDBkey();
			},
			false
		);
	}

	/**
	 * Rebuilds only the ID range holding the entity's SMW ID, which differs from its page ID
	 */
	private function rebuildEntityIdOf( WikiPage $subject ): void {
		$id = $this->getStore()->getObjectIds()->getId( $subject );

		$this->getStore()->refreshData( $id, 1, false, false )->rebuild( $id );
	}

	protected function assertStoreHasDataToRefresh( $useJobs ) {
		$refreshPosition = $this->title->getArticleID();

		$entityRebuildDispatcher = $this->getStore()->refreshData(
			$refreshPosition,
			1,
			false,
			$useJobs
		);

		$entityRebuildDispatcher->rebuild( $refreshPosition );

		$this->assertGreaterThan(
			0,
			$entityRebuildDispatcher->getEstimatedProgress()
		);
	}

	public function titleProvider() {
		$provider = [];

	// $provider[] = array( NS_MAIN, 'withInterWiki', 'commons' );
		$provider[] = [ NS_MAIN, 'NormalTite', '' ];
		$provider[] = [ NS_MAIN, 'UseUpdateJobs', '' ];

		return $provider;
	}

}

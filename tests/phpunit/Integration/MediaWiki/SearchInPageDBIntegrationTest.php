<?php

namespace SMW\Tests\Integration\MediaWiki;

use ExtensionRegistry;
use SMW\MediaWiki\Search\ExtendedSearchEngine;
use SMW\Tests\SMWIntegrationTestCase;
use SMW\Tests\Utils\PageCreator;
use SMW\Tests\Utils\PageDeleter;
use SMW\Tests\Utils\UtilityFactory;
use Title;

/**
 * @group SMW
 * @group SMWExtension
 *
 * @group semantic-mediawiki-integration
 * @group mediawiki-database
 * @group Database
 *
 * @group medium
 *
 * @license GNU GPL v2+
 * @since 2.1
 *
 * @author mwjames
 */
class SearchInPageDBIntegrationTest extends SMWIntegrationTestCase {

	protected function setUp(): void {
		parent::setUp();

		$mwHooksHandler = UtilityFactory::getInstance()->newMwHooksHandler();
		$mwHooksHandler->invokeHooksFromRegistry();
	}

	public function testSearchForPageValueAsTerm() {
		$propertyPage = Title::newFromText( 'Has some page value', SMW_NS_PROPERTY );
		$targetPage = Title::newFromText( __METHOD__ );

		$pageCreator = new PageCreator();

		$pageCreator
			->createPage( $propertyPage )
			->doEdit( '[[Has type::Page]]' );

		$pageCreator
			->createPage( $targetPage )
			->doEdit( '[[Has some page value::Foo]]' );

		$this->testEnvironment->executePendingDeferredUpdates();

		$search = new ExtendedSearchEngine();
		$results = $search->searchText( '[[Has some page value::Foo]]' );

		$this->assertInstanceOf(
			'\SMW\MediaWiki\Search\SearchResultSet',
			$results
		);

		$this->assertSame(
			1,
			$results->getTotalHits()
		);

		$pageDeleter = new PageDeleter();
		$pageDeleter->deletePage( $targetPage );
		$pageDeleter->deletePage( $propertyPage );
	}

	public function testSearchForGeographicCoordinateValueAsTerm() {
		if ( !ExtensionRegistry::getInstance()->isLoaded( 'Maps' ) ) {
			$this->markTestSkipped( "Requires 'Geographic coordinate' to be a supported data type (see Semantic Maps)" );
		}

		$propertyPage = Title::newFromText( 'Has coordinates', SMW_NS_PROPERTY );
		$targetPage = Title::newFromText( __METHOD__ );

		$pageCreator = new PageCreator();

		$pageCreator
			->createPage( $propertyPage )
			->doEdit( '[[Has type::Geographic coordinate]]' );

		$pageCreator
			->createPage( $targetPage )
			->doEdit( "[[Has coordinates::52°31'N, 13°24'E]]" );

		$this->testEnvironment->executePendingDeferredUpdates();

		$search = new ExtendedSearchEngine();
		$results = $search->searchText( "[[Has coordinates::52°31'N, 13°24'E]]" );

		$this->assertInstanceOf(
			'\SMW\MediaWiki\Search\SearchResultSet',
			$results
		);

		if ( is_a( $this->getStore(), '\SMW\SPARQLStore\SPARQLStore' ) ) {
			$this->markTestIncomplete( "Test was marked as incomplete because the SPARQLStore doesn't support the Geo data type" );
		}

		$this->testEnvironment->executePendingDeferredUpdates();

		$this->assertSame(
			1,
			$results->getTotalHits()
		);

		$pageDeleter = new PageDeleter();
		$pageDeleter->deletePage( $targetPage );
		$pageDeleter->deletePage( $propertyPage );
	}

}
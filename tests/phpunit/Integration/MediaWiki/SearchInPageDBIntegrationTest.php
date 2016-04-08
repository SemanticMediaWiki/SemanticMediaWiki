<?php

namespace SMW\Tests\Integration\MediaWiki;

use SMW\MediaWiki\Search\Search;
use SMW\Tests\MwDBaseUnitTestCase;
use SMW\Tests\Utils\PageCreator;
use SMW\Tests\Utils\PageDeleter;
use Title;

/**
 * @group SMW
 * @group SMWExtension
 *
 * @group semantic-mediawiki-integration
 * @group mediawiki-database
 *
 * @group medium
 *
 * @license GNU GPL v2+
 * @since 2.1
 *
 * @author mwjames
 */
class SearchInPageDBIntegrationTest extends MwDBaseUnitTestCase {

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

		$search = new Search();
		$results = $search->searchTitle( '[[Has some page value::Foo]]' );

		$this->assertInstanceOf(
			'\SMW\MediaWiki\Search\SearchResultSet',
			$results
		);

		$this->assertEquals(
			1,
			$results->getTotalHits()
		);

		$pageDeleter = new PageDeleter();
		$pageDeleter->deletePage( $targetPage );
		$pageDeleter->deletePage( $propertyPage );
	}

	public function testSearchForGeographicCoordinateValueAsTerm() {

		if ( !defined( 'SM_VERSION' ) ) {
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

		$search = new Search();
		$results = $search->searchTitle( "[[Has coordinates::52°31'N, 13°24'E]]" );

		$this->assertInstanceOf(
			'\SMW\MediaWiki\Search\SearchResultSet',
			$results
		);

		if ( is_a( $this->getStore(), '\SMW\SPARQLStore\SPARQLStore' ) ) {
			$this->markTestIncomplete( "Test was marked as incomplete because the SPARQLStore doesn't support the Geo data type" );
		}

		$this->testEnvironment->executePendingDeferredUpdates();

		$this->assertEquals(
			1,
			$results->getTotalHits()
		);

		$pageDeleter = new PageDeleter();
		$pageDeleter->deletePage( $targetPage );
		$pageDeleter->deletePage( $propertyPage );
	}

}

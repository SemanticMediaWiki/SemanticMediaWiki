<?php

namespace SMW\Tests\Integration\MediaWiki;

use LinksUpdate;
use ParserOutput;
use SMW\DIWikiPage;
use SMW\Tests\MwDBaseUnitTestCase;
use SMW\Tests\Utils\PageCreator;
use Title;

/**
 *
 * @group SMW
 * @group SMWExtension
 * @group semantic-mediawiki-integration
 * @group mediawiki-databaseless
 * @group medium
 *
 * @license GNU GPL v2+
 * @since   2.0
 *
 * @author mwjames
 */
class LinksUpdateEmptyParserOutputDBIntegrationTest extends MwDBaseUnitTestCase {

	public function testDoUpdate() {

		$title   = Title::newFromText( __METHOD__ );
		$subject = DIWikiPage::newFromTitle( $title );

		$pageCreator = new PageCreator();

		$pageCreator
			->createPage( $title )
			->doEdit( '[[Has some property::LinksUpdateConstructedOnEmptyParserOutput]]' );

		$propertiesCountBeforeUpdate = count( $this->getStore()->getSemanticData( $subject )->getProperties() );

		$linksUpdate = new LinksUpdate( $title, new ParserOutput() );
		$linksUpdate->doUpdate();


		$this->assertCount(
			$propertiesCountBeforeUpdate,
			$this->getStore()->getSemanticData( $subject )->getProperties()
		);
	}

}

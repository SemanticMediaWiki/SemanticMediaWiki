<?php

namespace SMW\Test;

use MWTimestamp;
use Revision;
use SMW\MediaWiki\PageInfoProvider;
use SMW\PageInfo;
use User;
use WikiPage;

/**
 * @covers \SMW\MediaWiki\PageInfoProvider
 *
 *
 * @group SMW
 * @group SMWExtension
 *
 * @license GNU GPL v3+
 * @since 3.2.0
 *
 * @author MarkAHershberger
 */
class PageInfoProviderTest extends SemanticMediaWikiTestCase {

	public function getClass() {
		return '\SMW\MediaWiki\PageInfoProvider';
	}

	private function newInstance(
		WikiPage $wikiPage = null,
		Revision $revision = null,
		User $user = null
	) {
		if ( $wikiPage === null ) {
			$title = Title::newFromText( __METHOD__ );
			$wikiPage = new WikiPage( $title );
		}

		return new PageInfoProvider( $title, $revision, $user );
	}

	public function testCanConstruct() {
		$this->assertInstanceOf( $this->getClass(), $this->newInstance() );
	}

	/**
	 * @depends testCanConstruct
	 */
	public function testCanGetCreationDate() {
		$this->assertInstanceOf( 'MWTimestamp', $this->newInstance()->getCreationDate() );
	}
}

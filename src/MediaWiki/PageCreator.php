<?php

namespace SMW\MediaWiki;

use MediaWiki\MediaWikiServices;
use MediaWiki\Page\WikiPageFactory;
use Title;
use WikiFilePage;
use WikiPage;

/**
 * @license GNU GPL v2+
 * @since 2.0
 *
 * @author mwjames
 */
class PageCreator {

	/**
	 * @since  2.0
	 *
	 * @param Title $title
	 *
	 * @return WikiPage
	 */
	public function createPage( Title $title ) {
		if ( method_exists( WikiPageFactory::class, 'newFromTitle' ) ) {
			return MediaWikiServices::getInstance()->getWikiPageFactory()->newFromTitle( $title );
		} else {
			// MW <= 1.35
			return WikiPage::factory( $title );
		}
	}

	/**
	 * @since  2.0
	 *
	 * @param Title $title
	 *
	 * @return WikiFilePage
	 */
	public function createFilePage( Title $title ) {
		return new WikiFilePage( $title );
	}

}

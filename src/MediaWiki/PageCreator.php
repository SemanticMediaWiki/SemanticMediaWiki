<?php

namespace SMW\MediaWiki;

use MediaWiki\MediaWikiServices;
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
		if ( version_compare( MW_VERSION, '1.36', '>=' ) ) {
			return MediaWikiServices::getInstance()->getWikiPageFactory()->newFromTitle( $title );
		}

		return WikiPage::factory( $title );
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

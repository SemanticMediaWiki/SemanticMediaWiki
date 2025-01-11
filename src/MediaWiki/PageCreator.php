<?php

namespace SMW\MediaWiki;

use MediaWiki\MediaWikiServices;
use Title;
use WikiFilePage;
use WikiPage;

/**
 * @license GPL-2.0-or-later
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
		return MediaWikiServices::getInstance()->getWikiPageFactory()->newFromTitle( $title );
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

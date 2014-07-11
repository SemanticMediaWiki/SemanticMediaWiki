<?php

namespace SMW\MediaWiki;

use WikiPage;
use Title;

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
	 * @param  Title $title
	 *
	 * @return WikiPage
	 */
	public function createPage( Title $title ) {
		return WikiPage::factory( $title );
	}

}

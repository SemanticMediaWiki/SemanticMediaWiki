<?php

namespace SMW\Tests\Utils;

use SMW\DIWikiPage;

use Title;
use WikiPage;

/**
 * @group SMW
 * @group SMWExtension
 *
 * @license GNU GPL v2+
 * @since 1.9.1
 */
class PageDeleter {

	/**
	 * @since 1.9.1
	 */
	public function deletePage( Title $title ) {
		$page = new WikiPage( $title );
		$page->doDeleteArticle( 'SMW system test: delete page' );
	}

	/**
	 * @since 2.1
	 *
	 * @param array $poolOfPages
	 */
	public function doDeletePoolOfPages( array $poolOfPages ) {

		foreach ( $poolOfPages as $page ) {

			if ( $page instanceof WikiPage || $page instanceof DIWikiPage ) {
				$page = $page->getTitle();
			}

			if ( is_string( $page ) ) {
				$page = Title::newFromText( $page );
			}

			if ( !$page instanceof Title ) {
				continue;
			}

			$this->deletePage( $page );
		}
	}

}

<?php

namespace SMW\Tests\Util;

use Title;
use WikiPage;

/**
 * @ingroup Test
 *
 * @group SMW
 * @group SMWExtension
 *
 * @licence GNU GPL v2+
 * @since 1.9.1
 */
class PageDeleter {

	public function deletePage( Title $title ) {
		$page = new WikiPage( $title );
		$page->doDeleteArticle( 'SMW system test: delete page' );
	}

}

<?php

namespace SMW\MediaWiki;

use Title;

/**
 * @license GNU GPL v2+
 * @since 2.0
 *
 * @author mwjames
 */
class TitleCreator {

	/**
	 * @since  2.0
	 *
	 * @param  string $text
	 *
	 * @return Title|null
	 */
	public function createFromText( $text, $namespace = NS_MAIN ) {
		return Title::newFromText( $text, $namespace );
	}

}

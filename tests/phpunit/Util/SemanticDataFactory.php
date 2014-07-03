<?php

namespace SMW\Tests\Util;

use SMW\DIWikiPage;
use SMW\SemanticData;

use Title;

/**
 * @license GNU GPL v2+
 * @since   1.9.3
 *
 * @author mwjames
 */
class SemanticDataFactory {

	/**
	 * @param string $titleAsText
	 *
	 * @return SemanticData
	 */
	public function newEmptySemanticData( $titleAsText ) {
		return new SemanticData( DIWikiPage::newFromTitle( Title::newFromText( $titleAsText ) ) );
	}

}

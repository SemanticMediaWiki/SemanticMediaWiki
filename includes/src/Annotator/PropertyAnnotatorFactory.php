<?php

namespace SMW\Annotator;

use SMW\SemanticData;
use SMw\MediaWiki\RedirectTargetFinder;

use Title;

/**
 * @license GNU GPL v2+
 * @since 2.0
 *
 * @author mwjames
 */
class PropertyAnnotatorFactory {

	/**
	 * @since 2.0
	 *
	 * @param SemanticData $semanticData
	 * @param RedirectTargetFinder $redirectTargetFinder
	 *
	 * @return RedirectPropertyAnnotator
	 */
	public function newRedirectPropertyAnnotator( SemanticData $semanticData, RedirectTargetFinder $redirectTargetFinder ) {
		return new RedirectPropertyAnnotator(
			new NullPropertyAnnotator( $semanticData ),
			$redirectTargetFinder
		);
	}

}

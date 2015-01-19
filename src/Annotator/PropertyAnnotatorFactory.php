<?php

namespace SMW\Annotator;

use SMW\MediaWiki\PageInfoProvider;
use SMw\MediaWiki\RedirectTargetFinder;
use SMW\SemanticData;
use SMW\PageInfo;

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
	 *
	 * @return NullPropertyAnnotator
	 */
	public function newNullPropertyAnnotator( SemanticData $semanticData ) {
		return new NullPropertyAnnotator( $semanticData );
	}

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
			$this->newNullPropertyAnnotator( $semanticData ),
			$redirectTargetFinder
		);
	}

	/**
	 * @since 2.0
	 *
	 * @param SemanticData $semanticData
	 * @param PageInfo $pageInfo
	 *
	 * @return PredefinedPropertyAnnotator
	 */
	public function newPredefinedPropertyAnnotator( SemanticData $semanticData, PageInfo $pageInfo ) {
		return new PredefinedPropertyAnnotator(
			$this->newNullPropertyAnnotator( $semanticData ),
			$pageInfo
		);
	}

	/**
	 * @since 2.0
	 *
	 * @param SemanticData $semanticData
	 * @param string $sortkey
	 *
	 * @return SortkeyPropertyAnnotator
	 */
	public function newSortkeyPropertyAnnotator( SemanticData $semanticData, $sortkey ) {
		return new SortkeyPropertyAnnotator(
			$this->newNullPropertyAnnotator( $semanticData ),
			$sortkey
		);
	}

	/**
	 * @since 2.0
	 *
	 * @param SemanticData $semanticData
	 * @param array $categories
	 *
	 * @return CategoryPropertyAnnotator
	 */
	public function newCategoryPropertyAnnotator( SemanticData $semanticData, array $categories ) {
		return new CategoryPropertyAnnotator(
			$this->newNullPropertyAnnotator( $semanticData ),
			$categories
		);
	}

}

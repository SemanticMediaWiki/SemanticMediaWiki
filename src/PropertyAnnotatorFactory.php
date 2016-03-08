<?php

namespace SMW;

use SMW\PropertyAnnotator\NullPropertyAnnotator;
use SMW\PropertyAnnotator\RedirectPropertyAnnotator;
use SMW\PropertyAnnotator\PredefinedPropertyAnnotator;
use SMW\PropertyAnnotator\SortkeyPropertyAnnotator;
use SMW\PropertyAnnotator\CategoryPropertyAnnotator;
use SMW\PropertyAnnotator\MandatoryTypePropertyAnnotator;
use SMW\PropertyAnnotator\DisplayTitlePropertyAnnotator;
use SMw\MediaWiki\RedirectTargetFinder;

use SMW\Store;

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

		$predefinedPropertyAnnotator = new PredefinedPropertyAnnotator(
			$this->newNullPropertyAnnotator( $semanticData ),
			$pageInfo
		);

		$predefinedPropertyAnnotator->setPredefinedPropertyList(
			ApplicationFactory::getInstance()->getSettings()->get( 'smwgPageSpecialProperties' )
		);

		return $predefinedPropertyAnnotator;
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
	 * @since 2.4
	 *
	 * @param SemanticData $semanticData
	 * @param string|false $displayTitle
	 *
	 * @return DisplayTitlePropertyAnnotator
	 */
	public function newDisplayTitlePropertyAnnotator( SemanticData $semanticData, $displayTitle ) {
		return new DisplayTitlePropertyAnnotator(
			$this->newNullPropertyAnnotator( $semanticData ),
			$displayTitle
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

		$categoryPropertyAnnotator = new CategoryPropertyAnnotator(
			$this->newNullPropertyAnnotator( $semanticData ),
			$categories
		);

		$categoryPropertyAnnotator->setShowHiddenCategoriesState(
			ApplicationFactory::getInstance()->getSettings()->get( 'smwgShowHiddenCategories' )
		);

		$categoryPropertyAnnotator->setCategoryInstanceUsageState(
			ApplicationFactory::getInstance()->getSettings()->get( 'smwgCategoriesAsInstances' )
		);

		$categoryPropertyAnnotator->setCategoryHierarchyUsageState(
			ApplicationFactory::getInstance()->getSettings()->get( 'smwgUseCategoryHierarchy' )
		);

		return $categoryPropertyAnnotator;
	}

	/**
	 * @since 2.2
	 *
	 * @param SemanticData $semanticData
	 *
	 * @return MandatoryTypePropertyAnnotator
	 */
	public function newMandatoryTypePropertyAnnotator( SemanticData $semanticData ) {
		return new MandatoryTypePropertyAnnotator(
			$this->newNullPropertyAnnotator( $semanticData )
		);
	}

}

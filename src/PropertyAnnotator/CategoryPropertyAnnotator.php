<?php

namespace SMW\PropertyAnnotator;

use SMW\ApplicationFactory;
use SMW\DIProperty;
use SMW\DIWikiPage;
use SMW\PropertyAnnotator;

/**
 * Handling category annotation
 *
 * @ingroup SMW
 *
 * @license GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class CategoryPropertyAnnotator extends PropertyAnnotatorDecorator {

	/**
	 * @var array
	 */
	private $categories;

	/**
	 * @var array|null
	 */
	private $hiddenCategories = null;

	/**
	 * @var boolean
	 */
	private $showHiddenCategoriesState = true;

	/**
	 * @var boolean
	 */
	private $categoryInstanceUsageState = true;

	/**
	 * @var boolean
	 */
	private $categoryHierarchyUsageState = true;

	/**
	 * @since 1.9
	 *
	 * @param PropertyAnnotator $propertyAnnotator
	 * @param array $categories
	 */
	public function __construct( PropertyAnnotator $propertyAnnotator, array $categories ) {
		parent::__construct( $propertyAnnotator );
		$this->categories = $categories;
	}

	/**
	 * @since 2.3
	 *
	 * @param boolean $showHiddenCategoriesState
	 */
	public function setShowHiddenCategoriesState( $showHiddenCategoriesState ) {
		$this->showHiddenCategoriesState = (bool)$showHiddenCategoriesState;
	}

	/**
	 * @since 2.3
	 *
	 * @param boolean $categoryInstanceUsageState
	 */
	public function setCategoryInstanceUsageState( $categoryInstanceUsageState ) {
		$this->categoryInstanceUsageState = (bool)$categoryInstanceUsageState;
	}

	/**
	 * @since 2.3
	 *
	 * @param boolean $categoryHierarchyUsageState
	 */
	public function setCategoryHierarchyUsageState( $categoryHierarchyUsageState ) {
		$this->categoryHierarchyUsageState = (bool)$categoryHierarchyUsageState;
	}

	/**
	 * @see PropertyAnnotatorDecorator::addPropertyValues
	 */
	protected function addPropertyValues() {

		$namespace = $this->getSemanticData()->getSubject()->getNamespace();

		foreach ( $this->categories as $catname ) {

			if ( !$this->showHiddenCategoriesState && $this->isHiddenCategory( $catname ) ) {
				continue;
			}

			if ( $this->categoryInstanceUsageState && ( $namespace !== NS_CATEGORY ) ) {
				$this->getSemanticData()->addPropertyObjectValue(
					new DIProperty( DIProperty::TYPE_CATEGORY ),
					new DIWikiPage( $catname, NS_CATEGORY, '' )
				);
			}

			if ( $this->categoryHierarchyUsageState && ( $namespace === NS_CATEGORY ) ) {
				$this->getSemanticData()->addPropertyObjectValue(
					new DIProperty( DIProperty::TYPE_SUBCATEGORY ),
					new DIWikiPage( $catname, NS_CATEGORY, '' )
				);
			}
		}
	}

	private function isHiddenCategory( $catName ) {

		if ( $this->hiddenCategories === null ) {

			$wikipage = ApplicationFactory::getInstance()->newPageCreator()->createPage(
				$this->getSemanticData()->getSubject()->getTitle()
			);

			$this->hiddenCategories = $wikipage->getHiddenCategories();
		}

		foreach ( $this->hiddenCategories as $hiddenCategory ) {

			if ( $hiddenCategory->getText() === $catName ) {
				return true;
			};

		}

		return false;
	}

}

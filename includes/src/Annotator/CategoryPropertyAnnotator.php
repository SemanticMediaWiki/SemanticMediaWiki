<?php

namespace SMW\Annotator;

use SMW\PropertyAnnotator;
use SMW\DIProperty;
use SMW\DIWikiPage;
use SMW\Application;

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
	protected $categories;

	/**
	 * @var array|null
	 */
	protected $hiddenCategories = null;

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

	protected function addPropertyValues() {

		$settings  = Application::getInstance()->getSettings();
		$namespace = $this->getSemanticData()->getSubject()->getNamespace();

		foreach ( $this->categories as $catname ) {

			if ( !$settings->get( 'smwgShowHiddenCategories' ) && $this->isHiddenCategory( $catname ) ) {
				continue;
			}

			if ( $settings->get( 'smwgCategoriesAsInstances' ) && ( $namespace !== NS_CATEGORY ) ) {
				$this->getSemanticData()->addPropertyObjectValue(
					new DIProperty( DIProperty::TYPE_CATEGORY ),
					new DIWikiPage( $catname, NS_CATEGORY, '' )
				);
			}

			if ( $settings->get( 'smwgUseCategoryHierarchy' ) && ( $namespace === NS_CATEGORY ) ) {
				$this->getSemanticData()->addPropertyObjectValue(
					new DIProperty( DIProperty::TYPE_SUBCATEGORY ),
					new DIWikiPage( $catname, NS_CATEGORY, '' )
				);
			}
		}
	}

	protected function isHiddenCategory( $catName ) {

		if ( $this->hiddenCategories === null ) {
			$wikipage = Application::getInstance()->newPageCreator()->createPage( $this->getSemanticData()->getSubject()->getTitle() );
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

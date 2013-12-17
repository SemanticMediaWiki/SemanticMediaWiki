<?php

namespace SMW;

use Wikipage;

/**
 * Handling category annotation
 *
 * @ingroup SMW
 *
 * @licence GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class CategoryPropertyAnnotator extends PropertyAnnotatorDecorator {

	/** @var array */
	protected $categories;

	/** @var array|null */
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

	/**
	 * @since 1.9
	 */
	protected function addPropertyValues() {

		$settings  = $this->withContext()->getSettings();
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

		$this->setState( 'updateOutput' );
	}

	/**
	 * Whether a category is specified as hidden
	 *
	 * @since 1.9
	 *
	 * @param  $catName
	 *
	 * @return boolean
	 */
	protected function isHiddenCategory( $catName ) {

		if ( $this->hiddenCategories === null ) {
			$wikipage = Wikipage::factory( $this->getSemanticData()->getSubject()->getTitle() );
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

<?php

namespace SMW\PropertyAnnotators;

use SMW\PropertyAnnotator;

/**
 * @license GNU GPL v2+
 * @since 2.4
 *
 * @author mwjames
 */
class DisplayTitlePropertyAnnotator extends PropertyAnnotatorDecorator {

	/**
	 * @var string|false
	 */
	private $displayTitle;

	/**
	 * @var string
	 */
	private $defaultSort;

	/**
	 * @var boolean
	 */
	private $canCreateAnnotation = true;

	/**
	 * @since 2.4
	 *
	 * @param PropertyAnnotator $propertyAnnotator
	 * @param string|false $displayTitle
	 * @param string $defaultSort
	 */
	public function __construct( PropertyAnnotator $propertyAnnotator, $displayTitle = false, $defaultSort = '' ) {
		parent::__construct( $propertyAnnotator );
		$this->displayTitle = $displayTitle;
		$this->defaultSort = $defaultSort;
	}

	/**
	 * @see SMW_DV_WPV_DTITLE in $GLOBALS['smwgDVFeatures']
	 *
	 * @since 2.5
	 *
	 * @param boolean $canCreateAnnotation
	 */
	public function canCreateAnnotation( $canCreateAnnotation ) {
		$this->canCreateAnnotation = (bool)$canCreateAnnotation;
	}

	protected function addPropertyValues() {

		if ( !$this->canCreateAnnotation || !$this->displayTitle || $this->displayTitle === '' ) {
			return;
		}

		// #1439, #1611
		$dataItem = $this->dataItemFactory->newDIBlob(
			strip_tags( htmlspecialchars_decode( $this->displayTitle, ENT_QUOTES ) )
		);

		$this->getSemanticData()->addPropertyObjectValue(
			$this->dataItemFactory->newDIProperty( '_DTITLE' ),
			$dataItem
		);

		// If the defaultSort is empty then no explicit sortKey was expected
		// therefore use the title content before the SortKeyPropertyAnnotator
		if ( $this->defaultSort === '' ) {
			$this->getSemanticData()->addPropertyObjectValue(
				$this->dataItemFactory->newDIProperty( '_SKEY' ),
				$dataItem
			);
		}
	}

}

<?php

namespace SMW\PropertyAnnotator;

use SMW\DIProperty;
use SMW\PropertyAnnotator;
use SMWDIBlob as DIBlob;

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

	protected function addPropertyValues() {

		if ( !$this->displayTitle || $this->displayTitle === '' ) {
			return;
		}

		// #1439
		$dataItem = $this->dataItemFactory->newDIBlob(
			strip_tags(  $this->displayTitle )
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

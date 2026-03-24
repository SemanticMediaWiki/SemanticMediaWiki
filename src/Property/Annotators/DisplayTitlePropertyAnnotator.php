<?php

namespace SMW\Property\Annotators;

use SMW\Property\Annotator;

/**
 * @license GPL-2.0-or-later
 * @since 2.4
 *
 * @author mwjames
 */
class DisplayTitlePropertyAnnotator extends PropertyAnnotatorDecorator {

	private bool $canCreateAnnotation = true;

	/**
	 * @since 2.4
	 */
	public function __construct(
		Annotator $propertyAnnotator,
		private $displayTitle = false,
		private $defaultSort = '',
	) {
		parent::__construct( $propertyAnnotator );
	}

	/**
	 * @see SMW_DV_WPV_DTITLE in $GLOBALS['smwgDVFeatures']
	 *
	 * @since 2.5
	 *
	 * @param bool $canCreateAnnotation
	 */
	public function canCreateAnnotation( $canCreateAnnotation ): void {
		$this->canCreateAnnotation = (bool)$canCreateAnnotation;
	}

	protected function addPropertyValues(): void {
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

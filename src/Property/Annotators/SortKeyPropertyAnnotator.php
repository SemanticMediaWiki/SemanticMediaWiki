<?php

namespace SMW\Property\Annotators;

use SMW\DIProperty;
use SMW\Property\Annotator;

/**
 * @license GPL-2.0-or-later
 * @since 1.9
 *
 * @author mwjames
 */
class SortKeyPropertyAnnotator extends PropertyAnnotatorDecorator {

	/**
	 * @var string
	 */
	private $defaultSort;

	/**
	 * @since 1.9
	 *
	 * @param Annotator $propertyAnnotator
	 * @param string $defaultSort
	 */
	public function __construct( Annotator $propertyAnnotator, $defaultSort ) {
		parent::__construct( $propertyAnnotator );
		$this->defaultSort = $defaultSort;
	}

	protected function addPropertyValues() {
		$sortkey = $this->defaultSort ? $this->defaultSort : $this->getSemanticData()->getSubject()->getSortKey();

		$property = $this->dataItemFactory->newDIProperty(
			DIProperty::TYPE_SORTKEY
		);

		if ( !$this->getSemanticData()->hasProperty( $property ) ) {
			$this->getSemanticData()->addPropertyObjectValue(
				$property,
				$this->dataItemFactory->newDIBlob( $sortkey )
			);
		}
	}

}

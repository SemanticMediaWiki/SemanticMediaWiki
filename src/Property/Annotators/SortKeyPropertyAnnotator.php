<?php

namespace SMW\Property\Annotators;

use SMW\DataItems\Property;
use SMW\Property\Annotator;

/**
 * @license GPL-2.0-or-later
 * @since 1.9
 *
 * @author mwjames
 */
class SortKeyPropertyAnnotator extends PropertyAnnotatorDecorator {

	/**
	 * @since 1.9
	 */
	public function __construct(
		Annotator $propertyAnnotator,
		private $defaultSort,
	) {
		parent::__construct( $propertyAnnotator );
	}

	protected function addPropertyValues(): void {
		$sortkey = $this->defaultSort ?: $this->getSemanticData()->getSubject()->getSortKey();

		$property = $this->dataItemFactory->newDIProperty(
			Property::TYPE_SORTKEY
		);

		if ( !$this->getSemanticData()->hasProperty( $property ) ) {
			$this->getSemanticData()->addPropertyObjectValue(
				$property,
				$this->dataItemFactory->newDIBlob( $sortkey )
			);
		}
	}

}

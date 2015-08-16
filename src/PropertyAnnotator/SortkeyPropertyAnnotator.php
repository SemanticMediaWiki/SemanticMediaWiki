<?php

namespace SMW\PropertyAnnotator;

use SMW\DIProperty;
use SMW\PropertyAnnotator;
use SMWDIBlob as DIBlob;

/**
 * @license GNU GPL v2+
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
	 * @param PropertyAnnotator $propertyAnnotator
	 * @param string $defaultSort
	 */
	public function __construct( PropertyAnnotator $propertyAnnotator, $defaultSort ) {
		parent::__construct( $propertyAnnotator );
		$this->defaultSort = $defaultSort;
	}

	protected function addPropertyValues() {

		$sortkey = $this->defaultSort ? $this->defaultSort : $this->getSemanticData()->getSubject()->getSortKey();

		$this->getSemanticData()->addPropertyObjectValue(
			new DIProperty( DIProperty::TYPE_SORTKEY ),
			new DIBlob( $sortkey )
		);
	}

}

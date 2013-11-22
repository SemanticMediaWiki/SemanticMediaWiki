<?php

namespace SMW;

use SMWDIBlob as DIBlob;

/**
 * Handling sort key annotation
 *
 * @ingroup SMW
 *
 * @licence GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class SortKeyPropertyAnnotator extends PropertyAnnotatorDecorator {

	/** @var string */
	protected $defaultSort;

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

	/**
	 * @since 1.9
	 */
	protected function addPropertyValues() {

		$sortkey = $this->defaultSort ? $this->defaultSort : str_replace( '_', ' ', $this->getSemanticData()->getSubject()->getDBkey() );

		$this->getSemanticData()->addPropertyObjectValue(
			new DIProperty( DIProperty::TYPE_SORTKEY ),
			new DIBlob( $sortkey )
		);

		$this->setState( 'updateOutput' );
	}

}

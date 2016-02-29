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
	 * @since 2.4
	 *
	 * @param PropertyAnnotator $propertyAnnotator
	 * @param string|false $displayTitle
	 */
	public function __construct( PropertyAnnotator $propertyAnnotator, $displayTitle = false ) {
		parent::__construct( $propertyAnnotator );
		$this->displayTitle = $displayTitle;
	}

	protected function addPropertyValues() {

		if ( !$this->displayTitle || $this->displayTitle === '' ) {
			return;
		}

		$this->getSemanticData()->addPropertyObjectValue(
			new DIProperty( '_DTITLE' ),
			new DIBlob( $this->displayTitle )
		);
	}

}

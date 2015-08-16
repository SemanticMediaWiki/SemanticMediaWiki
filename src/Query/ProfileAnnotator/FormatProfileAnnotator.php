<?php

namespace SMW\Query\ProfileAnnotator;

use SMW\DIProperty;
use SMWDIBlob as DIBlob;

/**
 * @license GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class FormatProfileAnnotator extends ProfileAnnotatorDecorator {

	/**
	 * @var string
	 */
	private $format;

	/**
	 * @since 1.9
	 *
	 * @param ProfileAnnotator $profileAnnotator
	 * @param string $format
	 */
	public function __construct( ProfileAnnotator $profileAnnotator, $format ) {
		parent::__construct( $profileAnnotator );
		$this->format = $format;
	}

	/**
	 * ProfileAnnotatorDecorator::addPropertyValues
	 */
	protected function addPropertyValues() {
		$this->addQueryFormat( $this->format );
	}

	private function addQueryFormat( $format ) {
		$this->getSemanticData()->addPropertyObjectValue(
			new DIProperty( '_ASKFO' ),
			new DIBlob( $format )
		);
	}

}

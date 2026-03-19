<?php

namespace SMW\Query\ProfileAnnotators;

use SMW\DataItems\Blob;
use SMW\DataItems\Property;
use SMW\Query\ProfileAnnotator;

/**
 * @license GPL-2.0-or-later
 * @since 1.9
 *
 * @author mwjames
 */
class FormatProfileAnnotator extends ProfileAnnotatorDecorator {

	/**
	 * @since 1.9
	 */
	public function __construct(
		ProfileAnnotator $profileAnnotator,
		private $format,
	) {
		parent::__construct( $profileAnnotator );
	}

	/**
	 * ProfileAnnotatorDecorator::addPropertyValues
	 */
	protected function addPropertyValues() {
		$this->addQueryFormat( $this->format );
	}

	private function addQueryFormat( $format ): void {
		$this->getSemanticData()->addPropertyObjectValue(
			new Property( '_ASKFO' ),
			new Blob( $format )
		);
	}

}

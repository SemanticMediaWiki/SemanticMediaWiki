<?php

namespace SMW\Query\ProfileAnnotators;

use SMW\DataItems\Number;
use SMW\DataItems\Property;
use SMW\Query\ProfileAnnotator;

/**
 * @license GPL-2.0-or-later
 * @since 3.0
 *
 * @author mwjames
 */
class StatusCodeProfileAnnotator extends ProfileAnnotatorDecorator {

	/**
	 * @since 3.0
	 */
	public function __construct(
		ProfileAnnotator $profileAnnotator,
		private readonly array $statusCodes = [],
	) {
		parent::__construct( $profileAnnotator );
	}

	/**
	 * ProfileAnnotatorDecorator::addPropertyValues
	 */
	protected function addPropertyValues() {
		if ( $this->statusCodes !== [] ) {
			foreach ( $this->statusCodes as $statusCode ) {
				$this->addStatusCodeAnnotation( $statusCode );
			}
		}
	}

	private function addStatusCodeAnnotation( $statusCode ): void {
		$this->getSemanticData()->addPropertyObjectValue(
			new Property( '_ASKCO' ),
			new Number( $statusCode )
		);
	}

}

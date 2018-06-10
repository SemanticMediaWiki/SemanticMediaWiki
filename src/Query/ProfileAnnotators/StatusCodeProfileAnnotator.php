<?php

namespace SMW\Query\ProfileAnnotators;

use SMW\DIProperty;
use SMW\Query\ProfileAnnotator;
use SMWDINumber as DINumber;

/**
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class StatusCodeProfileAnnotator extends ProfileAnnotatorDecorator {

	/**
	 * @var array
	 */
	private $statusCodes = [];

	/**
	 * @since 3.0
	 *
	 * @param ProfileAnnotator $profileAnnotator
	 * @param array $statusCodes
	 */
	public function __construct( ProfileAnnotator $profileAnnotator, array $statusCodes = [] ) {
		parent::__construct( $profileAnnotator );
		$this->statusCodes = $statusCodes;
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

	private function addStatusCodeAnnotation( $statusCode ) {
		$this->getSemanticData()->addPropertyObjectValue(
			new DIProperty( '_ASKCO' ),
			new DINumber( $statusCode )
		);
	}

}

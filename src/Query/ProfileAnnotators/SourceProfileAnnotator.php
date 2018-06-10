<?php

namespace SMW\Query\ProfileAnnotators;

use SMW\DIProperty;
use SMW\Query\ProfileAnnotator;
use SMWDIBlob as DIBlob;

/**
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class SourceProfileAnnotator extends ProfileAnnotatorDecorator {

	/**
	 * @var string
	 */
	private $querySource;

	/**
	 * @since 2.5
	 *
	 * @param ProfileAnnotator $profileAnnotator
	 * @param string $querySource
	 */
	public function __construct( ProfileAnnotator $profileAnnotator, $querySource = '' ) {
		parent::__construct( $profileAnnotator );
		$this->querySource = $querySource;
	}

	/**
	 * ProfileAnnotatorDecorator::addPropertyValues
	 */
	protected function addPropertyValues() {
		if ( $this->querySource !== '' ) {
			$this->addQuerySource( $this->querySource );
		}
	}

	private function addQuerySource( $querySource ) {
		$this->getSemanticData()->addPropertyObjectValue(
			new DIProperty( '_ASKSC' ),
			new DIBlob( $querySource )
		);
	}

}

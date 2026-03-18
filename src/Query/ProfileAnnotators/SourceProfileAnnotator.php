<?php

namespace SMW\Query\ProfileAnnotators;

use SMW\DIProperty;
use SMW\Query\ProfileAnnotator;
use SMWDIBlob as DIBlob;

/**
 * @license GPL-2.0-or-later
 * @since 2.5
 *
 * @author mwjames
 */
class SourceProfileAnnotator extends ProfileAnnotatorDecorator {

	/**
	 * @since 2.5
	 */
	public function __construct(
		ProfileAnnotator $profileAnnotator,
		private $querySource = '',
	) {
		parent::__construct( $profileAnnotator );
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

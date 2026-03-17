<?php

namespace SMW\Query\ProfileAnnotators;

use SMW\DIProperty;
use SMW\Query\ProfileAnnotator;
use SMWDINumber as DINumber;

/**
 * @license GPL-2.0-or-later
 * @since 1.9
 *
 * @author mwjames
 */
class DurationProfileAnnotator extends ProfileAnnotatorDecorator {

	/**
	 * @since 1.9
	 */
	public function __construct(
		ProfileAnnotator $profileAnnotator,
		private $duration,
	) {
		parent::__construct( $profileAnnotator );
	}

	/**
	 * ProfileAnnotatorDecorator::addPropertyValues
	 */
	protected function addPropertyValues() {
		if ( $this->duration > 0 ) {
			$this->addGreaterThanZeroQueryDuration( $this->duration );
		}
	}

	private function addGreaterThanZeroQueryDuration( $duration ) {
		$this->getSemanticData()->addPropertyObjectValue(
			new DIProperty( '_ASKDU' ),
			new DINumber( $duration )
		);
	}

}

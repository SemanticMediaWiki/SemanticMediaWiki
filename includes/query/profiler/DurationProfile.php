<?php

namespace SMW\Query\Profiler;

use SMW\DIProperty;
use SMWDINumber as DINumber;

/**
 * Adds duration profiling annotation
 *
 * @ingroup SMW
 *
 * @licence GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class DurationProfile extends ProfileAnnotatorDecorator {

	/** @var integer */
	protected $duration;

	/**
	 * @since 1.9
	 *
	 * @param ProfileAnnotator $profileAnnotator
	 */
	public function __construct( ProfileAnnotator $profileAnnotator, $duration ) {
		parent::__construct( $profileAnnotator );
		$this->duration = $duration;
	}

	/**
	 * @since 1.9
	 */
	protected function addPropertyValues() {
		if ( $this->duration > 0 ) {
			$this->addGreaterThanZeroQueryDuration( $this->duration );
		}
	}

	/**
	 * @since 1.9
	 */
	private function addGreaterThanZeroQueryDuration( $duration ) {
		$this->getSemanticData()->addPropertyObjectValue(
			new DIProperty( '_ASKDU' ),
			new DINumber( $duration )
		);
	}

}
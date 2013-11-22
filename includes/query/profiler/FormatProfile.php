<?php

namespace SMW\Query\Profiler;

use SMW\DIProperty;
use SMWDIBlob as DIBlob;

/**
 * Provides access to Format profiling data
 *
 * @ingroup SMW
 *
 * @licence GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class FormatProfile extends ProfileAnnotatorDecorator {

	/** @var array */
	protected $format;

	/**
	 * @since 1.9
	 *
	 * @param ProfileAnnotator $profileAnnotator
	 */
	public function __construct( ProfileAnnotator $profileAnnotator, $format ) {
		parent::__construct( $profileAnnotator );
		$this->format = $format;
	}

	/**
	 * @since 1.9
	 */
	protected function addPropertyValues() {
		$this->addQueryFormat( $this->format );
	}

	/**
	 * @since 1.9
	 */
	private function addQueryFormat( $format ) {
		$this->getSemanticData()->addPropertyObjectValue(
			new DIProperty( '_ASKFO' ),
			new DIBlob( $format )
		);
	}

}
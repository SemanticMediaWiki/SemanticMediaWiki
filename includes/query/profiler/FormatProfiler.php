<?php

namespace SMW;

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
class FormatProfiler extends QueryProfilerDecorator {

	/** @var array */
	protected $format;

	/**
	 * @since 1.9
	 *
	 * @param QueryProfiler $queryProfiler
	 */
	public function __construct( QueryProfiler $queryProfiler, $format ) {
		parent::__construct( $queryProfiler );
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
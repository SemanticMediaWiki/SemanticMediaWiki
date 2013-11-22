<?php

namespace SMW\Query\Profiler;

use SMW\DIProperty;

use SMWDescription as QueryDescription;
use SMWDINumber as DINumber;
use SMWDIBlob as DIBlob;

/**
 * Provides access to some QueryDescription profiling data
 *
 * @ingroup SMW
 *
 * @licence GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class DescriptionProfile extends ProfileAnnotatorDecorator {

	/** @var QueryDescription */
	protected $description;

	/**
	 * @since 1.9
	 *
	 * @param ProfileAnnotator $profileAnnotator
	 */
	public function __construct( ProfileAnnotator $profileAnnotator, QueryDescription $description ) {
		parent::__construct( $profileAnnotator );
		$this->description = $description;
	}

	/**
	 * @since 1.9
	 */
	protected function addPropertyValues() {
		$this->addQueryString( $this->description->getQueryString() );
		$this->addQuerySize( $this->description->getSize() );
		$this->addQueryDepth( $this->description->getDepth() );
	}

	/**
	 * @since 1.9
	 */
	private function addQueryString( $queryString ) {
		$this->getSemanticData()->addPropertyObjectValue(
			new DIProperty( '_ASKST' ),
			new DIBlob( $queryString )
		);
	}

	/**
	 * @since 1.9
	 */
	private function addQuerySize( $size ) {
		$this->getSemanticData()->addPropertyObjectValue(
			new DIProperty( '_ASKSI' ),
			new DINumber( $size )
		);
	}

	/**
	 * @since 1.9
	 */
	private function addQueryDepth( $depth ) {
		$this->getSemanticData()->addPropertyObjectValue(
			new DIProperty( '_ASKDE' ),
			new DINumber( $depth )
		);
	}

}
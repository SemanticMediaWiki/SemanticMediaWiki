<?php

namespace SMW\Query\ProfileAnnotators;

use SMW\DIProperty;
use SMW\Query\Language\Description;
use SMW\Query\ProfileAnnotator;
use SMWDIBlob as DIBlob;
use SMWDINumber as DINumber;

/**
 * @license GPL-2.0-or-later
 * @since 1.9
 *
 * @author mwjames
 */
class DescriptionProfileAnnotator extends ProfileAnnotatorDecorator {

	/**
	 * @since 1.9
	 */
	public function __construct(
		ProfileAnnotator $profileAnnotator,
		private readonly Description $description,
	) {
		parent::__construct( $profileAnnotator );
	}

	/**
	 * ProfileAnnotatorDecorator::addPropertyValues
	 */
	protected function addPropertyValues() {
		$this->addQueryString( $this->description->getQueryString() );
		$this->addQuerySize( $this->description->getSize() );
		$this->addQueryDepth( $this->description->getDepth() );
	}

	private function addQueryString( $queryString ) {
		$this->getSemanticData()->addPropertyObjectValue(
			new DIProperty( '_ASKST' ),
			new DIBlob( $queryString )
		);
	}

	private function addQuerySize( $size ) {
		$this->getSemanticData()->addPropertyObjectValue(
			new DIProperty( '_ASKSI' ),
			new DINumber( $size )
		);
	}

	private function addQueryDepth( $depth ) {
		$this->getSemanticData()->addPropertyObjectValue(
			new DIProperty( '_ASKDE' ),
			new DINumber( $depth )
		);
	}

}

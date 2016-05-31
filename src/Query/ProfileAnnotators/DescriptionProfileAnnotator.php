<?php

namespace SMW\Query\ProfileAnnotators;

use SMW\DIProperty;
use SMW\Query\Language\Description;
use SMW\Query\ProfileAnnotator;
use SMWDIBlob as DIBlob;
use SMWDINumber as DINumber;

/**
 * @license GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class DescriptionProfileAnnotator extends ProfileAnnotatorDecorator {

	/**
	 * @var Description
	 */
	private $description;

	/**
	 * @since 1.9
	 *
	 * @param ProfileAnnotator $profileAnnotator
	 * @param Description $description
	 */
	public function __construct( ProfileAnnotator $profileAnnotator, Description $description ) {
		parent::__construct( $profileAnnotator );
		$this->description = $description;
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

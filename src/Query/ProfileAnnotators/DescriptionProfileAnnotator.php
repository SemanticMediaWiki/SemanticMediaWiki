<?php

namespace SMW\Query\ProfileAnnotators;

use SMW\DataItems\Blob;
use SMW\DataItems\Number;
use SMW\DataItems\Property;
use SMW\Query\Language\Description;
use SMW\Query\ProfileAnnotator;

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

	private function addQueryString( $queryString ): void {
		$this->getSemanticData()->addPropertyObjectValue(
			new Property( '_ASKST' ),
			new Blob( $queryString )
		);
	}

	private function addQuerySize( $size ): void {
		$this->getSemanticData()->addPropertyObjectValue(
			new Property( '_ASKSI' ),
			new Number( $size )
		);
	}

	private function addQueryDepth( $depth ): void {
		$this->getSemanticData()->addPropertyObjectValue(
			new Property( '_ASKDE' ),
			new Number( $depth )
		);
	}

}

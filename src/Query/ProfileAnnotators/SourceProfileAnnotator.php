<?php

namespace SMW\Query\ProfileAnnotators;

use SMW\DataItems\Blob;
use SMW\DataItems\Property;
use SMW\Query\ProfileAnnotator;

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
	protected function addPropertyValues(): void {
		if ( $this->querySource !== '' ) {
			$this->addQuerySource( $this->querySource );
		}
	}

	private function addQuerySource( $querySource ): void {
		$this->getSemanticData()->addPropertyObjectValue(
			new Property( '_ASKSC' ),
			new Blob( $querySource )
		);
	}

}

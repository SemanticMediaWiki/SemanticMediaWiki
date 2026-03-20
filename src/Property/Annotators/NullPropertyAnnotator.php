<?php

namespace SMW\Property\Annotators;

use SMW\DataModel\SemanticData;
use SMW\Property\Annotator;

/**
 * Root object representing the initial data transfer object to interact with
 * a Decorator
 *
 * @ingroup SMW
 *
 * @license GPL-2.0-or-later
 * @since 1.9
 *
 * @author mwjames
 */
class NullPropertyAnnotator implements Annotator {

	/**
	 * @since 1.9
	 */
	public function __construct( private readonly SemanticData $semanticData ) {
	}

	/**
	 * @see Annotator::getSemanticData
	 *
	 * @since 1.9
	 */
	public function getSemanticData(): SemanticData {
		return $this->semanticData;
	}

	/**
	 * @see Annotator::addAnnotation
	 *
	 * @since 1.9
	 */
	public function addAnnotation() {
		return $this;
	}

}

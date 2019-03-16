<?php

namespace SMW\Property\Annotators;

use SMW\PropertyAnnotator;
use SMW\SemanticData;

/**
 * Root object representing the initial data transfer object to interact with
 * a Decorator
 *
 * @ingroup SMW
 *
 * @license GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class NullPropertyAnnotator implements PropertyAnnotator {

	/**
	 * @var SemanticData
	 */
	private $semanticData;

	/**
	 * @since 1.9
	 *
	 * @param SemanticData $semanticData
	 */
	public function __construct( SemanticData $semanticData ) {
		$this->semanticData = $semanticData;
	}

	/**
	 * @see PropertyAnnotator::getSemanticData
	 *
	 * @since 1.9
	 */
	public function getSemanticData() {
		return $this->semanticData;
	}

	/**
	 * @see PropertyAnnotator::addAnnotation
	 *
	 * @since 1.9
	 */
	public function addAnnotation() {
		return $this;
	}

}

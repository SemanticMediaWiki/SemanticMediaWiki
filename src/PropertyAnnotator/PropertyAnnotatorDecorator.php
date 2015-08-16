<?php

namespace SMW\PropertyAnnotator;

use SMW\PropertyAnnotator;

/**
 * Decorator that contains the reference to the invoked PropertyAnnotator
 *
 * @ingroup SMW
 *
 * @license GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
abstract class PropertyAnnotatorDecorator implements PropertyAnnotator {

	/**
	 * @var PropertyAnnotator
	 */
	protected $propertyAnnotator;

	/**
	 * @since 1.9
	 *
	 * @param PropertyAnnotator $propertyAnnotator
	 */
	public function __construct( PropertyAnnotator $propertyAnnotator ) {
		$this->propertyAnnotator = $propertyAnnotator;
	}

	/**
	 * @see PropertyAnnotator::getSemanticData
	 *
	 * @since 1.9
	 *
	 * @return SemanticData
	 */
	public function getSemanticData() {
		return $this->propertyAnnotator->getSemanticData();
	}

	/**
	 * @see PropertyAnnotator::addAnnotation
	 *
	 * @since 1.9
	 *
	 * @return PropertyAnnotator
	 */
	public function addAnnotation() {

		$this->propertyAnnotator->addAnnotation();
		$this->addPropertyValues();

		return $this;
	}

	/**
	 * @since 1.9
	 */
	protected abstract function addPropertyValues();

}

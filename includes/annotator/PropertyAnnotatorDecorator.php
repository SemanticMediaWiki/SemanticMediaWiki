<?php

namespace SMW;

/**
 * Decorator that contains the reference to the invoked PropertyAnnotator
 *
 * @ingroup SMW
 *
 * @licence GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
abstract class PropertyAnnotatorDecorator extends ObservableSubject implements PropertyAnnotator, ContextAware {

	/** @var PropertyAnnotator */
	protected $propertyAnnotator;

	/**
	 * @since 1.9
	 *
	 * @param PropertyAnnotator $propertyAnnotator
	 */
	public function __construct( PropertyAnnotator $propertyAnnotator ) {
		$this->propertyAnnotator = $propertyAnnotator->addAnnotation();
	}

	/**
	 * @see ContextAware::withContext
	 *
	 * @since 1.9
	 *
	 * @return ContextResource
	 */
	public function withContext() {
		return $this->propertyAnnotator->withContext();
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

}

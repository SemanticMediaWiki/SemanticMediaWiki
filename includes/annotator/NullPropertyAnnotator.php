<?php

namespace SMW;

/**
 * Root object representing the initial data transfer object to interact with
 * a Decorator
 *
 * @ingroup SMW
 *
 * @licence GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class NullPropertyAnnotator extends ObservableSubject implements PropertyAnnotator, ContextAware {

	/** @var SemanticData */
	protected $semanticData;

	/** @var ContextResource */
	protected $context;

	/**
	 * @since 1.9
	 *
	 * @param SemanticData $semanticData
	 * @param ContextResource $context
	 */
	public function __construct( SemanticData $semanticData, ContextResource $context ) {
		$this->semanticData = $semanticData;
		$this->context = $context;
	}

	/**
	 * @see ContextAware::withContext
	 *
	 * @since 1.9
	 *
	 * @return ContextResource
	 */
	public function withContext() {
		return $this->context;
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

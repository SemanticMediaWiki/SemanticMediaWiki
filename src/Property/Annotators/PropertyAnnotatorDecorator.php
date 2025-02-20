<?php

namespace SMW\Property\Annotators;

use SMW\DataItemFactory;
use SMW\Property\Annotator;
use SMW\SemanticData;

/**
 * Decorator that contains the reference to the invoked Annotator
 *
 * @ingroup SMW
 *
 * @license GPL-2.0-or-later
 * @since 1.9
 *
 * @author mwjames
 */
abstract class PropertyAnnotatorDecorator implements Annotator {

	/**
	 * @var Annotator
	 */
	protected $propertyAnnotator;

	/**
	 * @var DataItemFactory
	 */
	protected $dataItemFactory;

	/**
	 * @since 1.9
	 *
	 * @param Annotator $propertyAnnotator
	 */
	public function __construct( Annotator $propertyAnnotator ) {
		$this->propertyAnnotator = $propertyAnnotator;
		$this->dataItemFactory = new DataItemFactory();
	}

	/**
	 * @see Annotator::getSemanticData
	 *
	 * @since 1.9
	 *
	 * @return SemanticData
	 */
	public function getSemanticData() {
		return $this->propertyAnnotator->getSemanticData();
	}

	/**
	 * @see Annotator::addAnnotation
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
	abstract protected function addPropertyValues();

}

<?php

namespace SMW\Query\ProfileAnnotator;

/**
 * Decorator implementing the ProfileAnnotator interface
 *
 * @license GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
abstract class ProfileAnnotatorDecorator implements ProfileAnnotator {

	/**
	 */
	protected $profileAnnotator;

	/**
	 * @since 1.9
	 *
	 * @param ProfileAnnotator $profileAnnotator
	 */
	public function __construct( ProfileAnnotator $profileAnnotator ) {
		$this->profileAnnotator = $profileAnnotator;
	}

	/**
	 * ProfileAnnotator::getProperty
	 *
	 * @since 1.9
	 *
	 * @return DIProperty
	 */
	public function getProperty() {
		return $this->profileAnnotator->getProperty();
	}

	/**
	 * ProfileAnnotator::getContainer
	 *
	 * @since 1.9
	 *
	 * @return DIContainer
	 */
	public function getContainer() {
		return $this->profileAnnotator->getContainer();
	}

	/**
	 * @see ProfileAnnotator::getSemanticData
	 *
	 * @since 1.9
	 *
	 * @return SemanticData
	 */
	public function getSemanticData() {
		return $this->profileAnnotator->getSemanticData();
	}

	/**
	 * ProfileAnnotator::addAnnotation
	 *
	 * @since 1.9
	 *
	 * @return ProfileAnnotator
	 */
	public function addAnnotation() {
		$this->profileAnnotator->addAnnotation();
		$this->addPropertyValues();
	}

	/**
	 * @since 1.9
	 */
	protected abstract function addPropertyValues();

}

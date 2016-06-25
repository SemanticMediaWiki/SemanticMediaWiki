<?php

namespace SMW\Query\ProfileAnnotator;

use SMW\DIProperty;
use SMWDIContainer as DIContainer;

/**
 * @license GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class NullProfileAnnotator implements ProfileAnnotator {

	/**
	 * @var DIContainer
	 */
	private $container;

	/**
	 * @since 1.9
	 *
	 * @param DIContainer $container
	 */
	public function __construct( DIContainer $container ) {
		$this->container = $container;
	}

	/**
	 * @since 1.9
	 *
	 * @return array
	 */
	public function getErrors() {
		return $this->getSemanticData()->getErrors();
	}

	/**
	 * ProfileAnnotator::getProperty
	 *
	 * @since 1.9
	 *
	 * @return array
	 */
	public function getProperty() {
		return new DIProperty( '_ASK' );
	}

	/**
	 * ProfileAnnotator::getContainer
	 *
	 * @since 1.9
	 *
	 * @return DIContainer
	 */
	public function getContainer() {
		return $this->container;
	}

	/**
	 * ProfileAnnotator::getSemanticData
	 *
	 * @since 1.9
	 *
	 * @return SemanticData
	 */
	public function getSemanticData() {
		return $this->container->getSemanticData();
	}

	/**
	 * ProfileAnnotator::addAnnotation
	 *
	 * @since 1.9
	 */
	public function addAnnotation() {
	}

}

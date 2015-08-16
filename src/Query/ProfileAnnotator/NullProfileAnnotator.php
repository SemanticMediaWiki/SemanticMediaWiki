<?php

namespace SMW\Query\ProfileAnnotator;

use SMW\DIProperty;
use SMW\Subobject;

/**
 * @license GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class NullProfileAnnotator implements ProfileAnnotator {

	/**
	 * @var Subobject
	 */
	private $subobject;

	/**
	 * @var string
	 */
	private $queryId = null;

	/**
	 * @since 1.9
	 *
	 * @param Subobject $subobject
	 * @param string $queryId
	 */
	public function __construct( Subobject $subobject, $queryId ) {
		$this->subobject = $subobject;
		$this->queryId = $queryId;
	}

	/**
	 * @since 1.9
	 *
	 * @return array
	 */
	public function getErrors() {
		return $this->subobject->getErrors();
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
	 * @return SemanticData
	 */
	public function getContainer() {
		return $this->subobject->getContainer();
	}

	/**
	 * ProfileAnnotator::getSemanticData
	 *
	 * @since 1.9
	 *
	 * @return SemanticData
	 */
	public function getSemanticData() {
		return $this->subobject->getSemanticData();
	}

	/**
	 * ProfileAnnotator::addAnnotation
	 *
	 * @since 1.9
	 */
	public function addAnnotation() {
		$this->subobject->setEmptyContainerForId( $this->queryId );
	}

}

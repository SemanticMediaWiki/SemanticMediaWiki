<?php

namespace SMW\Query\Profiler;

use SMW\Subobject;
use SMW\IdGenerator;
use SMW\DIProperty;

/**
 * Provides access to the Null object for handling profiling data
 *
 * @ingroup SMW
 *
 * @licence GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class NullProfile implements ProfileAnnotator {

	/** @var Subobject */
	protected $subobject;

	/** @var string */
	protected $queryId = null;

	/**
	 * @since 1.9
	 *
	 * @param Subobject $subobject
	 * @param IdGenerator $queryId
	 */
	public function __construct( Subobject $subobject, IdGenerator $queryId ) {
		$this->subobject = $subobject;
		$this->queryId = $queryId;
	}

	/**
	 * Returns errors collected during processing
	 *
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
		$this->subobject->setSemanticData(
			$this->subobject->generateId( $this->queryId->setPrefix( '_QUERY' ) )
		);
	}

}
<?php

namespace SMW;

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
class NullProfiler implements QueryProfiler {

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
	 * QueryProfiler::getProperty
	 *
	 * @since 1.9
	 *
	 * @return array
	 */
	public function getProperty() {
		return new DIProperty( '_ASK' );
	}

	/**
	 * QueryProfiler::getContainer
	 *
	 * @since 1.9
	 *
	 * @return SemanticData
	 */
	public function getContainer() {
		return $this->subobject->getContainer();
	}

	/**
	 * QueryProfiler::getSemanticData
	 *
	 * @since 1.9
	 *
	 * @return SemanticData
	 */
	public function getSemanticData() {
		return $this->subobject->getSemanticData();
	}

	/**
	 * QueryProfiler::createProfile
	 *
	 * @since 1.9
	 */
	public function createProfile() {
		$this->subobject->setSemanticData(
			$this->subobject->generateId( $this->queryId->setPrefix( '_QUERY' ) )
		);
	}

}
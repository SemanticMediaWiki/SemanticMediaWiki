<?php

namespace SMW;

/**
 * Decorator implementing the QueryProfiler interface
 *
 * @ingroup SMW
 *
 * @licence GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
abstract class QueryProfilerDecorator implements QueryProfiler {

	/** @var QueryProfiler */
	protected $queryProfiler;

	/**
	 * @since 1.9
	 *
	 * @param QueryProfiler $queryProfiler
	 */
	public function __construct( QueryProfiler $queryProfiler ) {
		$this->queryProfiler = $queryProfiler;
	}

	/**
	 * QueryProfiler::getProperty
	 *
	 * @since 1.9
	 *
	 * @return DIProperty
	 */
	public function getProperty() {
		return $this->queryProfiler->getProperty();
	}

	/**
	 * QueryProfiler::getContainer
	 *
	 * @since 1.9
	 *
	 * @return DIContainer
	 */
	public function getContainer() {
		return $this->queryProfiler->getContainer();
	}

	/**
	 * @see QueryProfiler::getSemanticData
	 *
	 * @since 1.9
	 *
	 * @return SemanticData
	 */
	public function getSemanticData() {
		return $this->queryProfiler->getSemanticData();
	}

	/**
	 * QueryProfiler::createProfile
	 *
	 * @since 1.9
	 *
	 * @return QueryProfiler
	 */
	public function createProfile() {
		$this->queryProfiler->createProfile();
		$this->addPropertyValues();
	}

	/**
	 * @since 1.9
	 */
	protected abstract function addPropertyValues();

}

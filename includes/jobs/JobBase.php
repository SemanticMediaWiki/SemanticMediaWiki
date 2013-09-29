<?php

namespace SMW;

use Job;
use Title;

/**
 * @since 1.9
 *
 * @license GNU GPL v2+
 * @author mwjames
 */
abstract class JobBase extends Job implements DependencyRequestor {

	/** @var DependencyBuilder */
	protected $dependencyBuilder = null;

	/**
	 * @see DependencyRequestor::setDependencyBuilder
	 *
	 * @since 1.9
	 *
	 * @param DependencyBuilder $builder
	 */
	public function setDependencyBuilder( DependencyBuilder $builder ) {
		$this->dependencyBuilder = $builder;
	}

	/**
	 * @see DependencyRequestor::getDependencyBuilder
	 *
	 * @since 1.9
	 *
	 * @return DependencyBuilder
	 */
	public function getDependencyBuilder() {

		// JobBase is a top-level class and to avoid a non-instantiated object
		// a default builder is set as for when Jobs are triggered using
		// command line etc.

		if ( $this->dependencyBuilder === null ) {
			$this->dependencyBuilder = new SimpleDependencyBuilder( new SharedDependencyContainer() );
		}

		return $this->dependencyBuilder;
	}

	/**
	 * Returns invoked Title object
	 *
	 * Apparently Job::getTitle() in MW 1.19 does not exist
	 *
	 * @since  1.9
	 *
	 * @return Title
	 */
	public function getTitle() {
		return $this->title;
	}

}

<?php

namespace SMW;

use Job;
use Title;

/**
 * Job base class
 *
 * @licence GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
abstract class JobBase extends Job implements ContextAware, ContextInjector {

	/** @var ContextResource */
	protected $context = null;

	/**
	 * @since 1.9
	 *
	 * @param ContextResource
	 */
	public function invokeContext( ContextResource $context ) {
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

		// JobBase is a top-level class and to avoid a non-instantiated object
		// a default builder is set as for when Jobs are triggered without
		// injected context (during command line etc.)
		if ( $this->context === null ) {
			$this->context = new BaseContext();
		}

		return $this->context;
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

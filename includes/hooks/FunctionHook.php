<?php

namespace SMW;

/**
 * Specifies an injectable hook class
 *
 * @ingroup FunctionHook
 *
 * @licence GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
abstract class FunctionHook implements ContextAware, ContextInjector {

	/** @var ContextResource */
	protected $context;

	/**
	 * Main method that initiates the processing of the registered
	 * hook class
	 *
	 * @since  1.9
	 *
	 * @return true
	 */
	public abstract function process();

	/**
	 * @see ContextInjector::invokeContext
	 *
	 * @since  1.9
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
		return $this->context;
	}

}

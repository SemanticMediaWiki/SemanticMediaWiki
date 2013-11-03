<?php

namespace SMW;

/**
 * Register a function hook
 *
 * @ingroup FunctionHook
 *
 * @licence GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class FunctionHookRegistry implements ContextAware {

	/** @var ContextResource */
	protected $context;

	/**
	 * @since 1.9
	 *
	 * @param ContextResource $context
	 */
	public function __construct( ContextResource $context = null ) {
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

		if ( $this->context === null ) {
			$this->context = new ExtensionContext();
		}

		return $this->context;
	}

	/**
	 * Register a FunctionHook and inject an appropriate context
	 *
	 * @since  1.9
	 *
	 * @param FunctionHook $hook
	 *
	 * @return FunctionHook
	 */
	public function register( FunctionHook $hook ) {
		$hook->invokeContext( $this->withContext() );
		return $hook;
	}

}

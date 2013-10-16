<?php

namespace SMW;

/**
 * Register a function hook
 *
 * @file
 *
 * @license GNU GPL v2+
 * @since   1.9
 *
 * @author mwjames
 */

/**
 * Register a function hook
 *
 * @ingroup Hook
 */
class FunctionHookRegistry implements ContextAware {

	/** @var ContextResource */
	protected $context;

	/**
	 * @since 1.9
	 *
	 * @param ContextResource $contextObject
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
			$this->context = new BaseContext();
		}

		return $this->context;
	}

	/**
	 * Load the hook and inject it with an appropriate context
	 *
	 * @since  1.9
	 *
	 * @param FunctionHook $hook
	 *
	 * @return FunctionHook
	 */
	public function load( FunctionHook $hook ) {

		// FIXME legacy use the context instead
		$hook->setDependencyBuilder( $this->withContext()->getDependencyBuilder() );
		$hook->invokeContext( $this->withContext() );

		return $hook;
	}

	/**
	 * Method to register a hook
	 *
	 * @since  1.9
	 *
	 * @param FunctionHook $hook
	 *
	 * @return FunctionHook
	 */
	public static function register( FunctionHook $hook ) {
		$instance = new self();
		return $instance->load( $hook );
	}

}

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
class FunctionHookRegistry {

	/**
	 * Method to register a hook and its DependencyBuilder
	 *
	 * @since  1.9
	 *
	 * @param FunctionHook $hook
	 *
	 * @return FunctionHook
	 */
	public static function register( FunctionHook $hook ) {
		$hook->setDependencyBuilder( new SimpleDependencyBuilder( new SharedDependencyContainer() ) );
		return $hook;
	}

}

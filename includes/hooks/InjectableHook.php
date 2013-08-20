<?php

namespace SMW;

/**
 * Specifies an injectable hook class
 *
 * @file
 *
 * @license GNU GPL v2+
 * @since   1.9
 *
 * @author mwjames
 */

/**
 * Specifies an injectable hook class
 *
 * @ingroup Hook
 */
abstract class InjectableHook extends DependencyInjector {

	/**
	 * Main method that initiates the processing of the registered
	 * hook class
	 *
	 * @since  1.9
	 *
	 * @return true
	 */
	public abstract function process();

}

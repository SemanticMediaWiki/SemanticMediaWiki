<?php

namespace SMW;

/**
 * Provides interfaces for dependency injection
 *
 * @file
 *
 * @license GNU GPL v2+
 * @since   1.9
 *
 * @author mwjames
 */

/**
 * Interface specifying a dependency object
 *
 * @ingroup DependencyContainer
 */
interface DependencyObject {

	/**
	 * Register a dependency object
	 *
	 * @since  1.9
	 */
	public function registerObject( $objectName, $objectSignature );

}

/**
 * Interface specifying a dependency container
 *
 * @ingroup DependencyContainer
 */
interface DependencyContainer extends DependencyObject, Accessible, Changeable, Combinable {}

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

	/** A new instance is created each time the service is requested */
	const SCOPE_PROTOTYPE = 0;

	/** Same instance is used over the lifetime of a request */
	const SCOPE_SINGLETON = 1;

	/**
	 * Defines an object
	 *
	 * @since  1.9
	 *
	 * @param DependencyBuilder $builder
	 */
	public function defineObject( DependencyBuilder $builder );

}

/**
 * Interface specifying a dependency container
 *
 * @ingroup DependencyContainer
 */
interface DependencyContainer extends Accessible, Changeable, Combinable {

	/**
	 * Register a dependency object
	 *
	 * @since  1.9
	 */
	public function registerObject( $objectName, $objectSignature, $objectScope );

	/**
	 * Collects and map objects for deferred registration
	 *
	 * @since  1.9
	 */
	public function loadObjects();

}

<?php

namespace SMW;

/**
 * Interfaces specifying a DependencyBuilder
 *
 * @file
 *
 * @license GNU GPL v2+
 * @since   1.9
 *
 * @author mwjames
 */

/**
 * Interface that specifies a method to create a new object
 *
 * @ingroup DependencyBuilder
 */
interface DependencyFactory {

	/**
	 * Returns a dependency object
	 *
	 * @since  1.9
	 */
	public function newObject( $objectName );

}

/**
 * Interface that specifies methods to handle injection container and objects
 *
 * @ingroup DependencyBuilder
 */
interface DependencyBuilder extends DependencyFactory {

	/**
	 * Returns invoked dependency container object
	 *
	 * @since  1.9
	 *
	 * @return DependencyContainer
	 */
	public function getContainer();

	/**
	 * Returns an invoked constructor argument
	 *
	 * @since  1.9
	 *
	 * @param string $key
	 */
	public function getArgument( $key );

	/**
	 * Adds an argument that can be used during object creation
	 *
	 * @since  1.9
	 *
	 * @param string $key
	 * @param mixed $value
	 */
	public function addArgument( $key, $value );

}

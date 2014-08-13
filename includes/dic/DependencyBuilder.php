<?php

namespace SMW;

/**
 * Interfaces specifying a DependencyBuilder
 *
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
	 *
	 * @param string $objectName
	 * @param mixed $objectArguments
	 */
	public function newObject( $objectName, $objectArguments = null );

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
	 * Returns whether the argument is known
	 *
	 * @since  1.9
	 *
	 * @param string $key
	 *
	 * @return boolean
	 */
	public function hasArgument( $key );

	/**
	 * Adds an argument that can be used during object creation
	 *
	 * @since  1.9
	 *
	 * @param string $key
	 * @param mixed $value
	 */
	public function addArgument( $key, $value );

	/**
	 * Defines an object scope used during the build process
	 *
	 * This overrides temporarily the object scope during the build process and
	 * will only be effective during the build while the original object scope
	 * keeps intact
	 *
	 * @since  1.9
	 *
	 * @param $scope
	 */
	public function setScope( $scope );

}

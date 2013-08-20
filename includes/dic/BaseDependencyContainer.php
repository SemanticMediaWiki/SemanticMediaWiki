<?php

namespace SMW;

/**
 * Provides a DependencyContainer base class
 *
 * @file
 *
 * @license GNU GPL v2+
 * @since   1.9
 *
 * @author mwjames
 */

/**
 * Implements the DependencyContainer interface and is responsible for handling
 * object storage, and retrieval of object definitions
 *
 * @ingroup DependencyContainer
 */
abstract class BaseDependencyContainer extends ObjectStorage implements DependencyContainer {

	/**
	 * @see ObjectStorage::contains
	 *
	 * @since  1.9
	 */
	public function has( $key ) {
		return $this->contains( $key );
	}

	/**
	 * @see ObjectStorage::lookup
	 *
	 * @since  1.9
	 */
	public function get( $key ) {
		return $this->lookup( $key );
	}

	/**
	 * @see ObjectStorage::attach
	 *
	 * @since  1.9
	 */
	public function set( $key, $value ) {
		return $this->attach( $key, $value );
	}

	/**
	 * @see ObjectStorage::detach
	 *
	 * @since  1.9
	 */
	public function remove( $key ) {
		return $this->detach( $key );
	}

	/**
	 * Returns storage array
	 *
	 * @since 1.9
	 *
	 * @return array
	 */
	public function toArray() {
		return $this->storage;
	}

	/**
	 * Merges elements of one or more arrays together
	 *
	 * @since 1.9
	 *
	 * @param array $mergeable
	 */
	public function merge( array $mergeable ) {
		$this->storage = array_merge( $this->storage, $mergeable );
	}

	/**
	 * Register an object via magic method __set
	 *
	 * @par Example:
	 * @code
	 *  $container = new EmptyDependencyContainer()
	 *
	 *  // Eager loading (do everything when asked)
	 *  $container->title = new Title() or
	 *
	 *  // Lazy loading (only do an instanitation when required)
	 *  $container->diWikiPage = function ( DependencyBuilder $builder ) {
	 *    return DIWikiPage::newFromTitle( $builder->getArgument( 'Title' ) );
	 *  } );
	 * @endcode
	 *
	 * @since  1.9
	 *
	 * @param string $key
	 * @param mixed $value
	 */
	public function __set( $objectName, $objectSignature ) {
		$this->set( $objectName, $objectSignature );
	}

	/**
	 * Register an object
	 *
	 * @par Example:
	 * @code
	 *  $container = new EmptyDependencyContainer()
	 *
	 *  // Eager loading (do everything when asked)
	 *  $container->registerObject( 'Title', new Title() ) or
	 *
	 *  // Lazy loading (only do an instanitation when required)
	 *  $container->registerObject( 'DIWikiPage', function ( DependencyBuilder $builder ) {
	 *    return DIWikiPage::newFromTitle( $builder->getArgument( 'Title' ) );
	 *  } );
	 * @endcode
	 *
	 * @since  1.9
	 *
	 * @param string $objectName
	 * @param mixed $signature
	 */
	public function registerObject( $objectName, $objectSignature ) {
		$this->set( $objectName, $objectSignature );
	}

}

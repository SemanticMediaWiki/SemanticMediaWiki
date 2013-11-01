<?php

namespace SMW;

/**
 * Implements the DependencyContainer interface and is responsible for handling
 * object storage, and retrieval of object definitions
 *
 * Examples and a more exhaustive description can be found at /docs/dic.md
 *
 * @ingroup DependencyContainer
 *
 * @licence GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
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
	 * @see ObjectStorage::detach
	 *
	 * @since  1.9
	 */
	public function toArray() {
		return $this->storage;
	}

	/**
	 * Merges elements of one or more arrays together
	 *
	 * @since 1.9
	 *
	 * @return array
	 */
	public function merge( array $mergeable ) {
		$this->storage = array_merge_recursive( $this->storage, $mergeable );
	}

	/**
	 * Register an object via magic method __set
	 *
	 * @since  1.9
	 *
	 * @param string $objectName
	 * @param mixed $objectSignature
	 */
	public function __set( $objectName, $objectSignature ) {
		$this->registerObject( $objectName, $objectSignature );
	}

	/**
	 * @see DependencyContainer::registerObject
	 *
	 * @since  1.9
	 *
	 * @param string $objectName
	 * @param mixed $objectSignature an arbitrary signature of any kind such Closure, DependencyObject etc.
	 * @param mixed $objectScope
	 */
	public function registerObject( $objectName, $objectSignature, $objectScope = DependencyObject::SCOPE_PROTOTYPE ) {
		$this->set( $objectName, array( $objectSignature, $objectScope ) );
	}

	/**
	 * @see DependencyContainer::loadAllDefinitions
	 *
	 * @since  1.9
	 *
	 * @return array
	 */
	public function loadAllDefinitions() {

		if ( !$this->has( 'def_' ) ) {
			$this->set( 'def_', $this->getDefinitions() );
		}

		return $this->get( 'def_' );
	}

	/**
	 * @since  1.9
	 *
	 * @return array
	 */
	protected abstract function getDefinitions();

}

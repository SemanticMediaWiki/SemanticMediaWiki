<?php

namespace SMW;

/**
 * Implementing a ContextResource interface and returning null
 *
 * @licence GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class EmptyContext implements ContextResource {

	/** @var DependencyBuilder */
	private $dependencyBuilder = null;

	/**
	 * @since 1.9
	 */
	public function __construct() {
		$this->dependencyBuilder = $this->register( null );
	}

	/**
	 * Returns a Store object
	 *
	 * @since 1.9
	 *
	 * @return Store
	 */
	public function getStore() {
		return $this->getDependencyBuilder()->newObject( 'Store' );
	}

	/**
	 * Returns a Settings object
	 *
	 * @since 1.9
	 *
	 * @return Settings
	 */
	public function getSettings() {
		return $this->getDependencyBuilder()->newObject( 'Settings' );
	}

	/**
	 * Returns a DependencyBuilder object
	 *
	 * @since 1.9
	 *
	 * @return DependencyBuilder
	 */
	public function getDependencyBuilder() {
		return $this->dependencyBuilder;
	}

	/**
	 * Register a builder
	 *
	 * @since 1.9
	 */
	protected function register( DependencyBuilder $builder = null ) {

		if ( $builder === null ) {
			$builder = new SimpleDependencyBuilder( new NullDependencyContainer() );
		}

		$builder->getContainer()->registerObject( 'Settings', null );
		$builder->getContainer()->registerObject( 'Store', null );
		$builder->getContainer()->registerObject( 'ExtensionContext', $this );

		return $builder;
	}

}

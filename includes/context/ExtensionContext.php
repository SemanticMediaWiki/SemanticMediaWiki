<?php

namespace SMW;

/**
 * Default implementation of the ContextResource interface
 *
 * @ingroup SMW
 *
 * @licence GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class ExtensionContext implements ContextResource {

	/** @var DependencyBuilder */
	private $dependencyBuilder = null;

	/**
	 * @since 1.9
	 *
	 * @param DependencyBuilder|null $builder
	 */
	public function __construct( DependencyBuilder $builder = null ) {
		$this->dependencyBuilder = $this->register( $builder );
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
	 * @note Always register a builder with a self-reference to the current
	 * context object to ensure all objects are accessing the context derive
	 * from the same "root"
	 *
	 * @since 1.9
	 */
	protected function register( DependencyBuilder $builder = null ) {

		if ( $builder === null ) {
			$builder = new SimpleDependencyBuilder( new SharedDependencyContainer() );
		}

		$builder->getContainer()->registerObject( 'ExtensionContext', $this );
		return $builder;
	}

}

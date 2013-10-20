<?php

namespace SMW;

/**
 * Abstract class that implements the DependencyRequestor to enable convenience
 * access to an injected DependencyBuilder
 *
 * @par Example:
 * @code
 *  class FooClass extends DependencyInjector { ... }
 *
 *  $fooClass = new FooClass( ... )
 *  $fooClass->setDependencyBuilder( new SimpleDependencyBuilder(
 *    new GenericDependencyContainer()
 *  ) );
 *
 *  $fooClass->getDependencyBuilder()->newObject( 'Bar' );
 * @endcode
 *
 * @ingroup DependencyRequestor
 *
 * @licence GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
abstract class DependencyInjector implements DependencyRequestor {

	/** @var DependencyBuilder */
	protected $dependencyBuilder;

	/**
	 * @see DependencyRequestor::setDependencyBuilder
	 *
	 * @since 1.9
	 *
	 * @param DependencyBuilder $builder
	 */
	public function setDependencyBuilder( DependencyBuilder $builder ) {
		$this->dependencyBuilder = $builder;
	}

	/**
	 * @see DependencyRequestor::getDependencyBuilder
	 *
	 * @since 1.9
	 *
	 * @return DependencyBuilder
	 */
	public function getDependencyBuilder() {
		return $this->dependencyBuilder;
	}
}

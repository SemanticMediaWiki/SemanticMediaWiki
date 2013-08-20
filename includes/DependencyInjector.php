<?php

namespace SMW;

use InvalidArgumentException;
use OutOfBoundsException;

/**
 * Interface specifying access to a DependencyBuilder
 *
 * @file
 *
 * @license GNU GPL v2+
 * @since   1.9
 *
 * @author mwjames
 */

/**
 * Specifies methods to access a DependencyBuilder within a client that requests
 * dependency injection
 *
 * @ingroup DependencyRequestor
 */
interface DependencyRequestor {

	/**
	 * Sets DependencyBuilder
	 *
	 * @since  1.9
	 */
	public function setDependencyBuilder( DependencyBuilder $builder );

	/**
	 * Sets DependencyBuilder
	 *
	 * @since  1.9
	 */
	public function getDependencyBuilder();

}

/**
 * Abstract class that implements the DependencyRequestor to enable convenience
 * access to an injected DependencyBuilder
 *
 * @par Example:
 * @code
 *  class ArticlePurge extends DependencyInjector { ... }
 *  $articlePurge = new ArticlePurge( ... )
 *  $articlePurge->setDependencyBuilder( new SimpleDependencyBuilder(
 *    new CommonDependencyContainer()
 *  ) );
 *  $articlePurge->getDependencyBuilder()->newObject( 'Settings' );
 * @endcode
 *
 * @since  1.9
 *
 * @ingroup DependencyRequestor
 */
abstract class DependencyInjector implements DependencyRequestor {

	/** @var DependencyBuilder */
	protected $dependencyBuilder;

	/**
	 * Injects a DependencyBuilder object
	 *
	 * @since 1.9
	 */
	public function setDependencyBuilder( DependencyBuilder $builder ) {
		$this->dependencyBuilder = $builder;
	}

	/**
	 * Returns injected DependencyBuilder object
	 *
	 * @since 1.9
	 */
	public function getDependencyBuilder() {
		return $this->dependencyBuilder;
	}
}

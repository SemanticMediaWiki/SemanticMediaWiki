<?php

namespace SMW;

use InvalidArgumentException;
use OutOfBoundsException;

/**
 * Interface specifying access to a DependencyBuilder
 *
 *
 * @license GNU GPL v2+
 * @since   1.9
 *
 * @author mwjames
 */

/**
 * Interface specifying access to a DependencyBuilder within a client that
 * requests dependency injection
 *
 * @ingroup DependencyRequestor
 */
interface DependencyRequestor {

	/**
	 * Sets DependencyBuilder
	 *
	 * @since  1.9
	 *
	 * @param DependencyBuilder $builder
	 */
	public function setDependencyBuilder( DependencyBuilder $builder );

	/**
	 * Returns invoked DependencyBuilder
	 *
	 * @since  1.9
	 *
	 * @return DependencyBuilder
	 */
	public function getDependencyBuilder();

}

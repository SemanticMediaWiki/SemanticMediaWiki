<?php

namespace SMW;

/**
 * Interface specifying a dependency object
 *
 * @ingroup DependencyContainer
 *
 * @licence GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
interface DependencyObject {

	/** A new instance is created each time the service is requested */
	const SCOPE_PROTOTYPE = 0;

	/** Same instance is used over the lifetime of a request */
	const SCOPE_SINGLETON = 1;

	/**
	 * Defines an object
	 *
	 * @since 1.9
	 *
	 * @param DependencyBuilder $builder
	 */
	public function retrieveDefinition( DependencyBuilder $builder );

}

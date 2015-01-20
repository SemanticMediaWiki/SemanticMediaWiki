<?php

namespace SMW;

/**
 * Interface specifying a dependency container
 *
 * @ingroup DependencyContainer
 *
 * @licence GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
interface DependencyContainer extends Accessible, Changeable, Combinable {

	/**
	 * Register a dependency object
	 *
	 * @since 1.9
	 *
	 * @param string $objectName
	 * @param callable|object $objectSignature
	 * @param int $objectScope DependencyObject::SCOPE_ enum value
	 *
	 * @return
	 */
	public function registerObject( $objectName, $objectSignature, $objectScope = DependencyObject::SCOPE_PROTOTYPE );

	/**
	 * Retrieves object definitions
	 *
	 * @since 1.9
	 */
	public function loadAllDefinitions();

}

<?php

namespace SMW;

/**
 * Provides an empty DependencyContainer entity
 *
 * @ingroup DependencyContainer
 *
 * @licence GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class NullDependencyContainer extends BaseDependencyContainer {

	/**
	 * @since 1.9
	 */
	protected function getDefinitions() {
		return null;
	}

}

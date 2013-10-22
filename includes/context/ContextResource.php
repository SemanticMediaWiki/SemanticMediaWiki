<?php

namespace SMW;

/**
 * Interface that describes a ContextResource object
 *
 * @ingroup SMW
 *
 * @licence GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
interface ContextResource {

	/**
	 * Returns a Store object
	 *
	 * @since 1.9
	 *
	 * @return Store
	 */
	public function getStore();

	/**
	 * Returns a Settings object
	 *
	 * @since 1.9
	 *
	 * @return Settings
	 */
	public function getSettings();

	/**
	 * Returns a DependencyBuilder object
	 *
	 * @since 1.9
	 *
	 * @return DependencyBuilder
	 */
	public function getDependencyBuilder();

}

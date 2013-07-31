<?php

namespace SMW;

/**
 * Semantic MediaWiki interface to access a configurable object (Settings)
 *
 * @file
 *
 * @license GNU GPL v2+
 * @since   1.9
 *
 * @author mwjames
 */

/**
 * Specifies an interface to access a configurable object (Settings)
 *
 * @ingroup Utility
 */
interface Configurable {

	/**
	 * Sets a Settings object
	 *
	 * @since 1.9
	 *
	 * @param Settings $settings
	 */
	public function setSettings( Settings $settings );

	/**
	 * Returns Settings object
	 *
	 * @since 1.9
	 *
	 * @return Settings
	 */
	public function getSettings();

}

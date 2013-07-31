<?php

namespace SMW;

/**
 * Semantic MediaWiki base interface to access a Store object
 *
 * @file
 *
 * @license GNU GPL v2+
 * @since   1.9
 *
 * @author mwjames
 */

/**
 * Specifies an interface to access a Store object
 *
 * @ingroup Utility
 */
interface StoreAccess {

	/**
	 * Sets a Store object
	 *
	 * @since 1.9
	 *
	 * @param Store $settings
	 */
	public function setStore( Store $store );

	/**
	 * Returns Store object
	 *
	 * @since 1.9
	 *
	 * @return Store
	 */
	public function getStore();

}

<?php

namespace SMW;

/**
 * Describes an instance that is aware of a Store object.
 *
 * @license GNU GPL v2
 * @since 2.5
 *
 * @author mwjames
 */
interface StoreAware {

	/**
	 * @since 2.5
	 *
	 * @param Store $store
	 */
	public function setStore( Store $store );

}

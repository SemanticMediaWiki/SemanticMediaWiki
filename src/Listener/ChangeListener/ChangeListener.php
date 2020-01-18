<?php

namespace SMW\Listener\ChangeListener;

/**
 * @license GNU GPL v2+
 * @since 3.2
 *
 * @author mwjames
 */
interface ChangeListener {

	/**
	 * @since 3.2
	 *
	 * @param array $attrs
	 */
	public function setAttrs( array $attrs );

	/**
	 * @since 3.2
	 *
	 * @param string $key
	 *
	 * @return boolean
	 */
	public function canTrigger( string $key );

	/**
	 * @since 3.2
	 *
	 * @param string $key
	 */
	public function trigger( string $key );

}

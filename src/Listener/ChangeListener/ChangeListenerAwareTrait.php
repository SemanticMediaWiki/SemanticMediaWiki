<?php

namespace SMW\Listener\ChangeListener;

/**
 * @license GNU GPL v2+
 * @since 3.2
 *
 * @author mwjames
 */
trait ChangeListenerAwareTrait {

	/**
	 * @var array
	 */
	private static $changeListeners = [];

	/**
	 * @since 3.2
	 */
	public function clearChangeListeners() {
		self::$changeListeners = [];
	}

	/**
	 * @since 3.2
	 *
	 * @param ChangeListener $changeListener
	 */
	public function registerChangeListener( ChangeListener $changeListener ) {
		self::$changeListeners[spl_object_hash( $changeListener )] = $changeListener;
	}

	/**
	 * @since 3.2
	 *
	 * @param ChangeListener[]|[]
	 */
	public function getChangeListeners() {
		return self::$changeListeners;
	}

}
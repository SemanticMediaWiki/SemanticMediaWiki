<?php

namespace SMW\Listener\ChangeListener;

/**
 * @license GPL-2.0-or-later
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
	public function clearChangeListeners(): void {
		self::$changeListeners = [];
	}

	/**
	 * @since 3.2
	 *
	 * @param ChangeListener $changeListener
	 */
	public function registerChangeListener( ChangeListener $changeListener ): void {
		self::$changeListeners[spl_object_hash( $changeListener )] = $changeListener;
	}

	/**
	 * @since 3.2
	 *
	 * @param ChangeListener[]|[]
	 */
	public function getChangeListeners(): array {
		return self::$changeListeners;
	}

}

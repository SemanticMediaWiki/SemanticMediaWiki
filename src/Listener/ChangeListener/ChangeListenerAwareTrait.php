<?php

namespace SMW\Listener\ChangeListener;

/**
 * @license GPL-2.0-or-later
 * @since 3.2
 *
 * @author mwjames
 */
trait ChangeListenerAwareTrait {

	private static array $changeListeners = [];

	/**
	 * @since 3.2
	 */
	public function clearChangeListeners(): void {
		self::$changeListeners = [];
	}

	/**
	 * @since 3.2
	 */
	public function registerChangeListener( ChangeListener $changeListener ): void {
		self::$changeListeners[spl_object_hash( $changeListener )] = $changeListener;
	}

	/**
	 * @since 3.2
	 */
	public function getChangeListeners(): array {
		return self::$changeListeners;
	}

}

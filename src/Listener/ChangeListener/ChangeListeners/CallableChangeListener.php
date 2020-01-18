<?php

namespace SMW\Listener\ChangeListener\ChangeListeners;

use SMW\Listener\ChangeListener\ChangeListener;
use SMW\Listener\ChangeListener\CallableChangeListenerTrait;
use SMW\Listener\ChangeListener\ChangeRecord;

/**
 * @license GNU GPL v2+
 * @since 3.2
 *
 * @author mwjames
 */
class CallableChangeListener implements ChangeListener {

	use CallableChangeListenerTrait;

	/**
	 * @since 3.2
	 *
	 * @param array $changeListeners
	 */
	public function __construct( array $changeListeners = [] ) {
		foreach ( $changeListeners as $key => $callback ) {
			$this->addListenerCallback( $key, $callback );
		}
	}

	/**
	 * @since 3.2
	 *
	 * @param string $key
	 * @param callable $callback
	 */
	public function addListenerCallback( string $key, callable $callback ) {

		if ( !isset( $this->changeListeners[$key] ) ) {
			$this->changeListeners[$key] = [];
		}

		$this->changeListeners[$key][] = $callback;
	}

}

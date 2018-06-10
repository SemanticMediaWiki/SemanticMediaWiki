<?php

namespace SMW;

use Closure;
use SMW\Exception\PropertyLabelNotResolvedException;

/**
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class ChangePropListener {

	/**
	 * @var []
	 */
	private static $listenerCallbacks = [];

	/**
	 * @var []
	 */
	private static $deferrableCallbacks = [];

	/**
	 * @since 3.0
	 */
	public static function clearListeners() {
		self::$listenerCallbacks = [];
		self::$deferrableCallbacks = [];
	}

	/**
	 * @since 3.0
	 *
	 * @param string $key
	 * @param Closure $callback
	 */
	public function addListenerCallback( $key, Closure $callback ) {

		if ( $key === '' ) {
			return;
		}

		if ( !isset( self::$listenerCallbacks[$key] ) ) {
			self::$listenerCallbacks[$key] = [];
		}

		self::$listenerCallbacks[$key][] = $callback;
	}

	/**
	 * Finalize event inception points by matching the key to a property
	 * equivalent representation.
	 *
	 * @since 3.0
	 *
	 * @param Store $store
	 */
	public function loadListeners( Store $store ) {

		foreach ( self::$listenerCallbacks as $key => $value ) {

			try {
				$property = DIProperty::newFromUserLabel( $key );
			} catch ( PropertyLabelNotResolvedException $e ) {
				continue;
			}

			$pid = $store->getObjectIds()->getSMWPropertyID(
				$property
			);

			self::$listenerCallbacks[$pid] = $value;
			unset( self::$listenerCallbacks[$key] );
		}
	}

	/**
	 * @since 3.0
	 *
	 * @param integer $pid
	 * @param array $record
	 */
	public static function record( $pid, array $record ) {

		if ( !isset( self::$listenerCallbacks[$pid] ) ) {
			return;
		}

		if ( !isset( self::$deferrableCallbacks[$pid] ) ) {
			self::$deferrableCallbacks[$pid] = [];
		}

		// Copy callbacks to the deferred list to isolate the execution
		// from the event point
		foreach ( self::$listenerCallbacks[$pid] as $callback ) {
			self::$deferrableCallbacks[$pid][] = [ $callback, $record ];
		}
	}

	/**
	 * @since 3.0
	 */
	public function callListeners() {

		if ( self::$deferrableCallbacks === [] ) {
			return;
		}

		$deferrableCallbacks = self::$deferrableCallbacks;

		$callback = function() use( $deferrableCallbacks ) {
			foreach ( $deferrableCallbacks as $pid => $records ) {
				foreach ( $records as $rec ) {
					call_user_func_array( $rec[0], [ $rec[1] ] );
				}
			}
		};

		$deferredTransactionalUpdate = ApplicationFactory::getInstance()->newDeferredTransactionalCallableUpdate(
			$callback
		);

		$deferredTransactionalUpdate->setOrigin(
			[
				'ChangePropListener::callListeners'
			]
		);

		$deferredTransactionalUpdate->commitWithTransactionTicket();
		$deferredTransactionalUpdate->pushUpdate();

		self::$deferrableCallbacks = [];
	}

}

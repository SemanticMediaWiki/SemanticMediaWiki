<?php

namespace SMW\EventDispatcher\Listener;

use Closure;
use RuntimeException;
use SMW\EventDispatcher\DispatchContext;
use SMW\EventDispatcher\EventListener;

/**
 * @license GPL-2.0-or-later
 * @since 1.0
 *
 * @author mwjames
 */
class GenericCallbackEventListener implements EventListener {

	/**
	 * @var array
	 */
	protected $callbacks = [];

	/**
	 * @var bool
	 */
	private $propagationStopState = false;

	/**
	 * @since 1.0
	 *
	 * @param Closure|callable|null $callback
	 */
	public function __construct( $callback = null ) {
		if ( $callback !== null ) {
			$this->registerCallback( $callback );
		}
	}

	/**
	 * @since 1.0
	 *
	 * @param Closure|callable $callback
	 * @throws RuntimeException
	 */
	public function registerCallback( $callback ) {
		if ( !is_callable( $callback ) ) {
			throw new RuntimeException( "Invoked object is not a valid callback or Closure" );
		}

		// While this does not build a real dependency chain, it allows for atomic
		// event handling by following FIFO
		$this->callbacks[] = $callback;
	}

	/**
	 * @since 1.0
	 *
	 * {@inheritDoc}
	 */
	public function execute( ?DispatchContext $dispatchContext = null ) {
		foreach ( $this->callbacks as $callback ) {
			call_user_func_array( $callback, [ $dispatchContext ] );
		}
	}

	/**
	 * @since 1.0
	 *
	 * @param bool $propagationStopState
	 */
	public function setPropagationStopState( $propagationStopState ) {
		$this->propagationStopState = (bool)$propagationStopState;
	}

	/**
	 * @since 1.0
	 *
	 * {@inheritDoc}
	 */
	public function isPropagationStopped() {
		return $this->propagationStopState;
	}

}

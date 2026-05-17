<?php

namespace SMW\EventDispatcher\Dispatcher;

use InvalidArgumentException;
use RuntimeException;
use SMW\EventDispatcher\DispatchContext;
use SMW\EventDispatcher\EventDispatcher;
use SMW\EventDispatcher\EventListener;
use SMW\EventDispatcher\EventListenerCollection;
use SMW\EventDispatcher\Exception\EventNotDispatchableException;
use SMW\EventDispatcher\Subscriber;
use Traversable;

/**
 * Dispatches events to registered listeners
 *
 * @license GPL-2.0-or-later
 * @since 1.0
 *
 * @author mwjames
 */
class GenericEventDispatcher implements EventDispatcher, Subscriber {

	/**
	 * @var array
	 */
	private $dispatchableListeners = [];

	/**
	 * @var bool
	 */
	private $throwOnMissingEvent = false;

	/**
	 * @since 1.0
	 *
	 * {@inheritDoc}
	 *
	 * @throws RuntimeException
	 */
	public function addListenerCollection( EventListenerCollection $listenerCollection ) {
		$collection = $listenerCollection->getCollection();

		if ( !is_array( $collection ) && !$collection instanceof Traversable ) {
			throw new RuntimeException( "Expected a traversable object" );
		}

		foreach ( $collection as $event => $listeners ) {
			foreach ( $listeners as $listener ) {
				$this->addListener( $event, $listener );
			}
		}
	}

	/**
	 * @since 1.0
	 *
	 * {@inheritDoc}
	 *
	 * @throws InvalidArgumentException
	 */
	public function addListener( $event, EventListener $listener ) {
		if ( !is_string( $event ) ) {
			throw new InvalidArgumentException( "Expected a string" );
		}

		$event = strtolower( $event );

		if ( !isset( $this->dispatchableListeners[$event] ) ) {
			$this->dispatchableListeners[$event] = [];
		}

		$this->dispatchableListeners[$event][] = $listener;
	}

	/**
	 * @since 1.0
	 *
	 * {@inheritDoc}
	 */
	public function removeListener( $event, ?EventListener $listener = null ) {
		$event = strtolower( $event );

		if ( !$this->hasEvent( $event ) ) {
			return;
		}

		if ( $listener !== null ) {
			foreach ( $this->dispatchableListeners[$event] as $key => $dispatchableListener ) {
				if ( $dispatchableListener == $listener ) {
					unset( $this->dispatchableListeners[$event][$key] );
				}
			}
		}

		if ( $listener === null || $this->dispatchableListeners[$event] === [] ) {
			unset( $this->dispatchableListeners[$event] );
		}
	}

	/**
	 * @since 1.1
	 *
	 * @param bool $throwOnMissingEvent
	 */
	public function throwOnMissingEvent( $throwOnMissingEvent ) {
		$this->throwOnMissingEvent = (bool)$throwOnMissingEvent;
	}

	/**
	 * @since 1.0
	 *
	 * {@inheritDoc}
	 */
	public function hasEvent( $event ) {
		return isset( $this->dispatchableListeners[strtolower( $event )] );
	}

	/**
	 * @since 1.0
	 *
	 * {@inheritDoc}
	 */
	public function dispatch( $event, $dispatchContext = null ) {
		$event = strtolower( $event );

		if ( !$this->hasEvent( $event ) && $this->throwOnMissingEvent ) {
			throw new EventNotDispatchableException( $event );
		} elseif ( !$this->hasEvent( $event ) ) {
			return;
		}

		if ( is_array( $dispatchContext ) ) {
			$dispatchContext = DispatchContext::newFromArray( $dispatchContext );
		}

		foreach ( $this->dispatchableListeners[$event] as $listener ) {

			$listener->execute( $dispatchContext );

			if ( $listener->isPropagationStopped() || ( $dispatchContext !== null && $dispatchContext->isPropagationStopped() ) ) {
				break;
			}
		}
	}

}

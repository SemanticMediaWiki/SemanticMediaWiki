<?php

namespace SMW\EventDispatcher\Listener;

use Closure;
use InvalidArgumentException;
use RuntimeException;
use SMW\EventDispatcher\EventListener;
use SMW\EventDispatcher\EventListenerCollection;

/**
 * @license GPL-2.0-or-later
 * @since 1.0
 *
 * @author mwjames
 */
class GenericEventListenerCollection implements EventListenerCollection {

	/**
	 * @var array
	 */
	private $collection = [];

	/**
	 * @since 1.0
	 *
	 * @param string $event
	 * @param EventListener $listener
	 *
	 * @throws InvalidArgumentException
	 */
	public function registerListener( $event, EventListener $listener ) {
		if ( !is_string( $event ) ) {
			throw new InvalidArgumentException( "Event is not a string" );
		}

		$this->addToCollection( $event, $listener );
	}

	/**
	 * @since 1.0
	 *
	 * @param string $event
	 * @param Closure|callable $callback
	 *
	 * @throws InvalidArgumentException
	 * @throws RuntimeException
	 */
	public function registerCallback( $event, $callback ) {
		if ( !is_string( $event ) ) {
			throw new InvalidArgumentException( "Event is not a string" );
		}

		if ( !is_callable( $callback ) ) {
			throw new RuntimeException( "Invoked object is not a valid callback or Closure" );
		}

		$this->addToCollection( $event, new GenericCallbackEventListener( $callback ) );
	}

	/**
	 * @since 1.0
	 *
	 * {@inheritDoc}
	 */
	public function getCollection() {
		return $this->collection;
	}

	private function addToCollection( $event, EventListener $listener ) {
		$event = strtolower( $event );

		if ( !isset( $this->collection[$event] ) ) {
			$this->collection[$event] = [];
		}

		$this->collection[$event][] = $listener;
	}

}

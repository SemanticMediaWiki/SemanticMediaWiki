<?php

namespace SMW\EventDispatcher;

use InvalidArgumentException;

/**
 * Generic context that can be added during the dispatch process to be
 * accessible to each invoked listener
 *
 * @license GPL-2.0-or-later
 * @since 1.0
 *
 * @author mwjames
 */
class DispatchContext {

	/**
	 * @var array
	 */
	private $container = [];

	/**
	 * @since 1.1
	 *
	 * @param array $container
	 *
	 * @return DispatchContext
	 */
	public static function newFromArray( array $container ) {
		$dispatchContext = new DispatchContext();

		foreach ( $container as $key => $value ) {
			$dispatchContext->set( $key, $value );
		}

		return $dispatchContext;
	}

	/**
	 * @since 1.0
	 *
	 * @param string $key
	 *
	 * @return bool
	 */
	public function has( $key ) {
		return isset( $this->container[strtolower( $key )] );
	}

	/**
	 * @since 1.0
	 *
	 * @param string $key
	 * @param mixed $value
	 */
	public function set( $key, $value ) {
		$this->container[strtolower( $key )] = $value;
	}

	/**
	 * @since 1.0
	 *
	 * @param string $key
	 *
	 * @return mixed
	 * @throws InvalidArgumentException
	 */
	public function get( $key ) {
		if ( $this->has( $key ) ) {
			return $this->container[strtolower( $key )];
		}

		throw new InvalidArgumentException( "{$key} is unknown" );
	}

	/**
	 * @since 1.0
	 *
	 * @return bool
	 */
	public function isPropagationStopped() {
		return $this->has( 'propagationstop' ) ? $this->get( 'propagationstop' ) : false;
	}

}

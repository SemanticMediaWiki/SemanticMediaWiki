<?php

namespace SMW;

use InvalidArgumentException;

/**
 * @license GPL-2.0-or-later
 * @since 3.0
 *
 * @author mwjames
 */
class Parameters {

	private array $parameters;

	/**
	 * @since 3.0
	 */
	public function __construct( array $parameters = [] ) {
		$this->parameters = $parameters;
	}

	/**
	 * @since 3.0
	 *
	 * @param string $key
	 * @param mixed $value
	 */
	public function set( $key, $value ): void {
		$this->parameters[$key] = $value;
	}

	/**
	 * @since 3.0
	 *
	 * @param string $key
	 * @param array $value
	 */
	public function merge( $key, array $value ): void {
		if ( !isset( $this->parameters[$key] ) ) {
			$this->parameters[$key] = [];
		}

		$this->parameters[$key] += $value;
	}

	/**
	 * @since 3.0
	 *
	 * @param string $key
	 *
	 * @return bool
	 */
	public function has( $key ): bool {
		return isset( $this->parameters[$key] ) || array_key_exists( $key, $this->parameters );
	}

	/**
	 * @since 3.0
	 *
	 * @param string $key
	 *
	 * @return string|array
	 * @throws InvalidArgumentException
	 */
	public function get( $key ) {
		if ( $this->has( $key ) ) {
			return $this->parameters[$key];
		}

		throw new InvalidArgumentException( "$key is an unregistered key." );
	}

}

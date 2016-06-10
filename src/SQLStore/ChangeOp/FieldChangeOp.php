<?php

namespace SMW\SQLStore\ChangeOp;

use InvalidArgumentException;

/**
 * @license GNU GPL v2+
 * @since 2.4
 *
 * @author mwjames
 */
class FieldChangeOp {

	/**
	 * @var array
	 */
	private $changeOp = array();

	/**
	 * @since 2.4
	 */
	public function __construct( array $changeOp = array() ) {
		$this->changeOp = $changeOp;
	}

	/**
	 * @since 2.4
	 *
	 * @param string $key
	 * @param mixed $value
	 */
	public function set( $key, $value ) {
		$this->changeOp[$key] = $value;
	}

	/**
	 * @since 2.4
	 *
	 * @param string $key
	 *
	 * @return boolean
	 */
	public function has( $key ) {
		return isset( $this->changeOp[$key] ) || array_key_exists( $key, $this->changeOp );
	}

	/**
	 * @since 2.4
	 *
	 * @param string $key
	 *
	 * @return string
	 * @throws InvalidArgumentException
	 */
	public function get( $key ) {

		if ( $this->has( $key ) ) {
			return $this->changeOp[$key];
		}

		throw new InvalidArgumentException( "{$key} is an unregistered field" );
	}

	/**
	 * @since 2.4
	 *
	 * @return array
	 */
	public function getChangeOp() {
		return $this->changeOp;
	}

}


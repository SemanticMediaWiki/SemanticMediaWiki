<?php

namespace SMW\Constraint;

use SMW\Message;
use SMW\ProcessingError;

/**
 * @license GNU GPL v2+
 * @since 3.1
 *
 * @author mwjames
 */
class ConstraintError implements ProcessingError {

	const ERROR_TYPE = 'constraint';

	/**
	 * @var []
	 */
	private $parameters = [];

	/**
	 * @var string
	 */
	private $type;

	/**
	 * @since 3.1
	 *
	 * @param string|[] $parameters
	 * @param integer|string|null $type
	 */
	public function __construct( $parameters, $type = null ) {
		$this->parameters = $parameters;
		$this->type = $type;
	}

	/**
	 * @since 3.1
	 *
	 * @return string
	 */
	public function getHash() {
		return Message::getHash( $this->parameters, $this->type );
	}

	/**
	 * @since 3.1
	 *
	 * @return string
	 */
	public function getType() {
		return self::ERROR_TYPE;
	}

	/**
	 * @since 3.1
	 *
	 * @return string
	 */
	public function encode() {
		return Message::encode( $this->parameters, $this->type );
	}

	/**
	 * @since 3.1
	 *
	 * @return string
	 */
	public function __toString() {
		return $this->encode();
	}

}

<?php

namespace SMW\Constraint;

use SMW\Localizer\Message;
use SMW\ProcessingError;

/**
 * @license GPL-2.0-or-later
 * @since 3.1
 *
 * @author mwjames
 */
class ConstraintError implements ProcessingError {

	const ERROR_TYPE = 'constraint';

	/**
	 * @since 3.1
	 */
	public function __construct(
		private $parameters,
		private $type = null,
	) {
	}

	/**
	 * @since 3.1
	 *
	 * @return string
	 */
	public function getHash(): string {
		return Message::getHash( $this->parameters, $this->type );
	}

	/**
	 * @since 3.1
	 *
	 * @return string
	 */
	public function getType(): string {
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
	public function __toString(): string {
		return $this->encode();
	}

}

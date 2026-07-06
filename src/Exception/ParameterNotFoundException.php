<?php

namespace SMW\Exception;

use InvalidArgumentException;

/**
 * @license GPL-2.0-or-later
 * @since 3.0
 *
 * @author mwjames
 */
class ParameterNotFoundException extends InvalidArgumentException {

	/**
	 * @since 3.0
	 */
	public function __construct( private $name ) {
		parent::__construct( " {$this->name} is missing as argument!" );
	}

	/**
	 * @since 3.0
	 *
	 * @return string
	 */
	public function getName() {
		return $this->name;
	}
}

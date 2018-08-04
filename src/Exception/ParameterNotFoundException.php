<?php

namespace SMW\Exception;

use InvalidArgumentException;

/**
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class ParameterNotFoundException extends InvalidArgumentException {

	/**
	 * @var string
	 */
	private $name;

	/**
	 * @since 3.0
	 *
	 * @param string $name
	 */
	public function __construct( $name ) {
		$this->name = $name;
		parent::__construct( " $name is missing as argument!" );
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

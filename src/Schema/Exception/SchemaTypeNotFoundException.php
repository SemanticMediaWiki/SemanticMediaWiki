<?php

namespace SMW\Schema\Exception;

use RuntimeException;

/**
 * @license GPL-2.0-or-later
 * @since 3.0
 *
 * @author mwjames
 */
class SchemaTypeNotFoundException extends RuntimeException {

	/**
	 * @var string
	 */
	private $type = '';

	/**
	 * @since 3.0
	 *
	 * @param string $type
	 */
	public function __construct( $type ) {
		parent::__construct( "$type is an unrecognized schema type." );
		$this->type = $type;
	}

	/**
	 * @since 3.0
	 *
	 * @return string
	 */
	public function getType() {
		return $this->type;
	}

}

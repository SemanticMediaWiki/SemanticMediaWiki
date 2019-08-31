<?php

namespace SMW\Schema;

use JsonSerializable;

/**
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class SchemaDefinition extends Compartment implements Schema {

	/**
	 * @var string
	 */
	private $name;

	/**
	 * @var array
	 */
	private $info = [];

	/**
	 * @since 3.0
	 *
	 * @param string $name
	 * @param array $definition
	 * @param array $info
	 */
	public function __construct( $name, array $definition, array $info = [] ) {
		parent::__construct( $definition );
		$this->name = $name;
		$this->info = $info;
	}

	/**
	 * @see Schema::info
	 * @since 3.0
	 *
	 * @return string|null
	 */
	public function info( $key, $default = null ) {

		if ( isset( $this->info[$key] ) ) {
			return $this->info[$key];
		}

		return $default;
	}

	/**
	 * @see Schema::getName
	 * @since 3.0
	 *
	 * @return string
	 */
	public function getName() {
		return str_replace( '_', ' ', $this->name );
	}

	/**
	 * @since 3.1
	 *
	 * @return []
	 */
	 public function toArray() {
		return $this->data;
	}

}

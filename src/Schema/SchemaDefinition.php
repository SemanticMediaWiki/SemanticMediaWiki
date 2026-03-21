<?php

namespace SMW\Schema;

/**
 * @license GPL-2.0-or-later
 * @since 3.0
 *
 * @author mwjames
 */
class SchemaDefinition extends Compartment implements Schema {

	/**
	 * @since 3.0
	 */
	public function __construct(
		private $name,
		array $definition,
		private array $info = [],
	) {
		parent::__construct( $definition );
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
	 * @return array
	 */
	public function toArray(): array {
		return $this->data;
	}

}

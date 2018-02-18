<?php

namespace SMW\Rule;

use JsonSerializable;

/**
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class RuleDefinition implements RuleDef, JsonSerializable {

	/**
	 * @var string
	 */
	private $name;

	/**
	 * @var array
	 */
	protected $definition = [];

	/**
	 * @var string|null
	 */
	private $schema;

	/**
	 * @since 3.0
	 *
	 * @param string $name
	 * @param array $definition
	 * @param string|null $schema
	 */
	public function __construct( $name, array $definition, $schema = null ) {
		$this->name = $name;
		$this->definition = $definition;
		$this->schema = $schema;
	}

	/**
	 * @see RuleDef::get
	 * @since 3.0
	 *
	 * @return mixed|null
	 */
	public function get( $key, $default = null ) {
		return $this->digDeep( $this->definition, $key, $default );
	}

	/**
	 * @see RuleDef::getName
	 * @since 3.0
	 *
	 * @return string
	 */
	public function getName() {
		return str_replace( '_', ' ', $this->name );
	}

	/**
	 * @see RuleDef::getSchema
	 * @since 3.0
	 *
	 * @return string|null
	 */
	public function getSchema() {
		return $this->schema;
	}

	/**
	 * @since 3.0
	 *
	 * @return string
	 */
	 public function jsonSerialize() {
		return json_encode( $this->definition );
	}

	/**
	 * @since 3.0
	 *
	 * @return string
	 */
	 public function __toString() {
		return $this->jsonSerialize();
	}

	private function digDeep( $array, $key, $default ) {

		if ( strpos( $key, '.' ) !== false ) {
			$list = explode( '.', $key, 2 );

			foreach ( $list as $k => $v ) {
				if ( isset( $array[$v] ) ) {
					return $this->digDeep( $array[$v], $list[$k+1], $default );
				}
			}
		}

		if ( isset( $array[$key] ) ) {
			return $array[$key];
		}

		return $default;
	}

}

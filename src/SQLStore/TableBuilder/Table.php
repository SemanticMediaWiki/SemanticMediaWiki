<?php

namespace SMW\SQLStore\TableBuilder;

use RuntimeException;

/**
 * @private
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class Table {

	/**
	 * @var string
	 */
	private $name;

	/**
	 * @var array
	 */
	private $attributes = [];

	/**
	 * @since 2.5
	 *
	 * @param string $name
	 */
	public function __construct( $name ) {
		$this->name = $name;
	}

	/**
	 * @since 2.5
	 *
	 * @param string
	 */
	public function getName() {
		return $this->name;
	}

	/**
	 * @since 2.5
	 *
	 * @param string
	 */
	public function getHash() {
		return json_encode( $this->attributes );
	}

	/**
	 * @since 2.5
	 *
	 * @param array
	 */
	public function getAttributes() {
		return $this->attributes;
	}

	/**
	 * @since 3.0
	 *
	 * @param string $key
	 *
	 * @param mixed
	 */
	public function get( $key ) {

		if ( !isset( $this->attributes[$key] ) ) {
			throw new RuntimeException( "$key is a reserved option key." );
		}

		return $this->attributes[$key];
	}

	/**
	 * @since 2.5
	 *
	 * @param string $fieldName
	 * @param string|array $fieldType
	 */
	public function addColumn( $fieldName, $fieldType ) {
		$this->attributes['fields'][$fieldName] = $fieldType;
	}

	/**
	 * @since 2.5
	 *
	 * @param string|array $index
	 * @param string|null $key
	 */
	public function addIndex( $index, $key = null ) {

		$val = is_array( $index ) ? $index[0] : $index;

		if ( count( explode( ' ', $val ) ) > 1 ) {
			throw new RuntimeException( "Index declaration `$val` contains a space!." );
		}

		if ( $key !== null ) {
			$this->attributes['indices'][$key] = $index;
		} else {
			$this->attributes['indices'][] = $index;
		}
	}

	/**
	 * @since 3.0
	 *
	 * @param string $fieldName
	 * @param string|int $default
	 */
	public function addDefault( $fieldName, $default ) {
		$this->attributes['defaults'][$fieldName] = $default;
	}

	/**
	 * @since 2.5
	 *
	 * @param string $key
	 * @param string|array $option
	 *
	 * @throws RuntimeException
	 */
	public function addOption( $key, $option ) {

		if ( $key === 'fields' || $key === 'indices' || $key === 'defaults' ) {
			throw new RuntimeException( "$key is a reserved option key." );
		}

		$this->attributes[$key] = $option;
	}

}

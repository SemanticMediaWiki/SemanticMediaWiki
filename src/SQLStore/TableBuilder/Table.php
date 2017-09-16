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
	private $options = array();

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
		return json_encode( $this->options );
	}

	/**
	 * @since 2.5
	 *
	 * @param array
	 */
	public function getOptions() {
		return $this->options;
	}

	/**
	 * @since 3.0
	 *
	 * @param string $key
	 *
	 * @param mixed
	 */
	public function getOption( $key ) {

		if ( !isset( $this->options[$key] ) ) {
			throw new RuntimeException( "$key is a reserved option key." );
		}

		return $this->options[$key];
	}

	/**
	 * @since 2.5
	 *
	 * @param string $fieldName
	 * @param string|array $fieldType
	 */
	public function addColumn( $fieldName, $fieldType ) {
		$this->options['fields'][$fieldName] = $fieldType;
	}

	/**
	 * @since 2.5
	 *
	 * @param string|array $index
	 * @param string|null $key
	 */
	public function addIndex( $index, $key = null ) {
		if ( $key !== null ) {
			$this->options['indicies'][$key] = $index;
		} else {
			$this->options['indicies'][] = $index;
		}
	}

	/**
	 * @since 3.0
	 *
	 * @param string $fieldName
	 * @param string|int $default
	 */
	public function addDefault( $fieldName, $default ) {
		$this->options['defaults'][$fieldName] = $default;
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

		if ( $key === 'fields' || $key === 'indicies' || $key === 'defaults' ) {
			throw new RuntimeException( "$key is a reserved option key." );
		}

		$this->options[$key] = $option;
	}

}

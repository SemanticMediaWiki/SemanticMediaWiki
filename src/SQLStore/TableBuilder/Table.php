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
	private $configuration = array();

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
	 * @param string|null $key
	 *
	 * @param array
	 */
	public function getConfiguration( $key = null ) {

		if ( isset( $this->configuration[$key] ) ) {
			return  $this->configuration[$key];
		}

		return $this->configuration;
	}

	/**
	 * @since 2.5
	 *
	 * @param string $column
	 * @param string $type
	 */
	public function addColumn( $column, $type ) {
		$this->configuration['fields'][$column] = $type;
	}

	/**
	 * @since 2.5
	 *
	 * @param string|array $index
	 */
	public function addIndex( $index ) {
		$this->configuration['indicies'][] = $index;
	}

	/**
	 * @since 2.5
	 *
	 * @param string $key
	 * @param string|array $index
	 */
	public function addIndexWithKey( $key, $index ) {
		$this->configuration['indicies'][$key] = $index;
	}

	/**
	 * @since 2.5
	 *
	 * @param string $key
	 * @param string $option
	 *
	 * @throws RuntimeException
	 */
	public function addOption( $key, $option ) {

		if ( $key === 'fields' || $key === 'indicies' ) {
			throw new RuntimeException( "$key is a reserved option key." );
		}

		$this->configuration[$key] = $option;
	}

}

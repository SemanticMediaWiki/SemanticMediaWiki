<?php

namespace SMW\Schema;

use JsonSerializable;

/**
 * @license GNU GPL v2+
 * @since 3.1
 *
 * @author mwjames
 */
class SchemaList implements JsonSerializable {

	// SchemaList -> SchemaSet

	/**
	 * @var array
	 */
	protected $list = [];

	/**
	 * @since 3.1
	 *
	 * @param array $list
	 */
	public function __construct( array $list ) {
		$this->list = $list;
	}

	/**
	 * @since 3.1
	 *
	 * @return []
	 */
	public function getList() {
		return $this->list;
	}

	/**
	 * @since 3.1
	 *
	 * @param Schema|SchemaList $schema
	 */
	public function add( $schema ) {

		if ( $schema instanceof SchemaDefinition ) {
			$this->list[] = $schema;
		}

		if ( $schema instanceof SchemaList ) {
			foreach ( $schema->getList() as $schemaDefinition ) {
				$this->add( $schemaDefinition );
			}
		}
	}

	/**
	 * @since 3.1
	 *
	 * @return []
	 */
	public function merge( SchemaList $schemaList ) {
		$list = [];

		foreach ( $schemaList->getList() as $schemaDefinition ) {
			$data = [];

			if ( $schemaDefinition instanceof SchemaDefinition ) {
				$data = $schemaDefinition->toArray();
			}

			$list = array_merge_recursive( $list, $data );
		}

		return $list;
	}

	/**
	 * @since 3.1
	 *
	 * @param string $key
	 *
	 * @return []
	 */
	public function get( $key ) {

		$list = $this->toArray();

		if ( isset( $list[$key] ) ) {
			return $list[$key];
		}

		return [];
	}

	/**
	 * @since 3.1
	 *
	 * @param string $key
	 *
	 * @return Iterator|[]
	 */
	public function newCompartmentIteratorByKey( $key ) {

		$list = $this->toArray();

		if ( isset( $list[$key] ) ) {
			return new CompartmentIterator( $list[$key] );
		}

		return [];
	}

	/**
	 * @since 3.1
	 *
	 * @return []
	 */
	public function toArray() {
		$list = [];

		foreach ( $this->getList() as $schemaDefinition ) {
			$data = [];

			if ( $schemaDefinition instanceof SchemaDefinition ) {
				$data = $schemaDefinition->toArray();
			}

			$list = array_merge_recursive( $list, $data );
		}

		return $list;
	}

	/**
	 * @since 3.1
	 *
	 * @return string
	 */
	 public function jsonSerialize() {
		return json_encode( $this->list );
	}

}

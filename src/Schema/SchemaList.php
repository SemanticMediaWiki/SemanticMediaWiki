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
	 * @return []
	 */
	public function merge( SchemaList $schemaList ) {
		$list = [];

		foreach ( $schemaList->getList() as $schemaDefinition ) {

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

<?php

namespace SMW\SQLStore\TableBuilder;

use RuntimeException;

/**
 * @private
 *
 * @license GPL-2.0-or-later
 * @since 2.5
 *
 * @author mwjames
 */
class Table {

	const TYPE_FIELDS = 'fields';
	const TYPE_INDICES = 'indices';
	const TYPE_DEFAULTS = 'defaults';

	/**
	 * @var array
	 */
	private $attributes = [];

	/**
	 * @since 2.5
	 */
	public function __construct(
		private $name,
		private readonly bool $isCoreTable = true,
	) {
	}

	/**
	 * @since 2.5
	 *
	 * @return string
	 */
	public function getName() {
		return $this->name;
	}

	/**
	 * @since 3.2
	 *
	 * @return bool
	 */
	public function isCoreTable(): bool {
		return $this->isCoreTable;
	}

	/**
	 * @since 2.5
	 *
	 * @return string
	 */
	public function getHash(): string|false {
		return json_encode( $this->attributes );
	}

	/**
	 * @since 2.5
	 *
	 * @return array
	 */
	public function getAttributes(): array {
		return $this->attributes;
	}

	/**
	 * @since 3.0
	 *
	 * @param string $key
	 *
	 * @return array
	 * @throws RuntimeException
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
	public function addColumn( $fieldName, $fieldType ): void {
		$this->attributes[self::TYPE_FIELDS][$fieldName] = $fieldType;
	}

	/**
	 * @since 3.1
	 *
	 * @param string $key
	 */
	public function setPrimaryKey( $key ): void {
		$this->addIndex( [ $key, "PRIMARY KEY" ], 'pri' );
	}

	/**
	 * @since 2.5
	 *
	 * @param string|array $index
	 * @param string|null $key
	 */
	public function addIndex( $index, $key = null ): void {
		$val = is_array( $index ) ? $index[0] : $index;

		if ( count( explode( ' ', $val ?? '' ) ) > 1 ) {
			throw new RuntimeException( "Index declaration `$val` contains a space!." );
		}

		if ( $key !== null ) {
			$this->attributes[self::TYPE_INDICES][$key] = $index;
		} else {
			$this->attributes[self::TYPE_INDICES][] = $index;
		}
	}

	/**
	 * @since 3.0
	 *
	 * @param string $fieldName
	 * @param string|int $default
	 */
	public function addDefault( $fieldName, $default ): void {
		$this->attributes[self::TYPE_DEFAULTS][$fieldName] = $default;
	}

	/**
	 * @since 2.5
	 *
	 * @param string $key
	 * @param string|array $option
	 *
	 * @throws RuntimeException
	 */
	public function addOption( $key, $option ): void {
		if ( in_array( $key, [ self::TYPE_FIELDS, self::TYPE_INDICES, self::TYPE_DEFAULTS ] ) ) {
			throw new RuntimeException( "$key is a reserved option key." );
		}

		$this->attributes[$key] = $option;
	}

}

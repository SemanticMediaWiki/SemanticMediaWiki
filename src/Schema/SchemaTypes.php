<?php

namespace SMW\Schema;

use SMW\Schema\Exception\SchemaTypeAlreadyExistsException;
use SMW\MediaWiki\HookDispatcherAwareTrait;

/**
 * @license GNU GPL v2+
 * @since 3.2
 *
 * @author mwjames
 */
class SchemaTypes {

	use HookDispatcherAwareTrait;

	/**
	 * @var array
	 */
	private $schemaTypes = [];

	/**
	 * @var string
	 */
	private $dir = '';

	/**
	 * @var bool
	 */
	private $onRegisterSchemaTypes = false;

	/**
	 * @since 3.2
	 *
	 * @param array $schemaTypes
	 * @param string $dir
	 */
	public function __construct( array $schemaTypes = [], string $dir = '' ) {
		$this->schemaTypes = $schemaTypes;
		$this->dir = $dir;
	}

	/**
	 * @since 3.2
	 *
	 * @param string $dir
	 *
	 * @return string
	 */
	public function withDir( string $dir = '' ) : string {
		return "{$this->dir}/$dir";
	}

	/**
	 * @since 3.2
	 *
	 * @param array $schemaTypes
	 */
	public function registerSchemaTypes( array $schemaTypes ) {

		if ( $this->onRegisterSchemaTypes ) {
			return;
		}

		$this->onRegisterSchemaTypes = true;
		$this->schemaTypes = $schemaTypes;

		$this->hookDispatcher->onRegisterSchemaTypes( $this );
	}

	/**
	 * This method is provided for hook handlers to register a new schema type
	 * via the `SMW::Schema::RegisterSchemaTypes` hook.
	 *
	 * @since 3.2
	 *
	 * @param string $type
	 * @param array $params
	 *
	 * @throws SchemaTypeAlreadyExistsException
	 */
	public function registerSchemaType( string $type, array $params ) {

		if ( isset( $this->schemaTypes[$type] ) ) {
			throw new SchemaTypeAlreadyExistsException( $type );
		}

		$this->schemaTypes[$type] = $params;
	}

	/**
	 * @since 3.2
	 *
	 * @param string $type
	 *
	 * @return []
	 */
	public function getType( string $type ) : array {
		return $this->schemaTypes[$type] ?? [];
	}

	/**
	 * @since 3.2
	 *
	 * @param string|null $type
	 *
	 * @return boolean
	 */
	public function isRegisteredType( ?string $type ) : bool {
		return isset( $this->schemaTypes[$type] );
	}

	/**
	 * @since 3.2
	 *
	 * @return []
	 */
	public function getRegisteredTypes() : array {
		return array_keys( $this->schemaTypes );
	}

	/**
	 * @since 3.2
	 *
	 * @param string $group
	 *
	 * @return []
	 */
	public function getRegisteredTypesByGroup( string $group ) : array {

		$registeredTypes = [];
		$groups = (array)$group;

		foreach ( $this->schemaTypes as $type => $val ) {
			if ( isset( $val['group'] ) && in_array( $val['group'], $groups ) ) {
				$registeredTypes[] = $type;
			}
		}

		return $registeredTypes;
	}

}

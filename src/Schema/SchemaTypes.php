<?php

namespace SMW\Schema;

use SMW\Schema\Exception\SchemaTypeAlreadyExistsException;
use SMW\MediaWiki\HookDispatcherAwareTrait;
use JsonSerializable;

/**
 * @license GNU GPL v2+
 * @since 3.2
 *
 * @author mwjames
 */
class SchemaTypes implements JsonSerializable {

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
	 * Default types
	 *
	 * @var []
	 */
	private static $defaultTypes = [
		'LINK_FORMAT_SCHEMA' => [
			'group' => SMW_SCHEMA_GROUP_FORMAT,
			'validation_schema' => 'link-format-schema.v1.json',
			'type_description' => 'smw-schema-description-link-format-schema'
		],
		'SEARCH_FORM_SCHEMA' => [
			'group' => SMW_SCHEMA_GROUP_SEARCH,
			'validation_schema' => 'search-form-schema.v1.json',
			'type_description' => 'smw-schema-description-search-form-schema'
		],
		'PROPERTY_GROUP_SCHEMA' => [
			'group' => SMW_SCHEMA_GROUP_PROPERTY,
			'validation_schema' => 'property-group-schema.v1.json',
			'type_description' => 'smw-schema-description-property-group-schema'
		],
		'PROPERTY_CONSTRAINT_SCHEMA' => [
			'group' => SMW_SCHEMA_GROUP_CONSTRAINT,
			'validation_schema' => 'property-constraint-schema.v1.json',
			'type_description' => 'smw-schema-description-property-constraint-schema',
			'change_propagation' => [ '_CONSTRAINT_SCHEMA' ],
			'usage_lookup' => '_CONSTRAINT_SCHEMA'
		],
		'CLASS_CONSTRAINT_SCHEMA' => [
			'group' => SMW_SCHEMA_GROUP_CONSTRAINT,
			'validation_schema' => 'class-constraint-schema.v1.json',
			'type_description' => 'smw-schema-description-class-constraint-schema',
			'change_propagation' => [ '_CONSTRAINT_SCHEMA' ],
			'usage_lookup' => '_CONSTRAINT_SCHEMA'
		],
		'PROPERTY_PROFILE_SCHEMA' => [
			'group' => SMW_SCHEMA_GROUP_PROFILE,
			'validation_schema' => 'property-profile-schema.v1.json',
			'type_description' => 'smw-schema-description-property-profile-schema',
			'change_propagation' => '_PROFILE_SCHEMA',
			'usage_lookup' => '_PROFILE_SCHEMA'
		]
	];

	/**
	 * @since 3.2
	 *
	 * @param string $dir
	 */
	public function __construct( string $dir = '' ) {
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
		return str_replace( [ '\\', '//', '/', '\\\\' ], DIRECTORY_SEPARATOR, "{$this->dir}/$dir" );
	}

	/**
	 * @since 3.2
	 *
	 * @param array $schemaTypes
	 */
	public function registerSchemaTypes( array $schemaTypes = [] ) {

		if ( $this->onRegisterSchemaTypes ) {
			return;
		}

		$this->onRegisterSchemaTypes = true;

		foreach ( self::$defaultTypes + $schemaTypes as $key => $value ) {

			if ( isset( $value['validation_schema'] ) ) {
				$value['validation_schema'] = $this->withDir( $value['validation_schema'] );
			}

			$this->registerSchemaType( $key, $value );
		}

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

	/**
	 * @see JsonSerializable::jsonSerialize
	 * @since 3.2
	 *
	 * @return string
	 */
	public function jsonSerialize() {
		return json_encode( $this->schemaTypes );
	}

}

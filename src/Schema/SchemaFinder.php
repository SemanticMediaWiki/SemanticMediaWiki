<?php

namespace SMW\Schema;

use SMW\DIProperty;
use SMW\RequestOptions;
use SMW\Store;
use SMWDIBlob as DIBlob;
use SMWDataItem as DataItem;
use Title;
use SMW\DIWikiPage;
use SMW\PropertySpecificationLookup;

/**
 * @private
 *
 * @license GNU GPL v2+
 * @since 3.1
 *
 * @author mwjames
 */
class SchemaFinder {

	/**
	 * @var Store
	 */
	private $store;

	/**
	 * @var PropertySpecificationLookup
	 */
	private $propertySpecificationLookup;

	/**
	 * @since 3.1
	 *
	 * @param Store $store
	 * @param PropertySpecificationLookup $propertySpecificationLookup
	 */
	public function __construct( Store $store, PropertySpecificationLookup $propertySpecificationLookup ) {
		$this->store = $store;
		$this->propertySpecificationLookup = $propertySpecificationLookup;
	}

	/**
	 * @since 3.1
	 *
	 * @param DataItem $dataItem
	 *
	 * @return SchemaList|[]
	 */
	public function getConstraintSchema( DataItem $dataItem ) {
		return $this->newSchemaList( $dataItem, new DIProperty( '_CONSTRAINT_SCHEMA' ) );
	}

	/**
	 * @since 3.1
	 *
	 * @param DataItem $dataItem
	 * @param DIProperty $property
	 *
	 * @return SchemaList|[]
	 */
	public function newSchemaList( DataItem $dataItem, DIProperty $property ) {

		$dataItems = $this->propertySpecificationLookup->getSpecification(
			$dataItem,
			$property
		);

		if ( $dataItems === null || $dataItems === false ) {
			return [];
		}

		$schemaList = [];

		foreach ( $dataItems as $subject ) {
			$this->findSchemaDefinition( $subject, $schemaList );
		}

		return new SchemaList( $schemaList );
	}

	/**
	 * @since 3.1
	 *
	 * @param string $type
	 *
	 * @return SchemaList
	 */
	public function getSchemaListByType( $type ) {

		$data = [];
		$schemaList = [];

		$subjects = $this->store->getPropertySubjects(
			new DIProperty( '_SCHEMA_TYPE' ),
			new DIBlob( $type )
		);

		foreach ( $subjects as $subject ) {
			$this->findSchemaDefinition( $subject, $schemaList );
		}

		return new SchemaList( $schemaList );
	}

	private function findSchemaDefinition( $subject, &$schemaList ) {

		if ( !$subject instanceof DIWikiPage ) {
			return;
		}

		$definitions = $this->propertySpecificationLookup->getSpecification(
			$subject,
			new DIProperty( '_SCHEMA_DEF' )
		);

		$name = str_replace( '_', ' ', $subject->getDBKey() );

		foreach ( $definitions as $definition ) {
			$schemaList[] = new SchemaDefinition(
				$name,
				json_decode( $definition->getString(), true )
			);
		}
	}

}

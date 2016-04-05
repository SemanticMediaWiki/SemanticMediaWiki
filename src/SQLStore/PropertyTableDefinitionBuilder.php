<?php

namespace SMW\SQLStore;

use Hooks;
use SMW\DataTypeRegistry;
use SMW\DIProperty;

/**
 * Class that generates property table definitions
 *
 * @license GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class PropertyTableDefinitionBuilder {

	/**
	 * @var TableDefinition[]
	 */
	protected $propertyTables = array();

	/**
	 * @var array
	 */
	protected $fixedPropertyTableIds = array();

	/**
	 * @var string
	 */
	private $fixedPropertyTablePrefix = 'smw_fpt';

	/**
	 * @since 1.9
	 *
	 * @param array $diType
	 * @param array $specialProperties
	 * @param array $userDefinedFixedProperties
	 */
	public function __construct( array $diTypes, array $specialProperties, array $userDefinedFixedProperties ) {
		$this->diTypes = $diTypes;
		$this->specialProperties = $specialProperties;
		$this->userDefinedFixedProperties = $userDefinedFixedProperties;
	}

	/**
	 * @since 1.9
	 */
	public function doBuild() {

		$this->addTableDefinitionForDiTypes( $this->diTypes );

		$this->addTableDefinitionForFixedProperties(
			$this->specialProperties
		);

		$customFixedProperties = array();

		Hooks::run( 'SMW::SQLStore::AddCustomFixedPropertyTables', array( &$customFixedProperties ) );

		$this->addTableDefinitionForFixedProperties(
			$customFixedProperties
		);

		$this->addRedirectTableDefinition();

		$this->addTableDefinitionForUserDefinedFixedProperties(
			$this->userDefinedFixedProperties
		);

		Hooks::run( 'SMW::SQLStore::updatePropertyTableDefinitions', array( &$this->propertyTables ) );

		$this->createFixedPropertyTableIdIndex();
	}

	/**
	 * Returns table prefix
	 *
	 * @since 1.9
	 *
	 * @return string
	 */
	public function getTablePrefix() {
		return $this->fixedPropertyTablePrefix;
	}

	/**
	 * Returns fixed properties table Ids
	 *
	 * @since 1.9
	 *
	 * @return array|null
	 */
	public function getFixedPropertyTableIds() {
		return $this->fixedPropertyTableIds;
	}

	/**
	 * Returns property table definitions
	 *
	 * @since 1.9
	 *
	 * @return TableDefinition[]
	 */
	public function getTableDefinitions() {
		return $this->propertyTables;
	}

	/**
	 * Returns new table definition
	 *
	 * @since 1.9
	 *
	 * @param $diType
	 * @param $tableName
	 * @param $fixedProperty
	 *
	 * @return TableDefinition
	 */
	public function newTableDefinition( $diType, $tableName, $fixedProperty = false ) {
		return new TableDefinition( $diType, $tableName, $fixedProperty );
	}

	/**
	 * Add property table definition
	 *
	 * @since 1.9
	 *
	 * @param $diType
	 * @param $tableName
	 * @param $fixedProperty
	 */
	protected function addPropertyTable( $diType, $tableName, $fixedProperty = false ) {
		$this->propertyTables[$tableName] = $this->newTableDefinition( $diType, $tableName, $fixedProperty );
	}

	/**
	 * @param array $diTypes
	 */
	private function addTableDefinitionForDiTypes( array $diTypes ) {
		foreach( $diTypes as $tableDIType => $tableName ) {
			$this->addPropertyTable( $tableDIType, $tableName );
		}
	}

	/**
	 * @param array $properties
	 */
	private function addTableDefinitionForFixedProperties( array $properties ) {
		foreach( $properties as $propertyKey => $propetyTableSuffix ) {

			// Either as plain index array containing the property key or as associated
			// array with property key => tableSuffix
			$propertyKey = is_int( $propertyKey ) ? $propetyTableSuffix : $propertyKey;

			$this->addPropertyTable(
				DataTypeRegistry::getInstance()->getDataItemId( DIProperty::getPredefinedPropertyTypeId( $propertyKey ) ),
				$this->fixedPropertyTablePrefix . strtolower( $propetyTableSuffix ),
				$propertyKey
			);
		}
	}

	private function addRedirectTableDefinition() {
		// Redirect table uses another subject scheme for historic reasons
		// TODO This should be changed if possible
		$redirectTableName = $this->fixedPropertyTablePrefix . '_redi';
		if ( isset( $this->propertyTables[$redirectTableName]) ) {
			$this->propertyTables[$redirectTableName]->setUsesIdSubject( false );
		}
	}

	/**
	 * Get all the tables for the properties that are declared as fixed
	 * (overly used and thus having separate tables)
	 *
	 * @param array $fixedProperties
	 */
	private function addTableDefinitionForUserDefinedFixedProperties( array $fixedProperties ) {
		foreach( $fixedProperties as $propertyKey => $tableDIType ) {

			// Normalize the key to be independent from a possible MW setting
			// (has area == Has_area <> Has_Area)
			$propertyKey = str_replace( ' ', '_', ucfirst( $propertyKey ) );

			$this->addPropertyTable(
				$tableDIType,
				$this->fixedPropertyTablePrefix . '_' . md5( $propertyKey ),
				$propertyKey
			);
		}
	}

	private function createFixedPropertyTableIdIndex() {

		foreach ( $this->propertyTables as $tid => $propTable ) {
			if ( $propTable->isFixedPropertyTable() ) {
				$this->fixedPropertyTableIds[$propTable->getFixedProperty()] = $tid;
			}
		}

		// Specifically set properties that must not be stored in any
		// property table to null here. Any function that hits this
		// null unprepared is doing something wrong anyway.
		$this->fixedPropertyTableIds['_SKEY'] = null;
	}

}

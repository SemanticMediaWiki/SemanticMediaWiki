<?php

namespace SMW\SQLStore;

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
	 * @since 1.9
	 *
	 * @param array $diType
	 * @param array $specialProperties
	 * @param array $fixedProperties
	 */
	public function __construct( array $diTypes, array $specialProperties, array $fixedProperties ) {
		$this->diTypes = $diTypes;
		$this->specialProperties = $specialProperties;
		$this->fixedProperties = $fixedProperties;
	}

	/**
	 * @since 1.9
	 */
	public function doBuild() {

		$this->buildPropertyTablesForDiTypes( $this->diTypes );
		$this->buildPropertyTablesForSpecialProperties( $this->specialProperties );
		$this->buildPropertyTablesForFixedProperties( $this->fixedProperties );

		wfRunHooks( 'SMW::SQLStore::updatePropertyTableDefinitions', array( &$this->propertyTables ) );

		$this->buildFixedPropertyTableIdIndex( $this->propertyTables );
	}

	/**
	 * Returns table prefix
	 *
	 * @since 1.9
	 *
	 * @return string
	 */
	public function getTablePrefix() {
		return 'smw_fpt';
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
	private function buildPropertyTablesForDiTypes( array $diTypes ) {
		foreach( $diTypes as $tableDIType => $tableName ) {
			$this->addPropertyTable( $tableDIType, $tableName );
		}
	}

	/**
	 * @param array $specialProperties
	 */
	private function buildPropertyTablesForSpecialProperties( array $specialProperties ) {
		foreach( $specialProperties as $propertyKey ) {
			$this->addPropertyTable(
				DataTypeRegistry::getInstance()->getDataItemId( DIProperty::getPredefinedPropertyTypeId( $propertyKey ) ),
				$this->getTablePrefix() . strtolower( $propertyKey ),
				$propertyKey
			);
		}

		// Redirect table uses another subject scheme for historic reasons
		// TODO This should be changed if possible
		$redirectTableName = $this->getTablePrefix() . '_redi';
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
	private function buildPropertyTablesForFixedProperties( array $fixedProperties ) {
		foreach( $fixedProperties as $propertyKey => $tableDIType ) {
			$this->addPropertyTable(
				$tableDIType,
				$this->getTablePrefix() . '_' . md5( $propertyKey ),
				$propertyKey
			);
		}
	}

	/**
	 * @param array $propertyTables
	 */
	private function buildFixedPropertyTableIdIndex( array $propertyTables ) {

		foreach ( $propertyTables as $tid => $propTable ) {
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

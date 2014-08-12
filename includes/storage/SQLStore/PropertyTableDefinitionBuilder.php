<?php

namespace SMW\SQLStore;

use SMW\DataTypeRegistry;
use SMWDIProperty;

/**
 * Class that generates property table definitions
 *
 * @ingroup SQLStore
 *
 * @licence GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class PropertyTableDefinitionBuilder {

	/** @var TableDefinition[] */
	protected $propertyTables = array();

	/** @var array */
	protected $fixedPropertyTableIds = array();

	/**
	 * @since 1.9
	 *
	 * @param array $diType
	 * @param array $specialProperties
	 * @param array $fixedProperties
	 */
	public function __construct(
		array $diTypes,
		array $specialProperties,
		array $fixedProperties
	) {
		$this->diTypes = $diTypes;
		$this->specialProperties = $specialProperties;
		$this->fixedProperties = $fixedProperties;
	}

	/**
	 * Build definitions
	 *
	 * @since 1.9
	 */
	public function runBuilder() {

		$this->getDITypes( $this->diTypes );
		$this->getSpecialProperties( $this->specialProperties );
		$this->getFixedProperties( $this->fixedProperties );

		wfRunHooks( 'SMW::SQLStore::updatePropertyTableDefinitions', array( &$this->propertyTables ) );

		$this->getFixedPropertyTableIds( $this->propertyTables );

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
	public function getTableIds() {
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
	public function getDefinition( $diType, $tableName, $fixedProperty = false ) {
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
		$this->propertyTables[$tableName] = $this->getDefinition( $diType, $tableName, $fixedProperty );
	}

	/**
	 * Add DI type table definitions
	 *
	 * @since 1.9
	 *
	 * @param array $diTypes
	 */
	protected function getDITypes( array $diTypes ) {
		foreach( $diTypes as $tableDIType => $tableName ) {
			$this->addPropertyTable( $tableDIType, $tableName );
		}
	}

	/**
	 * Add special properties table definitions
	 *
	 * @since 1.9
	 *
	 * @param array $specialProperties
	 */
	protected function getSpecialProperties( array $specialProperties ) {
		foreach( $specialProperties as $propertyKey ) {
			$this->addPropertyTable(
				DataTypeRegistry::getInstance()->getDataItemId( SMWDIProperty::getPredefinedPropertyTypeId( $propertyKey ) ),
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
	 * Add fixed property table definitions
	 *
	 * Get all the tables for the properties that are declared as fixed
	 * (overly used and thus having separate tables)
	 *
	 * @see $smwgFixedProperties
	 *
	 * @since 1.9
	 *
	 * @param array $fixedProperties
	 */
	protected function getFixedProperties( array $fixedProperties ) {
		foreach( $fixedProperties as $propertyKey => $tableDIType ) {
			$this->addPropertyTable(
				$tableDIType,
				$this->getTablePrefix() . '_' . md5( $propertyKey ),
				$propertyKey
			);
		}
	}

	/**
	 * Build index for fixed property tables Ids
	 *
	 * @since 1.9
	 *
	 * @param array $propertyTables
	 */
	protected function getFixedPropertyTableIds( array $propertyTables ) {

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

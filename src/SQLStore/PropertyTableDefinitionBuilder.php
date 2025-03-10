<?php

namespace SMW\SQLStore;

use MediaWiki\MediaWikiServices;
use SMW\DataTypeRegistry;
use SMW\DIProperty;
use SMW\PropertyRegistry;

/**
 * @private
 *
 * @license GPL-2.0-or-later
 * @since 1.9
 *
 * @author mwjames
 */
class PropertyTableDefinitionBuilder {

	/**
	 * Fixed property table prefix
	 */
	const PROPERTY_TABLE_PREFIX = 'smw_fpt';

	/**
	 * @var PropertyTypeFinder
	 */
	private $propertyTypeFinder;

	/**
	 * @var PropertyTableDefinition[]
	 */
	protected $propertyTables = [];

	/**
	 * @var array
	 */
	protected $fixedPropertyTableIds = [];

	/**
	 * @since 1.9
	 *
	 * @param PropertyTypeFinder $propertyTypeFinder
	 */
	public function __construct( PropertyTypeFinder $propertyTypeFinder ) {
		$this->propertyTypeFinder = $propertyTypeFinder;
	}

	/**
	 * @since 1.9
	 *
	 * @param array $diTypes
	 * @param array $specialProperties
	 * @param array $userDefinedFixedProperties
	 */
	public function doBuild( $diTypes, $specialProperties, $userDefinedFixedProperties ) {
		$this->addTableDefinitionForDiTypes( $diTypes );

		$this->addTableDefinitionForFixedProperties(
			$specialProperties,
			[],
			PropertyTableDefinition::TYPE_CORE
		);

		$customFixedProperties = [];
		$fixedPropertyTablePrefix = [];

		$hookContainer = MediaWikiServices::getInstance()->getHookContainer();
		// Allow to alter the prefix by an extension
		$hookContainer->run( 'SMW::SQLStore::AddCustomFixedPropertyTables', [ &$customFixedProperties, &$fixedPropertyTablePrefix ] );

		$this->addTableDefinitionForFixedProperties(
			$customFixedProperties,
			$fixedPropertyTablePrefix,
			PropertyTableDefinition::TYPE_CUSTOM
		);

		$this->addRedirectTableDefinition();

		$this->addTableDefinitionForUserDefinedFixedProperties(
			$userDefinedFixedProperties
		);

		$hookContainer->run( 'SMW::SQLStore::updatePropertyTableDefinitions', [ &$this->propertyTables ] );

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
		return self::PROPERTY_TABLE_PREFIX;
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
	 * @return PropertyTableDefinition[]
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
	 * @return PropertyTableDefinition
	 */
	public function newTableDefinition( $diType, $tableName, $fixedProperty = false ) {
		return new PropertyTableDefinition( $diType, $tableName, $fixedProperty );
	}

	/**
	 * @since 2.5
	 *
	 * @param string $tableName
	 *
	 * @return string
	 */
	public static function makeTableName( $tableName ) {
		return self::PROPERTY_TABLE_PREFIX . strtolower( $tableName );
	}

	/**
	 * @see http://stackoverflow.com/questions/3763728/shorter-php-cipher-than-md5
	 * @since 2.5
	 *
	 * @param string $tableName
	 *
	 * @return string
	 */
	public function createHashedTableNameFrom( $tableName ) {
		return self::PROPERTY_TABLE_PREFIX . '_' . substr( base_convert( md5( $tableName ), 16, 32 ), 0, 12 );
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
	protected function addPropertyTable( $diType, $tableName, $fixedProperty = false, $tableType = '' ) {
		$this->propertyTables[$tableName] = $this->newTableDefinition( $diType, $tableName, $fixedProperty );
		$this->propertyTables[$tableName]->setTableType( $tableType );
	}

	/**
	 * @param array $diTypes
	 */
	private function addTableDefinitionForDiTypes( array $diTypes ) {
		foreach ( $diTypes as $tableDIType => $tableName ) {
			$this->addPropertyTable( $tableDIType, $tableName, false, PropertyTableDefinition::TYPE_CORE );
		}
	}

	private function addTableDefinitionForFixedProperties( array $properties, array $fixedPropertyTablePrefix = [], $tableType = false ) {
		foreach ( $properties as $propertyKey => $propertyTableSuffix ) {

			$tablePrefix = isset( $fixedPropertyTablePrefix[$propertyKey] ) ? $fixedPropertyTablePrefix[$propertyKey] : self::PROPERTY_TABLE_PREFIX;

			// Either as plain index array containing the property key or as associated
			// array with property key => tableSuffix
			$propertyKey = is_int( $propertyKey ) ? $propertyTableSuffix : $propertyKey;

			$diType = DataTypeRegistry::getInstance()->getDataItemByType(
				PropertyRegistry::getInstance()->getPropertyValueTypeById( $propertyKey )
			);

			$tableName = $tablePrefix . strtolower( $propertyTableSuffix );

			$this->addPropertyTable( $diType, $tableName, $propertyKey, $tableType );
		}
	}

	private function addRedirectTableDefinition() {
		// Redirect table uses another subject scheme for historic reasons
		// TODO This should be changed if possible
		$redirectTableName = $this->makeTableName( '_REDI' );

		if ( isset( $this->propertyTables[$redirectTableName] ) ) {
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
		$this->propertyTypeFinder->setTypeTableName(
			$this->makeTableName( '_TYPE' )
		);

		foreach ( $fixedProperties as $propertyKey ) {

			// Normalize the key to be independent from a possible MW setting
			// (has area == Has_area <> Has_Area)
			$propertyKey = str_replace( ' ', '_', ucfirst( $propertyKey ) );
			$property = new DIProperty( $propertyKey );

			$diType = DataTypeRegistry::getInstance()->getDataItemByType(
				$this->propertyTypeFinder->findTypeID( $property )
			);

			$tableName = $this->createHashedTableNameFrom( $propertyKey );

			$this->addPropertyTable( $diType, $tableName, $propertyKey, PropertyTableDefinition::TYPE_CORE );
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

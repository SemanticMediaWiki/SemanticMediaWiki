<?php

namespace SMW\SQLStore;

use SMW\DataTypeRegistry;
use SMW\TypesRegistry;
use SMW\DIProperty;
use SMWDataItem as DataItem;

/**
 * @license GNU GPL v2+
 * @since 2.2
 *
 * @author mwjames
 */
class PropertyTableInfoFetcher {

	/**
	 * @var PropertyTypeFinder
	 */
	private $propertyTypeFinder;

	/**
	 * Array for keeping property table table data, indexed by table id.
	 * Access this only by calling getPropertyTables().
	 *
	 * @var TableDefinition[]|null
	 */
	private $propertyTableDefinitions = null;

	/**
	 * Array to cache "propkey => table id" associations for fixed property
	 * tables. Initialized by getPropertyTables(), which must be called
	 * before accessing this.
	 *
	 * @var array|null
	 */
	private $fixedPropertyTableIds = null;

	/**
	 * @var array
	 */
	private $customSpecialPropertyList = [];

	/**
	 * @var array
	 */
	private $customFixedPropertyList = [];

	/**
	 * Default tables to use for storing data of certain types.
	 *
	 * @var array
	 */
	private static $defaultDiTypeTableIdMap = [
		DataItem::TYPE_NUMBER     => 'smw_di_number',
		DataItem::TYPE_BLOB       => 'smw_di_blob',
		DataItem::TYPE_BOOLEAN    => 'smw_di_bool',
		DataItem::TYPE_URI        => 'smw_di_uri',
		DataItem::TYPE_TIME       => 'smw_di_time',
		DataItem::TYPE_GEO        => 'smw_di_coords', // currently created only if Semantic Maps are installed
		DataItem::TYPE_WIKIPAGE   => 'smw_di_wikipage',
		//DataItem::TYPE_CONCEPT    => '', // _CONC is the only property of this type
	];

	/**
	 * @since 2.5
	 *
	 * @param PropertyTypeFinder $propertyTypeFinder
	 */
	public function __construct( PropertyTypeFinder $propertyTypeFinder ) {
		$this->propertyTypeFinder = $propertyTypeFinder;
	}

	/**
	 * @since 2.2
	 *
	 * @param array $customFixedProperties
	 */
	public function setCustomFixedPropertyList( array $customFixedProperties ) {
		$this->customFixedPropertyList = $customFixedProperties;
	}

	/**
	 * @since 2.2
	 *
	 * @param array $customSpecialProperties
	 */
	public function setCustomSpecialPropertyList( array $customSpecialProperties ) {
		$this->customSpecialPropertyList = $customSpecialProperties;
	}

	/**
	 * Find the id of a property table that is suitable for storing values of
	 * the given type. The type is specified by an SMW type id such as '_wpg'.
	 * An empty string is returned if no matching table could be found.
	 *
	 * @since 2.2
	 *
	 * @param string $dataTypeTypeId
	 *
	 * @return string
	 */
	public function findTableIdForDataTypeTypeId( $dataTypeTypeId ) {
		return $this->findTableIdForDataItemTypeId(
			DataTypeRegistry::getInstance()->getDataItemId( $dataTypeTypeId )
		);
	}

	/**
	 * Find the id of a property table that is normally used to store
	 * data items of the given type. The empty string is returned if
	 * no such table exists.
	 *
	 * @since 2.2
	 *
	 * @param integer $dataItemId
	 *
	 * @return string
	 */
	public static function findTableIdForDataItemTypeId( $dataItemId ) {

		if ( array_key_exists( $dataItemId, self::$defaultDiTypeTableIdMap ) ) {
			return self::$defaultDiTypeTableIdMap[$dataItemId];
		}

		return '';
	}

	/**
	 * @since 3.0
	 *
	 * @return array
	 */
	public function getDefaultDataItemTables() {
		return array_values( self::$defaultDiTypeTableIdMap );
	}

	/**
	 * @since 2.5
	 *
	 * @param DIProperty $property
	 *
	 * @return boolean
	 */
	public function isFixedTableProperty( DIProperty $property ) {

		if ( $this->fixedPropertyTableIds === null ) {
			$this->buildDefinitionsForPropertyTables();
		}

		return array_key_exists( $property->getKey(), $this->fixedPropertyTableIds );
	}

	/**
	 * Retrieve the id of the property table that is to be used for storing
	 * values for the given property object.
	 *
	 * @since 2.2
	 *
	 * @param DIProperty $property
	 *
	 * @return string
	 */
	public function findTableIdForProperty( DIProperty $property ) {

		if ( $this->fixedPropertyTableIds === null ) {
			$this->buildDefinitionsForPropertyTables();
		}

		$propertyKey = $property->getKey();

		if ( array_key_exists( $propertyKey, $this->fixedPropertyTableIds ) ) {
			return $this->fixedPropertyTableIds[$propertyKey];
		}

		return $this->findTableIdForDataTypeTypeId( $property->findPropertyTypeID() );
	}

	/**
	 * Return the array of predefined property table declarations, initialising
	 * it if necessary. The result is an array of SMWSQLStore3Table objects
	 * indexed by table ids.
	 *
	 * It is ensured that the keys of the returned array agree with the name of
	 * the table that they refer to.
	 *
	 * @since 2.2
	 *
	 * @return TableDefinition[]
	 */
	public function getPropertyTableDefinitions() {

		if ( $this->propertyTableDefinitions === null ) {
			$this->buildDefinitionsForPropertyTables();
		}

		return $this->propertyTableDefinitions;
	}

	/**
	 * @since 2.2
	 */
	public function clearCache() {
		$this->propertyTableDefinitions = null;
		$this->fixedPropertyTableIds = null;
	}

	private function buildDefinitionsForPropertyTables() {

		$enabledSpecialProperties = TypesRegistry::getFixedProperties( 'default_fixed' );

		$customFixedSpecialPropertyList = array_flip(
			TypesRegistry::getFixedProperties( 'custom_fixed' )
		);

		foreach ( $this->customSpecialPropertyList as $property ) {
			if ( isset( $customFixedSpecialPropertyList[$property] ) ) {
				$enabledSpecialProperties[] = $property;
			}
		}

		$definitionBuilder = new PropertyTableDefinitionBuilder(
			$this->propertyTypeFinder
		);

		$definitionBuilder->doBuild(
			self::$defaultDiTypeTableIdMap,
			$enabledSpecialProperties,
			$this->customFixedPropertyList
		);

		$this->propertyTableDefinitions = $definitionBuilder->getTableDefinitions();
		$this->fixedPropertyTableIds = $definitionBuilder->getFixedPropertyTableIds();
	}

}

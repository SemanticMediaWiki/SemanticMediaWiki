<?php

namespace SMW\SQLStore;

use SMW\DataTypeRegistry;
use SMW\ApplicationFactory;
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
	 * Array for keeping property table table data, indexed by table id.
	 * Access this only by calling getPropertyTables().
	 *
	 * @var TableDefinition[]
	 */
	private static $propertyTableDefinitions = null;

	/**
	 * Array to cache "propkey => table id" associations for fixed property
	 * tables. Initialized by getPropertyTables(), which must be called
	 * before accessing this.
	 *
	 * @var array|null
	 */
	private static $fixedPropertyTableIds = null;

	/**
	 * Keys of special properties that should have their own
	 * fixed property table.
	 *
	 * @var array
	 */
	private static $customizableSpecialProperties = array(
		'_MDAT', '_CDAT', '_NEWP', '_LEDT', '_MIME', '_MEDIA',
	);

	/**
	 * @var array
	 */
	private static $fixedSpecialProperties = array(
		// property declarations
		'_TYPE', '_UNIT', '_CONV', '_PVAL', '_LIST', '_SERV',
		// query statistics (very frequently used)
		'_ASK', '_ASKDE', '_ASKSI', '_ASKFO', '_ASKST', '_ASKDU',
		// subproperties, classes, and instances
		'_SUBP', '_SUBC', '_INST',
		// redirects
		'_REDI',
		// has sub object
		'_SOBJ',
		// vocabulary import and URI assignments
		'_IMPO', '_URI',
		// Concepts
		'_CONC'
	);

	/**
	 * Default tables to use for storing data of certain types.
	 *
	 * @var array
	 */
	private static $defaultDiTypeTableIdMap = array(
		DataItem::TYPE_NUMBER     => 'smw_di_number',
		DataItem::TYPE_BLOB       => 'smw_di_blob',
		DataItem::TYPE_BOOLEAN    => 'smw_di_bool',
		DataItem::TYPE_URI        => 'smw_di_uri',
		DataItem::TYPE_TIME       => 'smw_di_time',
		DataItem::TYPE_GEO        => 'smw_di_coords', // currently created only if Semantic Maps are installed
		DataItem::TYPE_WIKIPAGE   => 'smw_di_wikipage',
		//DataItem::TYPE_CONCEPT    => '', // _CONC is the only property of this type
	);

	/**
	 * Find the id of a property table that is suitable for storing values of
	 * the given type. The type is specified by an SMW type id such as '_wpg'.
	 * An empty string is returned if no matching table could be found.
	 *
	 * @since 1.8
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
	 * @since 1.8
	 *
	 * @param integer $dataItemId
	 *
	 * @return string
	 */
	public function findTableIdForDataItemTypeId( $dataItemId ) {

		if ( array_key_exists( $dataItemId, self::$defaultDiTypeTableIdMap ) ) {
			return self::$defaultDiTypeTableIdMap[$dataItemId];
		}

		return '';
	}

	/**
	 * Retrieve the id of the property table that is to be used for storing
	 * values for the given property object.
	 *
	 * @since 1.8
	 *
	 * @param DIProperty $property
	 *
	 * @return string
	 */
	public function findTableIdForProperty( DIProperty $property ) {

		if ( self::$fixedPropertyTableIds === null ) {
			$this->getPropertyTableDefinitions();
		}

		$propertyKey = $property->getKey();

		if ( array_key_exists( $propertyKey, self::$fixedPropertyTableIds ) ) {
			return self::$fixedPropertyTableIds[$propertyKey];
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

		if ( self::$propertyTableDefinitions !== null ) {
			return self::$propertyTableDefinitions;
		}

		$settings = ApplicationFactory::getInstance()->getSettings();

		$enabledSpecialProperties = self::$fixedSpecialProperties;
		$customizableSpecialProperties = array_flip( self::$customizableSpecialProperties );

		$customFixedProperties = $settings->get( 'smwgFixedProperties' );
		$customSpecialProperties = $settings->get( 'smwgPageSpecialProperties' );

		foreach ( $customSpecialProperties as $property ) {
			if ( isset( $customizableSpecialProperties[$property] ) ) {
				$enabledSpecialProperties[] = $property;
			}
		}

		$definitionBuilder = new PropertyTableDefinitionBuilder(
			self::$defaultDiTypeTableIdMap,
			$enabledSpecialProperties,
			$customFixedProperties
		);

		$definitionBuilder->doBuild();

		self::$propertyTableDefinitions = $definitionBuilder->getTableDefinitions();
		self::$fixedPropertyTableIds = $definitionBuilder->getFixedPropertyTableIds();

		return self::$propertyTableDefinitions;
	}

	/**
	 * @since 2.2
	 */
	public function clear() {
		self::$propertyTableDefinitions = null;
		self::$fixedPropertyTableIds = null;
	}

}

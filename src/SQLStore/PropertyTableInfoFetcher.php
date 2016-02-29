<?php

namespace SMW\SQLStore;

use SMW\DataTypeRegistry;
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
	 * Keys of special properties that should have their own
	 * fixed property table.
	 *
	 * @var array
	 */
	private $customizableSpecialProperties = array(
		'_MDAT', '_CDAT', '_NEWP', '_LEDT', '_MIME', '_MEDIA',
	);

	/**
	 * @var array
	 */
	private $customSpecialPropertyList = array();

	/**
	 * @var array
	 */
	private $fixedSpecialProperties = array(
		// property declarations
		'_TYPE', '_UNIT', '_CONV', '_PVAL', '_LIST', '_SERV', '_PREC',
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
		'_CONC',
		// Monolingual text
		'_LCODE', '_TEXT',
		// Display title of
		'_DTITLE'
	);

	/**
	 * @var array
	 */
	private $customFixedPropertyList = array();

	/**
	 * Default tables to use for storing data of certain types.
	 *
	 * @var array
	 */
	private $defaultDiTypeTableIdMap = array(
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
	public function findTableIdForDataItemTypeId( $dataItemId ) {

		if ( array_key_exists( $dataItemId, $this->defaultDiTypeTableIdMap ) ) {
			return $this->defaultDiTypeTableIdMap[$dataItemId];
		}

		return '';
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

		$enabledSpecialProperties = $this->fixedSpecialProperties;
		$customizableSpecialProperties = array_flip( $this->customizableSpecialProperties );

		foreach ( $this->customSpecialPropertyList as $property ) {
			if ( isset( $customizableSpecialProperties[$property] ) ) {
				$enabledSpecialProperties[] = $property;
			}
		}

		$definitionBuilder = new PropertyTableDefinitionBuilder(
			$this->defaultDiTypeTableIdMap,
			$enabledSpecialProperties,
			$this->customFixedPropertyList
		);

		$definitionBuilder->doBuild();

		$this->propertyTableDefinitions = $definitionBuilder->getTableDefinitions();
		$this->fixedPropertyTableIds = $definitionBuilder->getFixedPropertyTableIds();
	}

}

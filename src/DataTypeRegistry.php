<?php

namespace SMW;

use SMWDataItem as DataItem;

/**
 * DataTypes registry class
 *
 * Registry class that manages datatypes, and provides various methods to access
 * the information
 *
 * @license GNU GPL v2+
 * @since 1.9
 *
 * @author Markus Krötzsch
 * @author Jeroen De Dauw
 * @author mwjames
 */
class DataTypeRegistry {

	/**
	 * @var DataTypeRegistry
	 */
	protected static $instance = null;

	/**
	 * Array of type labels indexed by type ids. Used for datatype resolution.
	 *
	 * @var string[]
	 */
	private $typeLabels = array();

	/**
	 * Array of ids indexed by type aliases. Used for datatype resolution.
	 *
	 * @var string[]
	 */
	private $typeAliases = array();

	/**
	 * @var string[]
	 */
	private $canonicalLabels = array();

	/**
	 * Array of class names for creating new SMWDataValue, indexed by type
	 * id.
	 *
	 * @var string[]
	 */
	private $typeClasses;

	/**
	 * Array of data item classes, indexed by type id.
	 *
	 * @var integer[]
	 */
	private $typeDataItemIds;

	/**
	 * Lookup map that allows finding a datatype id given a label or alias.
	 * All labels and aliases (ie array keys) are stored lower case.
	 *
	 * @var string[]
	 */
	private $typeByLabelOrAliasLookup = array();

	/**
	 * Array of default types to use for making datavalues for dataitems.
	 *
	 * @var string[]
	 */
	private $defaultDataItemTypeIds = array(
		DataItem::TYPE_BLOB => '_txt', // Text type
		DataItem::TYPE_URI => '_uri', // URL/URI type
		DataItem::TYPE_WIKIPAGE => '_wpg', // Page type
		DataItem::TYPE_NUMBER => '_num', // Number type
		DataItem::TYPE_TIME => '_dat', // Time type
		DataItem::TYPE_BOOLEAN => '_boo', // Boolean type
		DataItem::TYPE_CONTAINER => '_rec', // Value list type (replacing former nary properties)
		DataItem::TYPE_GEO => '_geo', // Geographical coordinates
		DataItem::TYPE_CONCEPT => '__con', // Special concept page type
		DataItem::TYPE_PROPERTY => '__pro', // Property type

		// If either of the following two occurs, we want to see a PHP error:
		//DataItem::TYPE_NOTYPE => '',
		//DataItem::TYPE_ERROR => '',
	);


	/**
	 * @var Closure[]
	 */
	private $extraneousFunctions = array();

	/**
	 * Returns a DataTypeRegistry instance
	 *
	 * @since 1.9
	 *
	 * @return DataTypeRegistry
	 */
	public static function getInstance() {

		if ( self::$instance === null ) {

			self::$instance = new self(
				$GLOBALS['smwgContLang']->getDatatypeLabels(),
				$GLOBALS['smwgContLang']->getDatatypeAliases(),
				$GLOBALS['smwgContLang']->getCanonicalDatatypeLabels()
			);

			self::$instance->initDatatypes();
		}

		return self::$instance;
	}

	/**
	 * Resets the DataTypeRegistry instance
	 *
	 * @since 1.9
	 */
	public static function clear() {
		self::$instance = null;
	}

	/**
	 * @since 1.9.0.2
	 *
	 * @param array $typeLabels
	 * @param array $typeAliases
	 */
	public function __construct( array $typeLabels = array() , array $typeAliases = array(), array $canonicalLabels = array() ) {
		foreach ( $typeLabels as $typeId => $typeLabel ) {
			$this->registerTypeLabel( $typeId, $typeLabel );
		}

		foreach ( $typeAliases as $typeAlias => $typeId ) {
			$this->registerDataTypeAlias( $typeId, $typeAlias );
		}

		foreach ( $canonicalLabels as $label => $id ) {
			$this->canonicalLabels[$id] = $label;
		}
	}

	/**
	 * Get the preferred data item ID for a given type. The ID defines the
	 * appropriate data item class for processing data of this type. See
	 * DataItem for possible values.
	 *
	 * @note SMWDIContainer is a pseudo dataitem type that is used only in
	 * data input methods, but not for storing data. Types that work with
	 * SMWDIContainer use SMWDIWikiPage as their DI type. (Since SMW 1.8)
	 *
	 * @param $typeId string id string for the given type
	 * @return integer data item ID
	 */
	public function getDataItemId( $typeId ) {
		if ( isset( $this->typeDataItemIds[$typeId] ) ) {
			return $this->typeDataItemIds[$typeId];
		}

		return DataItem::TYPE_NOTYPE;
	}

	/**
	 * @since  2.0
	 *
	 * @param string
	 * @return boolean
	 */
	public function isKnownTypeId( $typeId ) {
		return isset( $this->typeDataItemIds[$typeId] );
	}

	/**
	 * A function for registering/overwriting datatypes for SMW. Should be
	 * called from within the hook 'smwInitDatatypes'.
	 *
	 * @param $id string type ID for which this datatype is registered
	 * @param $className string name of the according subclass of SMWDataValue
	 * @param $dataItemId integer ID of the data item class that this data value uses, see DataItem
	 * @param $label mixed string label or false for types that cannot be accessed by users
	 */
	public function registerDataType( $id, $className, $dataItemId, $label = false ) {
		$this->typeClasses[$id] = $className;
		$this->typeDataItemIds[$id] = $dataItemId;

		if ( $label !== false ) {
			$this->registerTypeLabel( $id, $label );
		}
	}

	private function registerTypeLabel( $typeId, $typeLabel ) {
		$this->typeLabels[$typeId] = $typeLabel;
		$this->addTextToIdLookupMap( $typeId, $typeLabel );
	}

	private function addTextToIdLookupMap( $dataTypeId, $text ) {
		$this->typeByLabelOrAliasLookup[strtolower($text)] = $dataTypeId;
	}

	/**
	 * Add a new alias label to an existing datatype id. Note that every ID
	 * should have a primary label, either provided by SMW or registered with
	 * registerDataType(). This function should be called from within the hook
	 * 'smwInitDatatypes'.
	 *
	 * @param string $typeId
	 * @param string $typeAlias
	 */
	public function registerDataTypeAlias( $typeId, $typeAlias ) {
		$this->typeAliases[$typeAlias] = $typeId;
		$this->addTextToIdLookupMap( $typeId, $typeAlias );
	}

	/**
	 * Look up the ID that identifies the datatype of the given label
	 * internally. This id is used for all internal operations. If the
	 * label does not belong to a known type, the empty string is returned.
	 *
	 * The lookup is case insensitive.
	 *
	 * @param string $label
	 *
	 * @return string
	 */
	public function findTypeId( $label ) {

		$label = strtolower( $label );

		if ( isset( $this->typeByLabelOrAliasLookup[$label] ) ) {
			return $this->typeByLabelOrAliasLookup[$label];
		}

		return '';
	}

	/**
	 * Get the translated user label for a given internal ID. If the ID does
	 * not have a label associated with it in the current language, the
	 * empty string is returned. This is the case both for internal type ids
	 * and for invalid (unknown) type ids, so this method cannot be used to
	 * distinguish the two.
	 *
	 * @param string $id
	 *
	 * @return string
	 */
	public function findTypeLabel( $id ) {

		if ( isset( $this->typeLabels[$id] ) ) {
			return $this->typeLabels[$id];
		}

		// internal type without translation to user space;
		// might also happen for historic types after an upgrade --
		// alas, we have no idea what the former label would have been
		return '';
	}

	/**
	 * Returns a label for a typeId that is independent from the user/content
	 * language
	 *
	 * @since 2.3
	 *
	 * @return string
	 */
	public function findCanonicalLabelById( $id ) {

		if ( isset( $this->canonicalLabels[$id] ) ) {
			return $this->canonicalLabels[$id];
		}

		return '';
	}

	/**
	 * Return an array of all labels that a user might specify as the type of
	 * a property, and that are internal (i.e. not user defined). No labels are
	 * returned for internal types without user labels (e.g. the special types
	 * for some special properties), and for user defined types.
	 *
	 * @return array
	 */
	public function getKnownTypeLabels() {
		return $this->typeLabels;
	}

	/**
	 * @since 2.1
	 *
	 * @return array
	 */
	public function getKnownTypeAliases() {
		return $this->typeAliases;
	}

	/**
	 * Returns a default DataItemId
	 *
	 * @since 1.9
	 *
	 * @param string $diType
	 *
	 * @return string|null
	 */
	public function getDefaultDataItemTypeId( $diType ) {

		if ( isset( $this->defaultDataItemTypeIds[$diType] ) ) {
			return $this->defaultDataItemTypeIds[$diType];
		}

		return null;
	}

	/**
	 * Returns a class based on a typeId
	 *
	 * @since 1.9
	 *
	 * @param string $typeId
	 *
	 * @return string|null
	 */
	public function getDataTypeClassById( $typeId ) {

		if ( $this->hasDataTypeClassById( $typeId ) ) {
			return $this->typeClasses[$typeId];
		}

		return null;
	}

	/**
	 * Whether a datatype class is registered for a particular typeId
	 *
	 * @since 1.9
	 *
	 * @param string $typeId
	 *
	 * @return boolean
	 */
	public function hasDataTypeClassById( $typeId ) {
		return isset( $this->typeClasses[$typeId] ) && class_exists( $this->typeClasses[$typeId] );
	}

	/**
	 * Gather all available datatypes and label<=>id<=>datatype
	 * associations. This method is called before most methods of this
	 * factory.
	 */
	protected function initDatatypes() {
		// Setup built-in datatypes.
		// NOTE: all ids must start with underscores, where two underscores indicate
		// truly internal (non user-acceptable types). All others should also get a
		// translation in the language files, or they won't be available for users.
		$this->typeClasses = array(
			'_txt'  => 'SMWStringValue', // Text type
			'_cod'  => 'SMWStringValue', // Code type
			'_str'  => 'SMWStringValue', // DEPRECATED Will vanish after SMW 1.9; use '_txt'
			'_ema'  => 'SMWURIValue', // Email type
			'_uri'  => 'SMWURIValue', // URL/URI type
			'_anu'  => 'SMWURIValue', // Annotation URI type
			'_tel'  => 'SMWURIValue', // Phone number (URI) type
			'_wpg'  => 'SMWWikiPageValue', // Page type
			'_wpp'  => 'SMWWikiPageValue', // Property page type TODO: make available to user space
			'_wpc'  => 'SMWWikiPageValue', // Category page type TODO: make available to user space
			'_wpf'  => 'SMWWikiPageValue', // Form page type for Semantic Forms
			'_num'  => 'SMWNumberValue', // Number type
			'_tem'  => 'SMWTemperatureValue', // Temperature type
			'_dat'  => 'SMWTimeValue', // Time type
			'_boo'  => 'SMWBoolValue', // Boolean type
			'_rec'  => 'SMWRecordValue', // Value list type (replacing former nary properties)
			'_qty'  => 'SMWQuantityValue', // Type for numbers with units of measurement
			// Special types are not avaialble directly for users (and have no local language name):
			'__typ' => 'SMWTypesValue', // Special type page type
			'__pls' => 'SMWPropertyListValue', // Special type list for decalring _rec properties
			'__con' => 'SMWConceptValue', // Special concept page type
			'__sps' => 'SMWStringValue', // Special string type
			'__spu' => 'SMWURIValue', // Special uri type
			'__sob' => 'SMWWikiPageValue', // Special subobject type
			'__sup' => 'SMWWikiPageValue', // Special subproperty type
			'__suc' => 'SMWWikiPageValue', // Special subcategory type
			'__spf' => 'SMWWikiPageValue', // Special Form page type for Semantic Forms
			'__sin' => 'SMWWikiPageValue', // Special instance of type
			'__red' => 'SMWWikiPageValue', // Special redirect type
			'__err' => 'SMWErrorValue', // Special error type
			'__imp' => 'SMW\DataValues\ImportValue', // Special import vocabulary type
			'__pro' => 'SMWPropertyValue', // Property type (possibly predefined, no always based on a page)
			'__key' => 'SMWStringValue', // Sort key of a page
		);

		$this->typeDataItemIds = array(
			'_txt'  => DataItem::TYPE_BLOB, // Text type
			'_cod'  => DataItem::TYPE_BLOB, // Code type
			'_str'  => DataItem::TYPE_BLOB, // DEPRECATED Will vanish after SMW 1.9; use '_txt'
			'_ema'  => DataItem::TYPE_URI, // Email type
			'_uri'  => DataItem::TYPE_URI, // URL/URI type
			'_anu'  => DataItem::TYPE_URI, // Annotation URI type
			'_tel'  => DataItem::TYPE_URI, // Phone number (URI) type
			'_wpg'  => DataItem::TYPE_WIKIPAGE, // Page type
			'_wpp'  => DataItem::TYPE_WIKIPAGE, // Property page type TODO: make available to user space
			'_wpc'  => DataItem::TYPE_WIKIPAGE, // Category page type TODO: make available to user space
			'_wpf'  => DataItem::TYPE_WIKIPAGE, // Form page type for Semantic Forms
			'_num'  => DataItem::TYPE_NUMBER, // Number type
			'_tem'  => DataItem::TYPE_NUMBER, // Temperature type
			'_dat'  => DataItem::TYPE_TIME, // Time type
			'_boo'  => DataItem::TYPE_BOOLEAN, // Boolean type
			'_rec'  => DataItem::TYPE_WIKIPAGE, // Value list type (replacing former nary properties)
			'_geo'  => DataItem::TYPE_GEO, // Geographical coordinates
			'_gpo'  => DataItem::TYPE_BLOB, // Geographical polygon
			'_qty'  => DataItem::TYPE_NUMBER, // Type for numbers with units of measurement
			// Special types are not available directly for users (and have no local language name):
			'__typ' => DataItem::TYPE_URI, // Special type page type
			'__pls' => DataItem::TYPE_BLOB, // Special type list for decalring _rec properties
			'__con' => DataItem::TYPE_CONCEPT, // Special concept page type
			'__sps' => DataItem::TYPE_BLOB, // Special string type
			'__spu' => DataItem::TYPE_URI, // Special uri type
			'__sob' => DataItem::TYPE_WIKIPAGE, // Special subobject type
			'__sup' => DataItem::TYPE_WIKIPAGE, // Special subproperty type
			'__suc' => DataItem::TYPE_WIKIPAGE, // Special subcategory type
			'__spf' => DataItem::TYPE_WIKIPAGE, // Special Form page type for Semantic Forms
			'__sin' => DataItem::TYPE_WIKIPAGE, // Special instance of type
			'__red' => DataItem::TYPE_WIKIPAGE, // Special redirect type
			'__err' => DataItem::TYPE_ERROR, // Special error type
			'__imp' => DataItem::TYPE_BLOB, // Special import vocabulary type
			'__pro' => DataItem::TYPE_PROPERTY, // Property type (possibly predefined, no always based on a page)
			'__key' => DataItem::TYPE_BLOB, // Sort key of a page
		);

		// Deprecated since 1.9
		wfRunHooks( 'smwInitDatatypes' );

		// Since 1.9
		\Hooks::run( 'SMW::DataType::initTypes', array( $this ) );
	}

	/**
	 * Inject services and objects that are planned to be used during the invocation of
	 * a DataValue
	 *
	 * @since 2.3
	 *
	 * @param string  $name
	 * @param \Closure $callback
	 */
	public function registerExtraneousFunction( $name, \Closure $callback ) {
		$this->extraneousFunctions[$name] = $callback;
	}

	/**
	 * @since 2.3
	 *
	 * @return Closure[]
	 */
	public function getExtraneousFunctions() {
		return $this->extraneousFunctions;
	}

}

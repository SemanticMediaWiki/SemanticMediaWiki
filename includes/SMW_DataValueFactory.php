<?php
/**
 * This file contains the SMWDataValueFactory class.
 *
 * @author Markus Krötzsch
 * @author Jeroen De Dauw
 *
 * @file
 * @ingroup SMWDataValues
 */

/**
 * Factory class for creating SMWDataValue objects for supplied types or
 * properties and data values.
 *
 * The class has the main entry point newTypeIdValue(), which creates a new
 * datavalue object, possibly with preset user values, captions and
 * property names. To create suitable datavalues for a given property, the
 * method newPropertyObjectValue() can be used.
 *
 * Other than this, the class manages registration data for datatypes, and
 * provides various methods to access this information.
 *
 * @ingroup SMWDataValues
 */
class SMWDataValueFactory {

	/**
	 * Array of type labels indexed by type ids. Used for datatype resolution.
	 *
	 * @var array
	 */
	static private $mTypeLabels;

	/**
	 * Array of ids indexed by type aliases. Used for datatype resolution.
	 *
	 * @var array
	 */
	static private $mTypeAliases;

	/**
	 * Array of class names for creating new SMWDataValue, indexed by type
	 * id.
	 *
	 * @var array of string
	 */
	static private $mTypeClasses;

	/**
	 * Array of data item classes, indexed by type id.
	 *
	 * @var array of integer
	 */
	static private $mTypeDataItemIds;

	/**
	 * Array of default types to use for making datavalues for dataitems.
	 *
	 * @var array of string
	 */
	static private $mDefaultDataItemTypeIds = array(
		SMWDataItem::TYPE_BLOB => '_txt', // Text type
		SMWDataItem::TYPE_STRING => '_str', // String type
		SMWDataItem::TYPE_URI => '_uri', // URL/URI type
		SMWDataItem::TYPE_WIKIPAGE => '_wpg', // Page type
		SMWDataItem::TYPE_NUMBER => '_num', // Number type
		SMWDataItem::TYPE_TIME => '_dat', // Time type
		SMWDataItem::TYPE_BOOLEAN => '_boo', // Boolean type
		SMWDataItem::TYPE_CONTAINER => '_rec', // Value list type (replacing former nary properties)
		SMWDataItem::TYPE_GEO => '_geo', // Geographical coordinates
		SMWDataItem::TYPE_CONCEPT => '__con', // Special concept page type
		SMWDataItem::TYPE_PROPERTY => '__pro', // Property type
		// If either of the following two occurs, we want to see a PHP error:
		//SMWDataItem::TYPE_NOTYPE => '',
		//SMWDataItem::TYPE_ERROR => '',
	);

	/**
	 * Create a value from a type id. If no $value is given, an empty
	 * container is created, the value of which can be set later on.
	 *
	 * @param $typeId string id string for the given type
	 * @param $valueString mixed user value string, or false if unknown
	 * @param $caption mixed user-defined caption, or false if none given
	 * @param $property SMWDIProperty property object for which this value is made, or null
	 * @param $contextPage SMWDIWikiPage that provides a context for parsing the value string, or null
	 *
	 * @return SMWDataValue
	 */
	static public function newTypeIdValue( $typeId, $valueString = false, $caption = false,
			$property = null, $contextPage = null ) {

		self::initDatatypes();

		if ( array_key_exists( $typeId, self::$mTypeClasses ) ) {
			$result = new self::$mTypeClasses[$typeId]( $typeId );
		} else {
			return new SMWErrorValue( $typeId,
				wfMessage( 'smw_unknowntype', $typeId )->inContentLanguage()->text(),
				$valueString, $caption );
		}

		if ( !is_null( $property ) ) {
			$result->setProperty( $property );
		}

		if ( !is_null( $contextPage ) ) {
			$result->setContextPage( $contextPage );
		}

		if ( $valueString !== false ) {
			$result->setUserValue( $valueString, $caption );
		}

		return $result;
	}

	/**
	 * Create a value for a data item.
	 *
	 * @param $dataItem SMWDataItem
	 * @param $property mixed null or SMWDIProperty property object for which this value is made
	 * @param $caption mixed user-defined caption, or false if none given
	 *
	 * @return SMWDataValue
	 */
	static public function newDataItemValue( SMWDataItem $dataItem, SMWDIProperty $property = null, $caption = false ) {
		if ( !is_null( $property ) ) {
			$typeId = $property->findPropertyTypeID();
		} else {
			$typeId = self::$mDefaultDataItemTypeIds[$dataItem->getDiType()];
		}

		$result = self::newTypeIdValue( $typeId, false, $caption, $property );
		$result->setDataItem( $dataItem );

		if ( $caption !== false ) {
			$result->setCaption( $caption );
		}

		return $result;
	}


	/**
	 * Get the preferred data item ID for a given type. The ID defines the
	 * appropriate data item class for processing data of this type. See
	 * SMWDataItem for possible values.
	 *
	 * @note SMWDIContainer is a pseudo dataitem type that is used only in
	 * data input methods, but not for storing data. Types that work with
	 * SMWDIContainer use SMWDIWikiPage as their DI type. (Since SMW 1.8)
	 *
	 * @param $typeId string id string for the given type
	 * @return integer data item ID
	 */
	static public function getDataItemId( $typeId ) {
		self::initDatatypes();
		if ( array_key_exists( $typeId, self::$mTypeDataItemIds ) ) {
			return self::$mTypeDataItemIds[$typeId];
		} else {
			return SMWDataItem::TYPE_NOTYPE;
		}
	}

	/**
	 * Create a value for the given property, provided as an SMWDIProperty
	 * object. If no value is given, an empty container is created, the
	 * value of which can be set later on.
	 *
	 * @param $property SMWDIProperty property object for which this value is made
	 * @param $valueString mixed user value string, or false if unknown
	 * @param $caption mixed user-defined caption, or false if none given
	 * @param $contextPage SMWDIWikiPage that provides a context for parsing the value string, or null
	 *
	 * @return SMWDataValue
	 */
	static public function newPropertyObjectValue( SMWDIProperty $property, $valueString = false,
			$caption = false, $contextPage = null ) {

		$typeId = $property->isInverse() ? '_wpg' : $property->findPropertyTypeID();
		return self::newTypeIdValue( $typeId, $valueString, $caption, $property, $contextPage );
	}

	/**
	 * Gather all available datatypes and label<=>id<=>datatype
	 * associations. This method is called before most methods of this
	 * factory.
	 */
	static protected function initDatatypes() {
		global $smwgContLang;

		if ( is_array( self::$mTypeLabels ) ) {
			return; // init happened before
		}

		self::$mTypeLabels = $smwgContLang->getDatatypeLabels();
		self::$mTypeAliases = $smwgContLang->getDatatypeAliases();

		// Setup built-in datatypes.
		// NOTE: all ids must start with underscores, where two underscores indicate
		// truly internal (non user-acessible types). All others should also get a
		// translation in the language files, or they won't be available for users.
		self::$mTypeClasses = array(
			'_txt'  => 'SMWStringValue', // Text type
			'_cod'  => 'SMWStringValue', // Code type
			'_str'  => 'SMWStringValue', // String type
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
			'__sup' => 'SMWWikiPageValue', // Special subproperty type
			'__suc' => 'SMWWikiPageValue', // Special subcategory type
			'__spf' => 'SMWWikiPageValue', // Special Form page type for Semantic Forms
			'__sin' => 'SMWWikiPageValue', // Special instance of type
			'__red' => 'SMWWikiPageValue', // Special redirect type
			'__err' => 'SMWErrorValue', // Special error type
			'__imp' => 'SMWImportValue', // Special import vocabulary type
			'__pro' => 'SMWPropertyValue', // Property type (possibly predefined, no always based on a page)
			'__key' => 'SMWStringValue', // Sort key of a page
		);

		self::$mTypeDataItemIds = array(
			'_txt'  => SMWDataItem::TYPE_BLOB, // Text type
			'_cod'  => SMWDataItem::TYPE_BLOB, // Code type
			'_str'  => SMWDataItem::TYPE_STRING, // String type
			'_ema'  => SMWDataItem::TYPE_URI, // Email type
			'_uri'  => SMWDataItem::TYPE_URI, // URL/URI type
			'_anu'  => SMWDataItem::TYPE_URI, // Annotation URI type
			'_tel'  => SMWDataItem::TYPE_URI, // Phone number (URI) type
			'_wpg'  => SMWDataItem::TYPE_WIKIPAGE, // Page type
			'_wpp'  => SMWDataItem::TYPE_WIKIPAGE, // Property page type TODO: make available to user space
			'_wpc'  => SMWDataItem::TYPE_WIKIPAGE, // Category page type TODO: make available to user space
			'_wpf'  => SMWDataItem::TYPE_WIKIPAGE, // Form page type for Semantic Forms
			'_num'  => SMWDataItem::TYPE_NUMBER, // Number type
			'_tem'  => SMWDataItem::TYPE_NUMBER, // Temperature type
			'_dat'  => SMWDataItem::TYPE_TIME, // Time type
			'_boo'  => SMWDataItem::TYPE_BOOLEAN, // Boolean type
			'_rec'  => SMWDataItem::TYPE_WIKIPAGE, // Value list type (replacing former nary properties)
			'_geo'  => SMWDataItem::TYPE_GEO, // Geographical coordinates
			'_gpo'  => SMWDataItem::TYPE_BLOB, // Geographical polygon
			'_qty'  => SMWDataItem::TYPE_NUMBER, // Type for numbers with units of measurement
			// Special types are not avaialble directly for users (and have no local language name):
			'__typ' => SMWDataItem::TYPE_URI, // Special type page type
			'__pls' => SMWDataItem::TYPE_STRING, // Special type list for decalring _rec properties
			'__con' => SMWDataItem::TYPE_CONCEPT, // Special concept page type
			'__sps' => SMWDataItem::TYPE_STRING, // Special string type
			'__spu' => SMWDataItem::TYPE_URI, // Special uri type
			'__sup' => SMWDataItem::TYPE_WIKIPAGE, // Special subproperty type
			'__suc' => SMWDataItem::TYPE_WIKIPAGE, // Special subcategory type
			'__spf' => SMWDataItem::TYPE_WIKIPAGE, // Special Form page type for Semantic Forms
			'__sin' => SMWDataItem::TYPE_WIKIPAGE, // Special instance of type
			'__red' => SMWDataItem::TYPE_WIKIPAGE, // Special redirect type
			'__err' => SMWDataItem::TYPE_ERROR, // Special error type
			'__imp' => SMWDataItem::TYPE_STRING, // Special import vocabulary type
			'__pro' => SMWDataItem::TYPE_PROPERTY, // Property type (possibly predefined, no always based on a page)
			'__key' => SMWDataItem::TYPE_STRING, // Sort key of a page
		);

		wfRunHooks( 'smwInitDatatypes' );
	}

	/**
	 * A function for registering/overwriting datatypes for SMW. Should be
	 * called from within the hook 'smwInitDatatypes'.
	 *
	 * @param $id string type ID for which this datatype is registered
	 * @param $className string name of the according subclass of SMWDataValue
	 * @param $dataItemId integer ID of the data item class that this data value uses, see SMWDataItem
	 * @param $label mixed string label or false for types that cannot be accessed by users
	 */
	static public function registerDatatype( $id, $className, $dataItemId, $label = false ) {
		self::$mTypeClasses[$id] = $className;
		self::$mTypeDataItemIds[$id] = $dataItemId;

		if ( $label != false ) {
			self::$mTypeLabels[$id] = $label;
		}
	}

	/**
	 * Add a new alias label to an existing datatype id. Note that every ID
	 * should have a primary label, either provided by SMW or registered with
	 * registerDatatype(). This function should be called from within the hook
	 * 'smwInitDatatypes'.
	 *
	 * @param string $id
	 * @param string $label
	 */
	static public function registerDatatypeAlias( $id, $label ) {
		self::$mTypeAliases[$label] = $id;
	}

	/**
	 * Look up the ID that identifies the datatype of the given label
	 * internally. This id is used for all internal operations. If the
	 * label does not bleong to a known type, the empty string is returned.
	 *
	 * This method may or may not take aliases into account, depeding on
	 * the parameter $useAlias.
	 *
	 * @param string $label
	 * @param boolean $useAlias
	 * @return string
	 */
	static public function findTypeID( $label, $useAlias = true ) {
		self::initDatatypes();
		$id = array_search( $label, self::$mTypeLabels );

		if ( $id !== false ) {
			return $id;
		} elseif ( ( $useAlias ) && ( array_key_exists( $label, self::$mTypeAliases ) ) ) {
			return self::$mTypeAliases[$label];
		} else {
			return '';
		}
	}

	/**
	 * Get the translated user label for a given internal ID. If the ID does
	 * not have a label associated with it in the current language, the 
	 * empty string is returned. This is the case both for internal type ids
	 * and for invalid (unkown) type ids, so this method cannot be used to
	 * distinguish the two.
	 *
	 * @param string $id
	 */
	static public function findTypeLabel( $id ) {
		self::initDatatypes();

		if ( array_key_exists( $id, self::$mTypeLabels ) ) {
			return self::$mTypeLabels[$id];
		} else { // internal type without translation to user space;
			// might also happen for historic types after an upgrade --
			// alas, we have no idea what the former label would have been
			return '';
		}
	}

	/**
	 * Return an array of all labels that a user might specify as the type of
	 * a property, and that are internal (i.e. not user defined). No labels are
	 * returned for internal types without user labels (e.g. the special types
	 * for some special properties), and for user defined types.
	 *
	 * @return array
	 */
	static public function getKnownTypeLabels() {
		self::initDatatypes();
		return self::$mTypeLabels;
	}

}

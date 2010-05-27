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
 * The class has two main entry points:
 * - newTypeObjectValue()
 * - newTypeIDValue()
 *
 * These create new DV objects, possibly with preset user values, captions and
 * property names. Further methods are used to conveniently create DVs for
 * properties and special properties:
 * - newPropertyObjectValue()
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
	 * Array of class names for creating new SMWDataValue, indexed by type id.
	 * 
	 * @var array of SMWDataValue
	 */
	static private $mTypeClasses;

	/**
	 * Create an SMWDataValue object that can hold values for the type that the
	 * given SMWTypesValue object specifies. If no $value is given, an empty
	 * container is created, the value of which can be set later on.
	 * 
	 * @param SMWTypesValue $typevalue Represents the type of the object
	 * @param mixed $value user value string, or false if unknown
	 * @param mixed $caption user-defined caption or false if none given
	 * @param $property SMWPropertyValue property object for which this value was made, or NULL
	 */
	static public function newTypeObjectValue( SMWTypesValue $typeValue, $value = false, $caption = false, $property = null ) {
		if ( !$typeValue->isValid() ) { // just return the error, pass it through
			$result = self::newTypeIDValue( '__err' );
			$result->addError( $typeValue->getErrors() );
			
			return $result;
		}
		
		return self::newTypeIDValue( $typeValue->getDBkey(), $value, $caption, $property );
	}

	/**
	 * Create a value from a type id. If no $value is given, an empty container
	 * is created, the value of which can be set later on.
	 * 
	 * @param $typeid id string for the given type
	 * @param mixed $value user value string, or false if unknown
	 * @param mixed $caption user-defined caption or false if none given
	 * @param SMWPropertyValue $property Property object for which this value was made, or NULL
	 * 
	 * @return SMWDataValue
	 */
	static public function newTypeIDValue( $typeid, $value = false, $caption = false, $property = null ) {
		self::initDatatypes();
		
		if ( array_key_exists( $typeid, self::$mTypeClasses ) ) { // direct response for basic types
			$result = new self::$mTypeClasses[$typeid]( $typeid );
		} elseif ( ( $typeid != '' ) && ( $typeid { 0 } != '_' ) ) { // custom type with linear conversion
			$result = new self::$mTypeClasses['__lin']( $typeid );
		} else { // type really unknown
			smwfLoadExtensionMessages( 'SemanticMediaWiki' );
			return new SMWErrorValue( wfMsgForContent( 'smw_unknowntype', $typeid ), $value, $caption );
		}
		
		if ( $property !== null ) $result->setProperty( $property );
		if ( $value !== false ) $result->setUserValue( $value, $caption );
		
		return $result;
	}

	/**
	 * Create a value for the given property, provided as an SMWPropertyValue
	 * object. If no value is given, an empty container is created, the value
	 * of which can be set later on.
	 * 
	 * @param SMWPropertyValue $property
	 * @param mixed $value
	 * @param mixed $caption
	 * 
	 * @return SMWDataValue
	 */
	static public function newPropertyObjectValue( SMWPropertyValue $property, $value = false, $caption = false ) {
		if ( $property->isInverse() ) {
			return self::newTypeIdValue( '_wpg', $value, $caption, $property );
		} else {
			return self::newTypeIDValue( $property->getPropertyTypeID(), $value, $caption, $property );
		}
	}

	/**
	 * Gather all available datatypes and label<=>id<=>datatype associations.
	 * This method is called before most methods of this factory.
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
			// Special types are not avaialble directly for users (and have no local language name):
			'__typ' => 'SMWTypesValue', // Special type page type
			'__tls' => 'SMWTypeListValue', // Special type list for decalring _rec properties
			'__con' => 'SMWConceptValue', // Special concept page type
			'__sps' => 'SMWStringValue', // Special string type
			'__spu' => 'SMWURIValue', // Special uri type
			'__sup' => 'SMWWikiPageValue', // Special subproperty type
			'__suc' => 'SMWWikiPageValue', // Special subcategory type
			'__spf' => 'SMWWikiPageValue', // Special Form page type for Semantic Forms
			'__sin' => 'SMWWikiPageValue', // Special instance of type
			'__red' => 'SMWWikiPageValue', // Special redirect type
			'__lin' => 'SMWLinearValue', // Special linear unit conversion type
			'__err' => 'SMWErrorValue', // Special error type
			'__imp' => 'SMWImportValue', // Special import vocabulary type
			'__pro' => 'SMWPropertyValue', // Property type (possibly predefined, no always based on a page)
		);
		
		wfRunHooks( 'smwInitDatatypes' );
	}

	/**
	 * A function for registering/overwriting datatypes for SMW. Should be
	 * called from within the hook 'smwInitDatatypes'.
	 * 
	 * @param string $id
	 * @param string $className
	 * @param mixed $label
	 */
	static public function registerDatatype( $id, $className, $label = false ) {
		self::$mTypeClasses[$id] = $className;
		
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
	 * internally. This id is used for all internal operations. Compound types
	 * are not supported by this method (decomposition happens earlier). Custom
	 * types get their DBkeyed label as id. All ids are prefixed by an
	 * underscore in order to distinguish them from custom types.
	 *
	 * This method may or may not take aliases into account. For unknown
	 * labels, the normalised (DB-version) label is used as an ID.
	 * 
	 * @param string $label
	 * @param boolean $useAlias
	 */
	static public function findTypeID( $label, $useAlias = true ) {
		self::initDatatypes();
		$id = array_search( $label, self::$mTypeLabels );
		
		if ( $id !== false ) {
			return $id;
		} elseif ( ( $useAlias ) && ( array_key_exists( $label, self::$mTypeAliases ) ) ) {
			return self::$mTypeAliases[$label];
		} else {
			return str_replace( ' ', '_', $label );
		}
	}

	/**
	 * Get the translated user label for a given internal ID. If the ID does
	 * not have a label associated with it in the current language, the ID
	 * itself is transformed into a label (appropriate for user defined types).
	 * 
	 * @param string $id
	 */
	static public function findTypeLabel( $id ) {
		self::initDatatypes();
		
		if ( $id { 0 } === '_' ) {
			if ( array_key_exists( $id, self::$mTypeLabels ) ) {
				return self::$mTypeLabels[$id];
			} else { // internal type without translation to user space;
			    // might also happen for historic types after an upgrade --
			    // alas, we have no idea what the former label would have been
				return str_replace( '_', ' ', $id );
			}
		} else { // non-builtin type, use id as label
			return str_replace( '_', ' ', $id );
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
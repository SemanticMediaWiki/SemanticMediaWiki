<?php
/**
 * The class in this file manages (special) properties that are
 * associated with a certain subject (article). It is used as a
 * container for chunks of subject-centred data.
 * 
 * @file
 * @ingroup SMW
 * 
 * @author Markus Krötzsch
 */

/**
 * Class for representing chunks of semantic data for one given
 * article (subject), similar what is typically displayed in the factbox.
 * This is a light-weight data container.
 * 
 * @ingroup SMW
 */
class SMWSemanticData {
	/// Text keys and arrays of datavalue objects.
	protected $propvals = array();
	/// Text keys and title objects.
	protected $properties = array();
	/// Stub property data that is not part of $propvals and $properties yet. Entries use
	/// property DB keys as keys. The value is an array of DBkey-arrays that define individual
	/// datavalues. The stubs will be set up when first accessed.
	protected $stubpropvals = array();
	/// Boolean, stating whether the container holds any normal properties.
	protected $hasvisibleprops = false;
	/// Boolean, stating whether the container holds any displayable special properties (some are internal only without a display name).
	protected $hasvisiblespecs = false;
	/// Boolean, stating whether this is a stub object. Stubbing might happen on serialisation to safe DB space
	public $stubobject = true;
	/**
	 *  Boolean, stating whether repeated values should be avoided. Not needing duplicte elimination
	 *  (e.g. when loading from store) can safe much time, since objects can remain stubs until someone
	 *  really acesses their value.
	 */
	protected $m_noduplicates;
	/// Cache for the local version of "Property:"
	static protected $m_propertyprefix = false;

	/// SMWWikiPageValue object that is the subject of this container.
	/// Subjects that are NULL are used to represent "internal objects" only.
	protected $subject;

	public function __construct( $subject, $noduplicates = true ) {
		$this->subject = $subject;
		$this->m_noduplicates = $noduplicates;
		$this->stubobject = false;
	}

	/**
	 * This object is added to the parser output of MediaWiki, but it is not useful to have all its data as part of the parser cache
	 * since the data is already stored in more accessible format in SMW. Hence this implementation of __sleep() makes sure only the
	 * subject is serialised, yielding a minimal stub data container after unserialisation. This is a little safer than serialising
	 * nothing: if, for any reason, SMW should ever access an unserialised parser output, then the Semdata container will at least
	 * look as if properly initialised (though empty).
	 * @note It might be even better to have other members with stub object data that is used for serializing, thus using much less data.
	 */
	public function __sleep() {
		return array( 'subject' );
	}

	/**
	 * Return subject to which the stored semantic annotation refer to.
	 * @return SMWWikiPageValue subject
	 */
	public function getSubject() {
		return $this->subject;
	}

	/**
	 * Get the array of all properties that have stored values.
	 */
	public function getProperties() {
		$this->unstubProperties();
		ksort( $this->properties, SORT_STRING );
		return $this->properties;
	}

	/**
	 * Get the array of all stored values for some property.
	 * 
	 * @param SMWPropertyValue $property
	 */
	public function getPropertyValues( SMWPropertyValue $property ) {
		if ( array_key_exists( $property->getDBkey(), $this->stubpropvals ) ) {
			// Unstub those entries completely.
			$this->unstubProperty( $property->getDBkey(), $property );
			
			foreach ( $this->stubpropvals[$property->getDBkey()] as $dbkeys ) {
				$dv = SMWDataValueFactory::newPropertyObjectValue( $property );
				$dv->setDBkeys( $dbkeys );
				
				if ( $this->m_noduplicates ) {
					$this->propvals[$property->getDBkey()][$dv->getHash()] = $dv;
				} else {
					$this->propvals[$property->getDBkey()][] = $dv;
				}
			}
			
			unset( $this->stubpropvals[$property->getDBkey()] );
		}
		
		if ( array_key_exists( $property->getDBkey(), $this->propvals ) ) {
			return $this->propvals[$property->getDBkey()];
		} else {
			return array();
		}
	}

	/**
	 * Generate a hash value to simplify the comparison of this data container with other
	 * containers. The hash uses PHP's md5 implementation, which is among the fastest hash
	 * algorithms that PHP offers.
	 */
	public function getHash() {
		$ctx = hash_init( 'md5' );
		
		if ( $this->subject !== null ) { // here and below, use "_#_" to separate values; really not much care needed here
			hash_update( $ctx, '_#_' . $this->subject->getHash() );
		}
		
		foreach ( $this->getProperties() as $property ) {
			hash_update( $ctx, '_#_' . $property->getHash() . '##' );
			
			foreach ( $this->getPropertyValues( $property ) as $dv ) {
				hash_update( $ctx, '_#_' . $dv->getHash() );
			}
		}
		
		return hash_final( $ctx );
	}

	/**
	 * Return true if there are any visible properties.
	 * @note While called "visible" this check actually refers to the function
	 * SMWPropertyValue::isShown(). The name is kept for compatibility.
	 */
	public function hasVisibleProperties() {
		$this->unstubProperties();
		return $this->hasvisibleprops;
	}

	/**
	 * Return true if there are any special properties that can
	 * be displayed.
	 * @note While called "visible" this check actually refers to the function
	 * SMWPropertyValue::isShown(). The name is kept for compatibility.
	 */
	public function hasVisibleSpecialProperties() {
		$this->unstubProperties();
		return $this->hasvisiblespecs;
	}

	/**
	 * Store a value for a property identified by its title object. Duplicate
	 * value entries are usually ignored.
	 * @note Attention: there is no check whether the type of the given datavalue agrees
	 * with what SMWDataValueFactory is producing (based on predefined property records and
	 * the current DB content). Always use SMWDataValueFactory to produce fitting values!
	 */
	public function addPropertyObjectValue( SMWPropertyValue $property, SMWDataValue $value ) {
		if ( !$property->isValid() ) return; // nothing we can do
		
		if ( !array_key_exists( $property->getDBkey(), $this->propvals ) ) {
			$this->propvals[$property->getDBkey()] = array();
			$this->properties[$property->getDBkey()] = $property;
		}
		
		if ( $this->m_noduplicates ) {
			$this->propvals[$property->getDBkey()][$value->getHash()] = $value;
		} else {
			$this->propvals[$property->getDBkey()][] = $value;
		}
		
		if ( !$property->isUserDefined() ) {
			if ( $property->isShown() ) {
				 $this->hasvisiblespecs = true;
				 $this->hasvisibleprops = true;
			}
		} else {
			$this->hasvisibleprops = true;
		}
	}

	/**
	 * Store a value for a given property identified by its text label (without
	 * namespace prefix). Duplicate value entries are usually ignored.
	 */
	public function addPropertyValue( $propertyname, SMWDataValue $value ) {
		$propertykey = smwfNormalTitleDBKey( $propertyname );
		
		if ( array_key_exists( $propertykey, $this->properties ) ) {
			$property = $this->properties[$propertykey];
		} else {
			if ( SMWSemanticData::$m_propertyprefix == false ) {
				global $wgContLang;
				SMWSemanticData::$m_propertyprefix = $wgContLang->getNsText( SMW_NS_PROPERTY ) . ':';
			} // explicitly use prefix to cope with things like [[Property:User:Stupid::somevalue]]
			
			$property = SMWPropertyValue::makeUserProperty( SMWSemanticData::$m_propertyprefix . $propertyname );
			
			if ( !$property->isValid() ) { // error, maybe illegal title text
				return;
			}
		}
		
		$this->addPropertyObjectValue( $property, $value );
	}

	/**
	 * Add data in abbreviated form so that it is only expanded if needed. The property key
	 * is the DB key (string) of a property value, whereas valuekeys is an array of DBkeys for
	 * the added value that will be used to initialize the value if needed at some point.
	 */
	public function addPropertyStubValue( $propertykey, $valuekeys ) {
		// Catch built-in properties, since their internal key is not what is used as a key elsewhere in SMWSemanticData.
		if ( $propertykey { 0 } == '_' ) {
			$property = SMWPropertyValue::makeProperty( $propertykey );
			$propertykey = $property->getDBkey();
			$this->unstubProperty( $propertykey, $property );
		}
		
		$this->stubpropvals[$propertykey][] = $valuekeys;
	}

	/**
	 * Delete all data other than the subject.
	 */
	public function clear() {
		$this->propvals = array();
		$this->properties = array();
		$this->stubpropvals = array();
		$this->hasvisibleprops = false;
		$this->hasvisiblespecs = false;
	}

	/**
	 * Process all properties that have been added as stubs. Associated data may remain in stub form.
	 */
	protected function unstubProperties() {
		foreach ( $this->stubpropvals as $pname => $values ) { // unstub property values only, the value lists are still kept as stubs
			$this->unstubProperty( $pname );
		}
	}

	/**
	 * Unstub a single property from the stub data array. If available, an existing object
	 * for that property might be provided, so we do not need to make a new one. It is not
	 * checked if the object matches the property name.
	 */
	protected function unstubProperty( $pname, $propertyobj = null ) {
		if ( !array_key_exists( $pname, $this->properties ) ) {
			if ( $propertyobj === null ) {
				$propertyobj = SMWPropertyValue::makeProperty( $pname );
			}
			
			$this->properties[$pname] = $propertyobj;
			
			if ( !$propertyobj->isUserDefined() ) {
				if ( $propertyobj->isShown() ) {
					 $this->hasvisiblespecs = true;
					 $this->hasvisibleprops = true;
				}
			} else {
				$this->hasvisibleprops = true;
			}
		}
	}

}
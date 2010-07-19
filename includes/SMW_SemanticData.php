<?php
/**
 * The class in this file manages (special) mProperties that are
 * associated with a certain subject (article). It is used as a
 * container for chunks of subject-centred data.
 * 
 * @file
 * @ingroup SMW
 * 
 * @author Markus Krötzsch
 * @author Jeroen De Dauw
 */

/**
 * Class for representing chunks of semantic data for one given
 * article (subject), similar what is typically displayed in the factbox.
 * This is a light-weight data container.
 * 
 * @ingroup SMW
 */
class SMWSemanticData {
	
	/**
	 * States whether this is a stub object. Stubbing might happen on serialisation to save DB space.
	 * 
	 * @var boolean
	 */
	public $stubObject = true;	
	
	/**
	 * Cache for the local version of "Property:"
	 * 
	 * @var mixed
	 */
	static protected $mPropertyPrefix = false;	
	
	/**
	 * Text keys and arrays of datavalue objects.
	 * 
	 * @var array
	 */ 
	protected $mPropVals = array();
	
	/**
	 * Text keys and title objects.
	 * 
	 * @var array
	 */
	protected $mProperties = array();
	
	/**
	 * Stub property data that is not part of $propvals and $mProperties yet. Entries use
	 * property DB keys as keys. The value is an array of DBkey-arrays that define individual
	 * datavalues. The stubs will be set up when first accessed.
	 * 
	 * @var array
	 */
	protected $mStubPropVals = array();
	
	/**
	 * States whether the container holds any normal properties.
	 * 
	 * @var boolean
	 */
	protected $mHasVisibleProps = false;
	
	/**
	 * States whether the container holds any displayable special mProperties (some are internal only without a display name).
	 * 
	 * @var boolean
	 */
	protected $mHasVisibleSpecs = false;
	
	/**
	 *  States whether repeated values should be avoided. Not needing duplicte elimination
	 *  (e.g. when loading from store) can save much time, since objects can remain stubs until someone
	 *  really acesses their value.
	 *  
	 *  @var boolean
	 */
	protected $mNoDuplicates;

	/**
	 * SMWWikiPageValue object that is the subject of this container.
	 * Subjects that are NULL are used to represent "internal objects" only.
	 * 
	 * @var SMWWikiPageValue
	 */
	protected $mSubject;

	/**
	 * Constructor.
	 * 
	 * @param SMWWikiPageValue $subject
	 * @param boolean $noDuplicates
	 */
	public function __construct( $subject, $noDuplicates = true ) {
		$this->mSubject = $subject;
		$this->mNoDuplicates = $noDuplicates;
		$this->stubObject = false;
	}

	/**
	 * This object is added to the parser output of MediaWiki, but it is not useful to have all its data as part of the parser cache
	 * since the data is already stored in more accessible format in SMW. Hence this implementation of __sleep() makes sure only the
	 * subject is serialised, yielding a minimal stub data container after unserialisation. This is a little safer than serialising
	 * nothing: if, for any reason, SMW should ever access an unserialised parser output, then the Semdata container will at least
	 * look as if properly initialised (though empty).
	 * 
	 * @note It might be even better to have other members with stub object data that is used for serializing, thus using much less data.
	 * 
	 * @return array
	 */
	public function __sleep() {
		return array( 'mSubject' );
	}

	/**
	 * Return subject to which the stored semantic annotation refer to.
	 * 
	 * @return SMWWikiPageValue subject
	 */
	public function getSubject() {
		return $this->mSubject;
	}

	/**
	 * Get the array of all properties that have stored values.
	 */
	public function getProperties() {
		$this->unstubProperties();
		ksort( $this->mProperties, SORT_STRING );
		
		return $this->mProperties;
	}

	/**
	 * Get the array of all stored values for some property.
	 * 
	 * @param SMWPropertyValue $property
	 * 
	 * @return array
	 */
	public function getPropertyValues( SMWPropertyValue $property ) {
		if ( array_key_exists( $property->getDBkey(), $this->mStubPropVals ) ) {
			// Unstub those entries completely.
			$this->unstubProperty( $property->getDBkey(), $property );
			
			foreach ( $this->mStubPropVals[$property->getDBkey()] as $dbkeys ) {
				$dv = SMWDataValueFactory::newPropertyObjectValue( $property );
				$dv->setDBkeys( $dbkeys );
				
				if ( $this->mNoDuplicates ) {
					$this->mPropVals[$property->getDBkey()][$dv->getHash()] = $dv;
				} else {
					$this->mPropVals[$property->getDBkey()][] = $dv;
				}
			}
			
			unset( $this->mStubPropVals[$property->getDBkey()] );
		}
		
		if ( array_key_exists( $property->getDBkey(), $this->mPropVals ) ) {
			return $this->mPropVals[$property->getDBkey()];
		} else {
			return array();
		}
	}

	/**
	 * Generate a hash value to simplify the comparison of this data container with other
	 * containers. The hash uses PHP's md5 implementation, which is among the fastest hash
	 * algorithms that PHP offers.
	 * 
	 * @return string
	 */
	public function getHash() {
		$ctx = hash_init( 'md5' );
		
		if ( $this->mSubject !== null ) { // here and below, use "_#_" to separate values; really not much care needed here
			hash_update( $ctx, '_#_' . $this->mSubject->getHash() );
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
	 * 
	 * @note While called "visible" this check actually refers to the function
	 * SMWPropertyValue::isShown(). The name is kept for compatibility.
	 * 
	 * @return boolean
	 */
	public function hasVisibleProperties() {
		$this->unstubProperties();
		return $this->mHasVisibleProps;
	}

	/**
	 * Return true if there are any special properties that can
	 * be displayed.
	 * 
	 * @note While called "visible" this check actually refers to the function
	 * SMWPropertyValue::isShown(). The name is kept for compatibility.
	 * 
	 * @return boolean
	 */
	public function hasVisibleSpecialProperties() {
		$this->unstubProperties();
		return $this->mHasVisibleSpecs;
	}

	/**
	 * Store a value for a property identified by its title object. Duplicate
	 * value entries are usually ignored.
	 * 
	 * @note Attention: there is no check whether the type of the given datavalue agrees
	 * with what SMWDataValueFactory is producing (based on predefined property records and
	 * the current DB content). Always use SMWDataValueFactory to produce fitting values!
	 * 
	 * @param SMWPropertyValue $property
	 * @param SMWDataValue $value
	 */
	public function addPropertyObjectValue( SMWPropertyValue $property, SMWDataValue $value ) {
		if ( !$property->isValid() ) return; // nothing we can do
		
		if ( !array_key_exists( $property->getDBkey(), $this->mPropVals ) ) {
			$this->mPropVals[$property->getDBkey()] = array();
			$this->mProperties[$property->getDBkey()] = $property;
		}
		
		if ( $this->mNoDuplicates ) {
			$this->mPropVals[$property->getDBkey()][$value->getHash()] = $value;
		} else {
			$this->mPropVals[$property->getDBkey()][] = $value;
		}
		
		if ( !$property->isUserDefined() ) {
			if ( $property->isShown() ) {
				 $this->mHasVisibleSpecs = true;
				 $this->mHasVisibleProps = true;
			}
		} else {
			$this->mHasVisibleProps = true;
		}
	}

	/**
	 * Store a value for a given property identified by its text label (without
	 * namespace prefix). Duplicate value entries are usually ignored.
	 * 
	 * @param string $propertyName
	 * @param SMWDataValue $value
	 */
	public function addPropertyValue( $propertyName, SMWDataValue $value ) {
		$propertykey = smwfNormalTitleDBKey( $propertyName );
		
		if ( array_key_exists( $propertykey, $this->mProperties ) ) {
			$property = $this->mProperties[$propertykey];
		} else {
			if ( self::$mPropertyPrefix == false ) {
				global $wgContLang;
				self::$mPropertyPrefix = $wgContLang->getNsText( SMW_NS_PROPERTY ) . ':';
			} // explicitly use prefix to cope with things like [[Property:User:Stupid::somevalue]]
			
			$property = SMWPropertyValue::makeUserProperty( self::$mPropertyPrefix . $propertyName );
			
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
	public function addPropertyStubValue( $propertyKey, $valueKeys ) {
		// Catch built-in mProperties, since their internal key is not what is used as a key elsewhere in SMWSemanticData.
		if ( $propertyKey { 0 } == '_' ) {
			$property = SMWPropertyValue::makeProperty( $propertyKey );
			$propertyKey = $property->getDBkey();
			$this->unstubProperty( $propertyKey, $property );
		}
		
		$this->mStubPropVals[$propertyKey][] = $valueKeys;
	}

	/**
	 * Delete all data other than the subject.
	 */
	public function clear() {
		$this->mPropVals = array();
		$this->mProperties = array();
		$this->mStubPropVals = array();
		$this->mHasVisibleProps = false;
		$this->mHasVisibleSpecs = false;
	}

	/**
	 * Process all mProperties that have been added as stubs. Associated data may remain in stub form.
	 */
	protected function unstubProperties() {
		foreach ( $this->mStubPropVals as $pname => $values ) { // unstub property values only, the value lists are still kept as stubs
			$this->unstubProperty( $pname );
		}
	}

	/**
	 * Unstub a single property from the stub data array. If available, an existing object
	 * for that property might be provided, so we do not need to make a new one. It is not
	 * checked if the object matches the property name.
	 * 
	 * @param string $propertyName
	 * @param $propertyObject
	 */
	protected function unstubProperty( $propertyName, $propertyObject = null ) {
		if ( !array_key_exists( $propertyName, $this->mProperties ) ) {
			if ( $propertyObject === null ) {
				$propertyObject = SMWPropertyValue::makeProperty( $propertyName );
			}
			
			$this->mProperties[$propertyName] = $propertyObject;
			
			if ( !$propertyObject->isUserDefined() ) {
				if ( $propertyObject->isShown() ) {
					 $this->mHasVisibleSpecs = true;
					 $this->mHasVisibleProps = true;
				}
			} else {
				$this->mHasVisibleProps = true;
			}
		}
	}

}
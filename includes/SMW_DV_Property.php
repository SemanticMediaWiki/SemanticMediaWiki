<?php
/**
 * File holding class SMWPropertyValue.
 * 
 * @author Markus Krötzsch
 * 
 * @file
 * @ingroup SMWDataValues
 */

/**
 * Objects of this class represent properties in SMW.
 *
 * This class represents both normal (user-defined) properties and
 * predefined ("special") properties. Predefined properties may still
 * have a standard label (and associated wiki article) and they will
 * behave just like user-defined properties in most cases (e.g. when
 * asking for a printout text, a link to the according page is produced).
 * It is possible that predefined properties have no visible label at all,
 * if they are used only internally and never specified by or shown to
 * the user. Those will use their internal ID as DB key, and
 * empty texts for most printouts. All other proeprties use their
 * canonical DB key (even if they are predefined and have an id).
 * Functions are provided to check whether a property is visible or
 * user-defined, and to get the internal ID, if any.
 *
 * @note This datavalue is used only for representing properties and,
 * possibly objects/values, but never for subjects (pages as such). Hence
 * it does not provide a complete Title-like interface, or support for
 * things like sortkey.
 *
 * @author Markus Krötzsch
 * @ingroup SMWDataValues
 */
class SMWPropertyValue extends SMWDataValue {

	/** Array for assigning types to predefined properties. Each
	 * property is associated with an array with the following
	 * elements:
	 *
	 * * ID of datatype to be used for this property
	 *
	 * * Boolean, stating if this property is shown in Factbox, Browse, and similar interfaces;
	 *   (note that this is only relevant if the property can be displayed at all, i.e. has an
	 *   translated label in the given language; we still set invisible properties to false here)
	 */
	static private $mPropertyTypes;
	static private $mPropertyLabels;
	static private $mPropertyAliases;

	/// If the property is predefined, its internal key is stored here. Otherwise FALSE.
	protected $m_propertyid;
	/// If the property is associated with a wikipage, it is stored here. Otherwise NULL.
	protected $m_wikipage = null;
	/// Store if this property is an inverse
	protected $m_inv = false;

	/**
	 * Remember the type value of this property once it has been calculated.
	 * @var unknown_type
	 */
	private $mPropTypeValue;
	
	/**
	 * Remember the type id of this property once it has been calculated.
	 * @var unknown_type
	 */
	private $mPropTypeId;

	/**
	 * Static function for creating a new property object from a
	 * propertyname (string) as a user might enter it.
	 * @note The resulting property object might be invalid if
	 * the provided name is not allowed. An object is returned
	 * in any case.
	 * 
	 * @param string $propertyName
	 * 
	 * @return SMWPropertyValue
	 */
	static public function makeUserProperty( $propertyName ) {
		$property = new SMWPropertyValue( '__pro' );
		$property->setUserValue( $propertyName );
		return $property;
	}

	/**
	 * Static function for creating a new property object from a
	 * property identifier (string) as it might be used internally.
	 * This might be the DB-key version of some property title
	 * text or the id of a predefined property (such as '_TYPE').
	 * @note The resulting property object might be invalid if
	 * the provided name is not allowed. An object is returned
	 * in any case.
	 */
	static public function makeProperty( $propertyid ) {
		$property = new SMWPropertyValue( '__pro' );
		$property->setDBkeys( array( $propertyid ) );
		return $property;
	}

	/**
	 * We use the internal wikipage object to store some of this objects data.
	 * Clone it to make sure that data can be modified independelty from the
	 * original object's content.
	 */
	public function __clone() {
		if ( $this->m_wikipage !== null ) $this->m_wikipage = clone $this->m_wikipage;
	}

	/**
	 * Extended parsing function to first check whether value refers to pre-defined
	 * property, resolve aliases, and set internal property id accordingly.
	 * @todo Accept/enforce property namespace.
	 */
	protected function parseUserValue( $value ) {
		$this->mPropTypeValue = null;
		$this->mPropTypeId = null;
		$this->m_inv = false;
		if ( $this->m_caption === false ) { // always use this as caption
			$this->m_caption = $value;
		}
		$value = smwfNormalTitleText( ltrim( rtrim( $value, ' ]' ), ' [' ) ); // slightly normalise label
		if ( ( $value !== '' ) && ( $value { 0 } == '-' ) ) { // check if this property refers to an inverse
			$value = substr( $value, 1 );
			$this->m_inv = true;
		}
		$this->m_propertyid = SMWPropertyValue::findPropertyID( $value );
		if ( $this->m_propertyid !== false ) {
			$value = SMWPropertyValue::findPropertyLabel( $this->m_propertyid );
		}
		if ( $value !== false ) {
			$this->m_wikipage = SMWDataValueFactory::newTypeIDValue( '_wpp' );
			$this->m_wikipage->setUserValue( $value, $this->m_caption );
			$this->addError( $this->m_wikipage->getErrors() );
		} else { // should rarely happen ($value is only changed if the input $value really was a label for a predefined prop)
			$this->m_wikipage = null;
		}
	}

	/**
	 * Extended parsing function to first check whether value is the id of a
	 * pre-defined property, to resolve property names and aliases, and to set
	 * internal property id accordingly.
	 */
	protected function parseDBkeys( $args ) {
		$this->mPropTypeValue = null;
		$this->mPropTypeId = null;
		$this->m_inv = false;
		if ( $args[0] { 0 } == '-' ) { // check if this property refers to an inverse
			$args[0] = substr( $args[0], 1 );
			$this->m_inv = true;
		}
		SMWPropertyValue::initProperties();
		if ( $args[0] { 0 } == '_' ) { // internal id, use as is (and hope it is still known)
			$this->m_propertyid = $args[0];
		} else { // possibly name of special property
			$this->m_propertyid = SMWPropertyValue::findPropertyID( str_replace( '_', ' ', $args[0] ) );
		}
		$label = ( $this->m_propertyid !== false ) ? SMWPropertyValue::findPropertyLabel( $this->m_propertyid ):$args[0];
		if ( $label != '' ) {
			$this->m_wikipage = SMWDataValueFactory::newTypeIDValue( '_wpp' );
			$this->m_wikipage->setDBkeys( array( str_replace( ' ', '_', $label ), SMW_NS_PROPERTY, '', '' ) );
			$this->m_wikipage->setOutputFormat( $this->m_outformat );
			$this->m_caption = $label;
			$this->addError( $this->m_wikipage->getErrors() ); // NOTE: this unstubs the wikipage, should we rather ignore errors here to prevent this?
		} else { // predefined property without label
			$this->m_wikipage = null;
			$this->m_caption = $this->m_propertyid;
		}
	}

	public function setCaption( $caption ) {
		parent::setCaption( $caption );
		if ( $this->m_wikipage instanceof SMWDataValue ) { // pass caption to embedded datavalue (used for printout)
			$this->m_wikipage->setCaption( $caption );
		}
	}


	public function setInverse( $isinverse ) {
		$this->unstub(); // make sure later unstubbing does not overwrite this
		return $this->m_inv = ( $isinverse == true );
	}

	/**
	 * Return TRUE if this is a usual wiki property that is defined by a wiki page, as
	 * opposed to a property that is pre-defined in the wiki.
	 */
	public function isUserDefined() {
		$this->unstub();
		return ( $this->m_propertyid == '' );
	}

	/**
	 * Return TRUE if this is a property that can be displayed, and not a pre-defined
	 * property that is used only internally and does not even have a user-readable name.
	 * @note Every user defined property is necessarily visible.
	 */
	public function isVisible() {
		$this->unstub();
		return ( $this->m_wikipage !== null );
	}

	/**
	 * Specifies whether values of this property should be shown in typical
	 * browsing interfaces. A property may wish to prevent this if either
	 * (1) its information is really dull, e.g. being a mere copy of
	 * information that is obvious from other things that are shown, or (2) the
	 * property is set in a hook after parsing, so that it is not reliably
	 * available when Factboxes are displayed. If a property is internal so it
	 * should never be observed by users, then it is better to just not
	 * associate any translated label with it, so it never appears anywhere.
	 */
	public function isShown() {
		$this->unstub();
		return ( ( $this->m_propertyid == '' ) ||
		        ( array_key_exists( $this->m_propertyid, SMWPropertyvalue::$mPropertyTypes ) &&
		         SMWPropertyvalue::$mPropertyTypes[$this->m_propertyid][1] ) );
	}

	/**
	 * Return TRUE if this property is an inverse.
	 */
	public function isInverse() {
		$this->unstub();
		return $this->m_inv;
	}

	public function setOutputFormat( $formatstring ) {
		$this->m_outformat = $formatstring;
		if ( $this->m_wikipage !== null ) { // do not unstub if not needed
			$this->m_wikipage->setOutputFormat( $formatstring );
		}
	}

	public function getShortWikiText( $linked = null ) {
		return $this->isVisible() ? $this->highlightText( $this->m_wikipage->getShortWikiText( $linked ) ):'';
	}

	public function getShortHTMLText( $linker = null ) {
		return $this->isVisible() ? $this->highlightText( $this->m_wikipage->getShortHTMLText( $linker ) ):'';
	}

	public function getLongWikiText( $linked = null ) {
		return $this->isVisible() ? $this->highlightText( $this->m_wikipage->getLongWikiText( $linked ) ):'';
	}

	public function getLongHTMLText( $linker = null ) {
		return $this->isVisible() ? $this->highlightText( $this->m_wikipage->getLongHTMLText( $linker ) ):'';
	}

	/**
	 * Return internal property id or page DBkey, either of which is sufficient for storing property references.
	 */
	public function getDBkeys() {
 		$this->unstub();
 		return $this->isVisible() ? array( $this->m_wikipage->getDBkey() ):array( $this->m_propertyid );
	}

	public function getSignature() {
		return 't';
	}

	public function getValueIndex() {
		return 0;
	}

	public function getLabelIndex() {
		return 0;
	}

	public function getWikiValue() {
		return $this->isVisible() ? ( ( $this->isInverse() ? '-':'' ) . $this->m_wikipage->getWikiValue() ):'';
	}

	/**
	 * If this property is associated with a wiki page, return an SMWWikiPageValue for
	 * that page. Otherwise return NULL.
	 */
	public function getWikiPageValue() {
		$this->unstub();
		return $this->m_wikipage;
	}

	/**
	 * If this property was not user defined, return the internal ID string referring to
	 * that property. Otherwise return FALSE;
	 */
	public function getPropertyID() {
		$this->unstub();
		return $this->m_propertyid;
	}

	/**
	 * Return an SMWTypesValue object representing the datatype of this property.
	 */
	public function getTypesValue() {
		global $smwgPDefaultType;
		if ( $this->mPropTypeValue !== null ) return $this->mPropTypeValue;
		if ( !$this->isValid() ) { // errors in property, return invalid types value with same errors
			$result = SMWDataValueFactory::newTypeIDValue( '__typ' );
			$result->setDBkeys( array( '__err' ) );
			$result->addError( $this->getErrors() );
		} elseif ( $this->isUserDefined() ) { // normal property
			$typearray = smwfGetStore()->getPropertyValues( $this->getWikiPageValue(), SMWPropertyValue::makeProperty( '_TYPE' ) );
			if ( count( $typearray ) == 1 ) { // unique type given
				$result = current( $typearray );
			} elseif ( count( $typearray ) == 0 ) { // no type given
				$result = SMWDataValueFactory::newTypeIDValue( '__typ' );
				$result->setDBkeys( array( $smwgPDefaultType ) );
			} else { // many types given, error
				smwfLoadExtensionMessages( 'SemanticMediaWiki' );
				$result = SMWDataValueFactory::newTypeIDValue( '__typ' );
				$result->setDBkeys( array( '__err' ) );
				$result->addError( wfMsgForContent( 'smw_manytypes' ) );
			}
		} else { // pre-defined property
			$result = SMWDataValueFactory::newTypeIDValue( '__typ' );
			if ( array_key_exists( $this->m_propertyid, SMWPropertyValue::$mPropertyTypes ) ) {
				$result->setDBkeys( array( SMWPropertyValue::$mPropertyTypes[$this->m_propertyid][0] ) );
			} else { // unknown type; it may still be that the property is "type-polymorphic" (like _1, _2, ... for Records)
				$result->setDBkeys( array( '__err' ) ); // use "__err" to make sure that it gets noticed if this information is really used to create values
			}
		}
		$this->mPropTypeValue = $result;
		return $result;
	}

	/**
	 * Quickly get the type id of some property without necessarily making
	 * another datavalue. Note that this is not the same as getTypeID(), which
	 * returns the id of this property datavalue.
	 */
	public function getPropertyTypeID() {
		if ( $this->mPropTypeId === null ) {
			$type = $this->getTypesValue();
			if ( $type instanceof SMWTypesValue ) {
				$this->mPropTypeId = $type->getDBkey();
			} else {
				$this->mPropTypeId = '__err';
			}
		}
		return $this->mPropTypeId;
	}

	/**
	 * Return a DB-key-like string: for visible properties, it is the actual DB key,
	 * for internal (invisible) properties, it is the property ID. The value agrees
	 * with the first component of getDBkeys() and it can be used in its place.
	 */
	public function getDBkey() {
		return $this->isVisible() ? $this->m_wikipage->getDBkey():$this->m_propertyid;
	}

	public function getText() {
		return $this->isVisible() ? $this->m_wikipage->getWikiValue():'';
	}

	/**
	 * Create special highlighting for hinting at special properties.
	 */
	protected function highlightText( $text ) {
		if ( $this->isUserDefined() ) {
			return $text;
		} else {
			SMWOutputs::requireHeadItem( SMW_HEADER_TOOLTIP );
			smwfLoadExtensionMessages( 'SemanticMediaWiki' );
			return '<span class="smwttinline"><span class="smwbuiltin">' . $text .
			'</span><span class="smwttcontent">' . wfMsgForContent( 'smw_isspecprop' ) . '</span></span>';
		}
	}

	/**
	 * Find and return the id for the pre-defined property of the given local label.
	 * If the label does not belong to a pre-defined property, return false.
	 * The given label should be slightly normalised, i.e. as returned by Title
	 * or smwfNormalTitleText().
	 *
	 * This function is protected. The public way of getting this data is to simply
	 * create a new property object and to retrieve its ID (if any).
	 */
	static protected function findPropertyID( $label, $useAlias = true ) {
		SMWPropertyValue::initProperties();
		$id = array_search( $label, SMWPropertyValue::$mPropertyLabels );
		if ( $id !== false ) {
			return $id;
		} elseif ( ( $useAlias ) && ( array_key_exists( $label, SMWPropertyValue::$mPropertyAliases ) ) ) {
			return SMWPropertyValue::$mPropertyAliases[$label];
		} else {
			return false;
		}
	}

	/**
	 * Get the translated user label for a given internal property ID.
	 * Returns false for properties without a translation (these are usually the
	 * internal ones generated by SMW but not shown to the user).
	 */
	static protected function findPropertyLabel( $id ) {
		SMWPropertyValue::initProperties();
		if ( array_key_exists( $id, SMWPropertyValue::$mPropertyLabels ) ) {
			return SMWPropertyValue::$mPropertyLabels[$id];
		} else { // incomplete translation (language bug) or deliberately invisible property
			return false;
		}
	}

	/**
	 * Set up predefined properties, including their label, aliases, and typing information.
	 */
	static protected function initProperties() {
		if ( is_array( SMWPropertyValue::$mPropertyTypes ) ) {
			return; // init happened before
		}

		global $smwgContLang, $smwgUseCategoryHierarchy;
		SMWPropertyValue::$mPropertyLabels = $smwgContLang->getPropertyLabels();
		SMWPropertyValue::$mPropertyAliases = $smwgContLang->getPropertyAliases();
		// Setup built-in predefined properties.
		// NOTE: all ids must start with underscores, where two underscores informally indicate
		// truly internal (non user-accessible properties). All others should also get a
		// translation in the language files, or they won't be available for users.
		SMWPropertyValue::$mPropertyTypes = array(
				'_TYPE'  =>  array( '__typ', true ),
				'_URI'   =>  array( '__spu', true ),
				'_INST'  =>  array( '__sin', false ),
				'_UNIT'  =>  array( '__sps', true ),
				'_IMPO'  =>  array( '__imp', true ),
				'_CONV'  =>  array( '__sps', true ),
				'_SERV'  =>  array( '__sps', true ),
				'_PVAL'  =>  array( '__sps', true ),
				'_REDI'  =>  array( '__red', true ),
				'_SUBP'  =>  array( '__sup', true ),
				'_SUBC'  =>  array( '__suc', !$smwgUseCategoryHierarchy ),
				'_CONC'  =>  array( '__con', false ),
				'_MDAT'  =>  array( '_dat', false ),
				'_ERRP'  =>  array( '_wpp', false ),
				'_LIST'  =>  array( '__tls', true ),
				// "virtual" properties for encoding lists in n-ary datatypes (their type must never be used, hence use __err)
// 				'_1'     =>  array('__err',false),
// 				'_2'     =>  array('__err',false),
// 				'_3'     =>  array('__err',false),
// 				'_4'     =>  array('__err',false),
// 				'_5'     =>  array('__err',false),
			);
		wfRunHooks( 'smwInitProperties' );
	}

	/**
	 * A function for registering/overwriting predefined properties for SMW. Should be called from
	 * within the hook 'smwInitProperties'. Ids should start with three underscores "___" to avoid
	 * current and future confusion with SMW built-ins.
	 */
	static public function registerProperty( $id, $typeid, $label = false, $show = false ) {
		SMWPropertyValue::$mPropertyTypes[$id] = array( $typeid, $show );
		if ( $label != false ) {
			SMWPropertyValue::$mPropertyLabels[$id] = $label;
		}
	}

	/**
	 * Add a new alias label to an existing datatype id. Note that every ID should have a primary
	 * label, either provided by SMW or registered with registerDatatype. This function should be
	 * called from within the hook 'smwInitDatatypes'.
	 */
	static public function registerPropertyAlias( $id, $label ) {
		SMWPropertyValue::$mPropertyAliases[$label] = $id;
	}

}

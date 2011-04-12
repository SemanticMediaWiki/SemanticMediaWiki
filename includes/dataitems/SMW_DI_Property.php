<?php
/**
 * @file
 * @ingroup SMWDataItems
 */

/**
 * This class implements Property data items.
 *
 * The static part of this class also manages global registrations of
 * predefined (built-in) properties, and maintains an association of
 * property IDs, localized labels, and aliases.
 *
 * @author Markus Krötzsch
 * @ingroup SMWDataItems
 */
class SMWDIProperty extends SMWDataItem {

	/**
	 * Array for assigning types to predefined properties. Each
	 * property is associated with an array with the following
	 * elements:
	 *
	 * * ID of datatype to be used for this property
	 *
	 * * Boolean, stating if this property is shown in Factbox, Browse, and
	 *   similar interfaces; (note that this is only relevant if the
	 *   property can be displayed at all, i.e. has a translated label in
	 *   the wiki language; invisible properties are never shown).
	 *
	 * @var array
	 */
	static protected $m_prop_types;
	/**
	 * Array with entries "property id" => "property label"
	 * @var array
	 */
	static protected $m_prop_labels;
	/**
	 * Array with entries "property alias" => "property id"
	 * @var array
	 */
	static protected $m_prop_aliases;

	/**
	 * Either an internal SMW property key (starting with "_") or the DB
	 * key of a property page in the wiki.
	 * @var string
	 */
	protected $m_key;
	/**
	 * Whether to take the inverse of this property or not.
	 * @var boolean
	 */
	protected $m_inverse;
	/**
	 * Cache for property type ID.
	 * @var string
	 */
	protected $m_proptypeid;

	/**
	 * Initialise a property. This constructor checks that keys of
	 * predefined properties do really exist (in the current configuration
	 * of the wiki). No check is performed to see if a user label is in
	 * fact the label or alias of a predefined property. If this should be
	 * done, the function SMWDIProperty::newFromUserLabel() can be used.
	 *
	 * @param $key string key for the property (internal SMW key or wikipage DB key)
	 * @param $inverse boolean states if the inverse of the property is constructed
	 * @param $typeid string SMW type id
	 */
	public function __construct( $key, $inverse = false, $typeid = '__pro' ) {
		if ( ( $key == '' ) || ( $key{0} == '-' ) ) {
			throw new SMWDataItemException( "Illegal property key \"$key\"." );
		}
		if ( $key{0} == '_' ) {
			SMWDIProperty::initPropertyRegistration();
			if ( !array_key_exists( $key, SMWDIProperty::$m_prop_types ) ) {
				throw new SMWDataItemException( "There is no predefined property with \"$key\"." );
			}
		}
		parent::__construct( $typeid );
		$this->m_key     = $key;
		$this->m_inverse = ( $inverse == true );
	}

	public function getDIType() {
		return SMWDataItem::TYPE_PROPERTY;
	}

	public function getKey() {
		return $this->m_key;
	}

	public function isInverse() {
		return $this->m_inverse;
	}

	public function getSortKey() {
		return $this->m_key;
	}

	/**
	 * Specifies whether values of this property should be shown in the
	 * Factbox. A property may wish to prevent this if either
	 * (1) its information is really dull, e.g. being a mere copy of
	 * information that is obvious from other things that are shown, or
	 * (2) the property is set in a hook after parsing, so that it is not
	 * reliably available when Factboxes are displayed. If a property is
	 * internal so it should never be observed by users, then it is better
	 * to just not associate any translated label with it, so it never
	 * appears anywhere.
	 * 
	 * Examples of properties that are not shown include Modificaiton date
	 * (not available in time), and Has improper value for (errors are
	 * shown directly on the page anyway).
	 */
	public function isShown() {
		return ( ( $this->isUserDefined() ) ||
		         ( array_key_exists( $this->m_key, SMWDIProperty::$m_prop_types ) &&
		           SMWDIProperty::$m_prop_types[$this->m_key][1] ) );
	}

	/**
	 * Return true if this is a usual wiki property that is defined by a
	 * wiki page, and not a property that is pre-defined in the wiki.
	 * @return boolean
	 */
	public function isUserDefined() {
		return $this->m_key{0} != '_';
	}

	/**
	 * Find a user-readable label for this property, or return '' if it is
	 * a predefined property that has no label.
	 * @return string
	 */
	public function getLabel() {
		if ( $this->isUserDefined() ) {
			return str_replace( '_', ' ', $this->m_key );
		} else {
			SMWDIProperty::initPropertyRegistration();
			if ( array_key_exists( $this->m_key, SMWDIProperty::$m_prop_labels ) ) {
				return SMWDIProperty::$m_prop_labels[$this->m_key];
			} else {
				return '';
			}
		}
	}

	/**
	 * Get an object of type SMWDIWikiPage that represents the page which
	 * relates to this property, or null if no such page exists. The latter
	 * can happen for special properties without user-readable label, and
	 * for inverse properties.
	 */
	public function getDiWikiPage() {
		if ( $this->m_inverse ) return null;
		if ( $this->isUserDefined() ) {
			$dbkey = $this->m_key;
		} else {
			$dbkey = str_replace( ' ', '_', $this->getLabel() );
		}
		try {
			return new SMWDIWikiPage( $dbkey, SMW_NS_PROPERTY, '', '_wpp' );
		} catch ( SMWDataItemException $e ) {
			return null;
		}
	}

	/**
	 * Get the type ID of a predefined property, or '' if the property
	 * is not predefined.
	 * The function is guaranteed to return a type ID if isUserDefined()
	 * returns false.
	 * @return string type ID
	 */
	public function getPredefinedPropertyTypeID() {
		if ( array_key_exists( $this->m_key, SMWDIProperty::$m_prop_types ) ) {
			return SMWDIProperty::$m_prop_types[$this->m_key][0];
		} else {
			return '';
		}
	}

	/**
	 * Find the property's type ID, either by looking up its predefined ID
	 * (if any) or by retrieving the relevant information from the store.
	 * If no type is stored for a user defined property, the global default
	 * type will be used.
	 *
	 * @return string type ID
	 */
	public function findPropertyTypeID() {
		global $smwgPDefaultType;
		if ( !isset( $this->m_proptypeid ) ) {
			if ( $this->isUserDefined() ) { // normal property
				$diWikiPage = new SMWDIWikiPage( $this->getKey(), SMW_NS_PROPERTY, '' );
				$typearray = smwfGetStore()->getPropertyValues( $diWikiPage, new SMWDIProperty( '_TYPE' ) );
				if ( count( $typearray ) >= 1 ) { // some types given, pick one (hopefully unique)
					$typeString = reset( $typearray );
					$this->m_proptypeid = ( $typeString instanceOf SMWDIWikiPage ) ? 
					                        SMWDataValueFactory::findTypeID( $typeString->getDBKey() ) : '__err';
				} elseif ( count( $typearray ) == 0 ) { // no type given
					$this->m_proptypeid = $smwgPDefaultType;
				}
			} else { // pre-defined property
				$this->m_proptypeid = $this->getPredefinedPropertyTypeID();
			}
		}
		return $this->m_proptypeid;
	}


	public function getSerialization() {
		return ( $this->m_inverse ? '-' : '' ) . $this->m_key ;
	}

	/**
	 * Create a data item from the provided serialization string and type
	 * ID.
	 * @return SMWDIProperty
	 */
	public static function doUnserialize( $serialization, $typeid ) {
		$inverse = false;
		if ( $serialization{0} == '-' ) {
			$serialization = substr( $serialization, 1 );
			$inverse = true;
		}
		return new SMWDIProperty( $serialization, $inverse, $typeid );
	}

	/**
	 * Construct a property from a user-supplied label. The main difference
	 * to the normal constructor of SMWDIProperty is that it is checked
	 * whether the label refers to a known predefined property.
	 * Note that this function only gives access to the registry data that
	 * SMWDIProperty stores, but does not do further parsing of user input.
	 * For example, '-' as first character is not interpreted for inverting
	 * a property. Likewise, no normalization of title strings is done. To
	 * process wiki input, SMWPropertyValue should be used.
	 *
	 * @param $label string label for the property
	 * @param $inverse boolean states if the inverse of the property is constructed
	 * @param $typeid string SMW type id
	 * @return SMWDIProperty object
	 */
	public static function newFromUserLabel( $label, $inverse = false, $typeid = '__pro' ) {
		$id = SMWDIProperty::findPropertyID( $label );
		if ( $id === false ) {
			return new SMWDIProperty( str_replace( ' ', '_', $label ), $inverse, $typeid );
		} else {
			return new SMWDIProperty( $id, $inverse, $typeid );
		}
	}

	/**
	 * Find and return the ID for the pre-defined property of the given
	 * local label. If the label does not belong to a pre-defined property,
	 * return false.
	 *
	 * This function is protected. The public way of getting this data is
	 * to simply create a new property object and to get its ID (if any).
	 * @param $label string normalized property label
	 * @param $useAlias boolean determining whether to check if the label is an alias
	 * @return mixed string property ID or false
	 */
	static protected function findPropertyID( $label, $useAlias = true ) {
		SMWDIProperty::initPropertyRegistration();
		$id = array_search( $label, SMWDIProperty::$m_prop_labels );
		if ( $id !== false ) {
			return $id;
		} elseif ( ( $useAlias ) && ( array_key_exists( $label, SMWDIProperty::$m_prop_aliases ) ) ) {
			return SMWDIProperty::$m_prop_aliases[$label];
		} else {
			return false;
		}
	}

	/**
	 * Get the translated user label for a given internal property ID.
	 * Returns false for properties without a translation (these are
	 * usually internal, generated by SMW but not shown to the user).
	 */
	static protected function findPropertyLabel( $id ) {
		SMWDIProperty::initPropertyRegistration();
		if ( array_key_exists( $id, SMWDIProperty::$m_prop_labels ) ) {
			return SMWDIProperty::$m_prop_labels[$id];
		} else { // incomplete translation (language bug) or deliberately invisible property
			return false;
		}
	}

	/**
	 * Set up predefined properties, including their label, aliases, and
	 * typing information.
	 */
	static protected function initPropertyRegistration() {
		if ( is_array( SMWDIProperty::$m_prop_types ) ) {
			return; // init happened before
		}

		global $smwgContLang, $smwgUseCategoryHierarchy;
		SMWDIProperty::$m_prop_labels  = $smwgContLang->getPropertyLabels();
		SMWDIProperty::$m_prop_aliases = $smwgContLang->getPropertyAliases();
		// Setup built-in predefined properties.
		// NOTE: all ids must start with underscores, where two underscores informally indicate
		// truly internal (non user-accessible properties). All others should also get a
		// translation in the language files, or they won't be available for users.
		SMWDIProperty::$m_prop_types = array(
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
			);
		wfRunHooks( 'smwInitProperties' );
	}

	/**
	 * A method for registering/overwriting predefined properties for SMW.
	 * It should be called from within the hook 'smwInitProperties' only.
	 * IDs should start with three underscores "___" to avoid current and
	 * future confusion with SMW built-ins.
	 * 
	 * @note See SMWDIProperty::isShown() for information about $show.
	 */
	static public function registerProperty( $id, $typeid, $label = false, $show = false ) {
		SMWDIProperty::$m_prop_types[$id] = array( $typeid, $show );
		if ( $label != false ) {
			SMWDIProperty::$m_prop_labels[$id] = $label;
		}
	}

	/**
	 * Add a new alias label to an existing property ID. Note that every ID
	 * should have a primary label, either provided by SMW or registered
	 * with SMWDIProperty::registerProperty(). This function should be
	 * called from within the hook 'smwInitDatatypes' only.
	 */
	static public function registerPropertyAlias( $id, $label ) {
		SMWDIProperty::$m_prop_aliases[$label] = $id;
	}

}
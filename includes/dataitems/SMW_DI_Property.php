<?php

namespace SMW;

use InvalidArgumentException;
use RuntimeException;
use SMW\InvalidPredefinedPropertyException;
use SMW\InvalidPropertyException;
use SMWDataItem;
use SMWDIUri;
use SMWDIWikiPage;
use SMWLanguage;

/**
 * This class implements Property data items.
 *
 * The static part of this class also manages global registrations of
 * predefined (built-in) properties, and maintains an association of
 * property IDs, localized labels, and aliases.
 *
 * @since 1.6
 *
 * @author Markus Krötzsch
 * @ingroup SMWDataItems
 */
class DIProperty extends SMWDataItem {

	// Property subobject data item ID
	const TYPE_SUBOBJECT  = '_SOBJ';
	// Property improper value data item ID
	const TYPE_ERROR      = '_ERRP';
	// Property instance of a category
	const TYPE_CATEGORY = '_INST';
	// Property "subcategory of"
	const TYPE_SUBCATEGORY = '_SUBC';
	// Property sort key of a page
	const TYPE_SORTKEY = '_SKEY';
	// Property modification date
	const TYPE_MODIFICATION_DATE = '_MDAT';
	// Property "creation date"
	const TYPE_CREATION_DATE = '_CDAT';
	// Property "last editor is"
	const TYPE_LAST_EDITOR = '_LEDT';
	// Property "is a new page"
	const TYPE_NEW_PAGE = '_NEWP';
	// Property "has type"
	const TYPE_HAS_TYPE = '_TYPE';
	// Property "corresponds to"
	const TYPE_CONVERSION = '_CONV';
	// Property "has query"
	const TYPE_ASKQUERY = '_ASK';

	// Property "has media type"
	const TYPE_MEDIA = '_MEDIA';
	// Property "has mime type"
	const TYPE_MIME = '_MIME';

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
	static private $m_prop_types;

	/**
	 * Array with entries "property id" => "property label"
	 * @var array
	 */
	static private $m_prop_labels;

	/**
	 * Array with entries "property alias" => "property id"
	 * @var array
	 */
	static private $m_prop_aliases;

	/**
	 * Either an internal SMW property key (starting with "_") or the DB
	 * key of a property page in the wiki.
	 * @var string
	 */
	private $m_key;

	/**
	 * Whether to take the inverse of this property or not.
	 * @var boolean
	 */
	private $m_inverse;

	/**
	 * Cache for property type ID.
	 * @var string
	 */
	private $m_proptypeid;

	/**
	 * Initialise a property. This constructor checks that keys of
	 * predefined properties do really exist (in the current configuration
	 * of the wiki). No check is performed to see if a user label is in
	 * fact the label or alias of a predefined property. If this should be
	 * done, the function self::newFromUserLabel() can be used.
	 *
	 * @param $key string key for the property (internal SMW key or wikipage DB key)
	 * @param $inverse boolean states if the inverse of the property is constructed
	 */
	public function __construct( $key, $inverse = false ) {
		if ( ( $key === '' ) || ( $key{0} == '-' ) ) {
			throw new InvalidPropertyException( "Illegal property key \"$key\"." );
		}

		if ( $key{0} == '_' ) {
			self::initPropertyRegistration();
			if ( !array_key_exists( $key, self::$m_prop_types ) ) {
				throw new InvalidPredefinedPropertyException( "There is no predefined property with \"$key\"." );
			}
		}

		$this->m_key = $key;
		$this->m_inverse = $inverse;
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
	 * Examples of properties that are not shown include Modification date
	 * (not available in time), and Has improper value for (errors are
	 * shown directly on the page anyway).
	 *
	 * @return boolean
	 */
	public function isShown() {
		return ( ( $this->isUserDefined() ) ||
		         ( array_key_exists( $this->m_key, self::$m_prop_types ) &&
		           self::$m_prop_types[$this->m_key][1] ) );
	}

	/**
	 * Return true if this is a usual wiki property that is defined by a
	 * wiki page, and not a property that is pre-defined in the wiki.
	 *
	 * @return boolean
	 */
	public function isUserDefined() {
		return $this->m_key{0} != '_';
	}

	/**
	 * Find a user-readable label for this property, or return '' if it is
	 * a predefined property that has no label. For inverse properties, the
	 * label starts with a "-".
	 *
	 * @return string
	 */
	public function getLabel() {
		$prefix = $this->m_inverse ? '-' : '';
		if ( $this->isUserDefined() ) {
			return $prefix . str_replace( '_', ' ', $this->m_key );
		} else {
			return self::findPropertyLabel( $this->m_key );
		}
	}

	/**
	 * Get an object of type SMWDIWikiPage that represents the page which
	 * relates to this property, or null if no such page exists. The latter
	 * can happen for special properties without user-readable label, and
	 * for inverse properties.
	 *
	 * It is possible to construct subobjects of the property's wikipage by
	 * providing an optional subobject name.
	 *
	 * @param string $subobjectName
	 * @return SMWDIWikiPage|null
	 */
	public function getDiWikiPage( $subobjectName = '' ) {
		if ( $this->m_inverse ) {
			return null;
		}

		if ( $this->isUserDefined() ) {
			$dbkey = $this->m_key;
		} else {
			$dbkey = str_replace( ' ', '_', $this->getLabel() );
		}

		try {
			return new SMWDIWikiPage( $dbkey, SMW_NS_PROPERTY, '', $subobjectName );
		} catch ( DataItemException $e ) {
			return null;
		}
	}

	/**
	 * @since  2.0
	 *
	 * @return self
	 * @throws RuntimeException
	 * @throws InvalidArgumentException
	 */
	public function setPropertyTypeId( $propertyTypeId ) {

		if ( !DataTypeRegistry::getInstance()->isKnownTypeId( $propertyTypeId ) ) {
			throw new RuntimeException( "{$propertyTypeId} is an unknown type id" );
		}

		if ( $this->isUserDefined() && $this->m_proptypeid === null ) {
			$this->m_proptypeid = $propertyTypeId;
			return $this;
		}

		if ( !$this->isUserDefined() && $propertyTypeId === self::getPredefinedPropertyTypeId( $this->m_key ) ) {
			$this->m_proptypeid = $propertyTypeId;
			return $this;
		}

		throw new InvalidArgumentException( 'Property type can not be altered for a predefined object' );
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
				$typearray = \SMW\StoreFactory::getStore()->getPropertyValues( $diWikiPage, new self( '_TYPE' ) );

				if ( count( $typearray ) >= 1 ) { // some types given, pick one (hopefully unique)
					$typeDataItem = reset( $typearray );

					if ( $typeDataItem instanceof SMWDIUri ) {
						$this->m_proptypeid = $typeDataItem->getFragment();
					} else {
						$this->m_proptypeid = $smwgPDefaultType;
						// This is important. If a page has an invalid assignment to "has type", no
						// value will be stored, so the elseif case below occurs. But if the value
						// is retrieved within the same run, then the error value for "has type" is
						// cached and thus this case occurs. This is why it is important to tolerate
						// this case -- it is not necessarily a DB error.
					}
				} elseif ( count( $typearray ) == 0 ) { // no type given
					$this->m_proptypeid = $smwgPDefaultType;
				}
			} else { // pre-defined property
				$this->m_proptypeid = self::getPredefinedPropertyTypeId( $this->m_key );
			}
		}

		return $this->m_proptypeid;
	}


	public function getSerialization() {
		return ( $this->m_inverse ? '-' : '' ) . $this->m_key;
	}

	/**
	 * Create a data item from the provided serialization string and type
	 * ID.
	 *
	 * @param string $serialization
	 *
	 * @return DIProperty
	 */
	public static function doUnserialize( $serialization ) {
		$inverse = false;

		if ( $serialization{0} == '-' ) {
			$serialization = substr( $serialization, 1 );
			$inverse = true;
		}

		return new self( $serialization, $inverse );
	}

	/**
	 * Construct a property from a user-supplied label. The main difference
	 * to the normal constructor of DIProperty is that it is checked
	 * whether the label refers to a known predefined property.
	 * Note that this function only gives access to the registry data that
	 * DIProperty stores, but does not do further parsing of user input.
	 * For example, '-' as first character is not interpreted for inverting
	 * a property. Likewise, no normalization of title strings is done. To
	 * process wiki input, SMWPropertyValue should be used.
	 *
	 * @param $label string label for the property
	 * @param $inverse boolean states if the inverse of the property is constructed
	 *
	 * @return DIProperty object
	 */
	public static function newFromUserLabel( $label, $inverse = false ) {
		$id = self::findPropertyID( $label );

		if ( $id === false ) {
			return new self( str_replace( ' ', '_', $label ), $inverse );
		} else {
			return new self( $id, $inverse );
		}
	}

	/**
	 * Find and return the ID for the pre-defined property of the given
	 * local label. If the label does not belong to a pre-defined property,
	 * return false.
	 *
	 * @param string $label normalized property label
	 * @param boolean $useAlias determining whether to check if the label is an alias
	 *
	 * @return mixed string property ID or false
	 */
	public static function findPropertyID( $label, $useAlias = true ) {
		self::initPropertyRegistration();
		$id = array_search( $label, self::$m_prop_labels );

		if ( $id !== false ) {
			return $id;
		} elseif ( $useAlias && array_key_exists( $label, self::$m_prop_aliases ) ) {
			return self::$m_prop_aliases[$label];
		} else {
			return false;
		}
	}

	/**
	 * Get the type ID of a predefined property, or '' if the property
	 * is not predefined.
	 * The function is guaranteed to return a type ID for keys of
	 * properties where isUserDefined() returns false.
	 *
	 * @param $key string key of the property
	 *
	 * @return string type ID
	 */
	public static function getPredefinedPropertyTypeId( $key ) {
		self::initPropertyRegistration();
		if ( array_key_exists( $key, self::$m_prop_types ) ) {
			return self::$m_prop_types[$key][0];
		} else {
			return '';
		}
	}

	/**
	 * Get the translated user label for a given internal property ID.
	 * Returns empty string for properties without a translation (these are
	 * usually internal, generated by SMW but not shown to the user).
	 *
	 * @since 1.8 public
	 * @param string $id
	 * @return string
	 */
	static public function findPropertyLabel( $id ) {
		self::initPropertyRegistration();
		if ( array_key_exists( $id, self::$m_prop_labels ) ) {
			return self::$m_prop_labels[$id];
		} else { // incomplete translation (language bug) or deliberately invisible property
			return '';
		}
	}

	/**
	 * Set up predefined properties, including their label, aliases, and
	 * typing information.
	 */
	static private function initPropertyRegistration() {
		if ( is_array( self::$m_prop_types ) ) {
			return; // init happened before
		}

		/**
		 * @var SMWLanguage $smwgContLang
		 */
		global $smwgContLang, $smwgUseCategoryHierarchy;

		$datatypeLabels = $smwgContLang->getDatatypeLabels();

		self::$m_prop_labels  = $smwgContLang->getPropertyLabels() + $datatypeLabels;
		self::$m_prop_aliases = $smwgContLang->getPropertyAliases() + $smwgContLang->getDatatypeAliases();

		// Setup built-in predefined properties.
		// NOTE: all ids must start with underscores. The translation
		// for each ID, if any, is defined in the language files.
		// Properties without translation cannot be entered by or
		// displayed to users, whatever their "show" value below.
		self::$m_prop_types = array(
				self::TYPE_HAS_TYPE =>  array( '__typ', true ), // "has type"
				'_URI'   =>  array( '__spu', true ), // "equivalent URI"
				self::TYPE_CATEGORY =>  array( '__sin', false ), // instance of a category
				'_UNIT'  =>  array( '__sps', true ), // "displays unit"
				'_IMPO'  =>  array( '__imp', true ), // "imported from"
				self::TYPE_CONVERSION =>  array( '__sps', true ), // "corresponds to"
				'_SERV'  =>  array( '__sps', true ), // "provides service"
				'_PVAL'  =>  array( '__sps', true ), // "allows value"
				'_REDI'  =>  array( '__red', true ), // redirects to some page
				'_SUBP'  =>  array( '__sup', true ), // "subproperty of"
				self::TYPE_SUBCATEGORY =>  array( '__suc', !$smwgUseCategoryHierarchy ), // "subcategory of"
				'_CONC'  =>  array( '__con', false ), // associated concept
				self::TYPE_MODIFICATION_DATE =>  array( '_dat', false ), // "modification date"
				self::TYPE_CREATION_DATE =>  array( '_dat', false ), // "creation date"
				self::TYPE_NEW_PAGE =>  array( '_boo', false ), // "is a new page"
				self::TYPE_LAST_EDITOR =>  array( '_wpg', false ), // "last editor is"
				self::TYPE_ERROR  =>  array( '_wpp', false ), // "has improper value for"
				'_LIST'  =>  array( '__pls', true ), // "has fields"
				self::TYPE_SORTKEY =>  array( '__key', false ), // sort key of a page
				'_SF_DF' => array( '__spf', true ), // Semantic Form's default form property
				'_SF_AF' => array( '__spf', true ),  // Semantic Form's alternate form property
				self::TYPE_SUBOBJECT =>  array( '__sob', true ), // "has subobject"
				self::TYPE_ASKQUERY  =>  array( '__sob', false ), // "has query"
				'_ASKST' =>  array( '_cod', true ), // "has query string"
				'_ASKFO' =>  array( '_txt', true ), // "has query format"
				'_ASKSI' =>  array( '_num', true ), // "has query size"
				'_ASKDE' =>  array( '_num', true ), // "has query depth"
				'_ASKDU' =>  array( '_num', true ), // "has query duration"
				self::TYPE_MEDIA => array( '_txt', true ), // "has media type"
				self::TYPE_MIME  => array( '_txt', true ), // "has mime type"
			);

		foreach ( $datatypeLabels as $typeid => $label ) {
			self::$m_prop_types[$typeid] = array( $typeid, true );
		}

		wfRunHooks( 'smwInitProperties' );
	}

	/**
	 * A method for registering/overwriting predefined properties for SMW.
	 * It should be called from within the hook 'smwInitProperties' only.
	 * IDs should start with three underscores "___" to avoid current and
	 * future confusion with SMW built-ins.
	 *
	 * @param $id string id
	 * @param $typeid SMW type id
	 * @param $label mixed string user label or false (internal property)
	 * @param $show boolean only used if label is given, see isShown()
	 *
	 * @note See self::isShown() for information about $show.
	 */
	static public function registerProperty( $id, $typeid, $label = false, $show = false ) {
		self::$m_prop_types[$id] = array( $typeid, $show );

		if ( $label != false ) {
			self::$m_prop_labels[$id] = $label;
		}
	}

	/**
	 * Add a new alias label to an existing property ID. Note that every ID
	 * should have a primary label, either provided by SMW or registered
	 * with self::registerProperty(). This function should be
	 * called from within the hook 'smwInitDatatypes' only.
	 *
	 * @param $id string id of a property
	 * @param $label string alias label for the property
	 *
	 * @note Always use registerProperty() for the first label. No property
	 * that has used "false" for a label on registration should have an
	 * alias.
	 */
	static public function registerPropertyAlias( $id, $label ) {
		self::$m_prop_aliases[$label] = $id;
	}

	public function equals( SMWDataItem $di ) {
		if ( $di->getDIType() !== SMWDataItem::TYPE_PROPERTY ) {
			return false;
		}

		return $di->getKey() === $this->m_key;
	}
}

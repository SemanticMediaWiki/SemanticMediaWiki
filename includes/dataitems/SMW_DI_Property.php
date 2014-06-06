<?php

namespace SMW;

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

	/**
	 * Legacy assignment
	 */
	const TYPE_SUBOBJECT  = PropertyRegistry::TYPE_SUBOBJECT;
	const TYPE_ERROR      = PropertyRegistry::TYPE_ERROR;
	const TYPE_CATEGORY = PropertyRegistry::TYPE_CATEGORY;
	const TYPE_SUBCATEGORY = PropertyRegistry::TYPE_SUBCATEGORY;
	const TYPE_SORTKEY = PropertyRegistry::TYPE_SORTKEY;
	const TYPE_MODIFICATION_DATE = PropertyRegistry::TYPE_MODIFICATION_DATE;
	const TYPE_CREATION_DATE = PropertyRegistry::TYPE_CREATION_DATE;
	const TYPE_LAST_EDITOR = PropertyRegistry::TYPE_LAST_EDITOR;
	const TYPE_NEW_PAGE = PropertyRegistry::TYPE_NEW_PAGE;
	const TYPE_HAS_TYPE = PropertyRegistry::TYPE_HAS_TYPE;
	const TYPE_CONVERSION = PropertyRegistry::TYPE_CONVERSION;
	const TYPE_ASKQUERY = PropertyRegistry::TYPE_ASKQUERY;
	const TYPE_MEDIA = PropertyRegistry::TYPE_MEDIA;
	const TYPE_MIME = PropertyRegistry::TYPE_MIME;

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
			if ( !PropertyRegistry::getInstance()->isKnownProperty( $key ) ) {
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
			PropertyRegistry::getInstance()->getPropertyVisibility( $this->m_key ) );
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
			return PropertyRegistry::getInstance()->findPropertyLabel( $this->m_key );
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
				$typearray = StoreFactory::getStore()->getPropertyValues( $diWikiPage, new self( '_TYPE' ) );

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
				$this->m_proptypeid = PropertyRegistry::getInstance()->getPredefinedPropertyTypeId( $this->m_key );
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
	 * @param SMWDataItem $di
	 *
	 * @return boolean
	 */
	public function equals( SMWDataItem $di ) {
		if ( $di->getDIType() !== SMWDataItem::TYPE_PROPERTY ) {
			return false;
		}

		return $di->getKey() === $this->m_key;
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

		$id = PropertyRegistry::getInstance()->findPropertyIdByLabel( $label );

		if ( $id === false ) {
			return new self( str_replace( ' ', '_', $label ), $inverse );
		} else {
			return new self( $id, $inverse );
		}
	}

	/**
	 * @see PropertyRegistry::findPropertyIdByLabel
	 *
	 * @deprecated since 1.9.3
	 */
	public static function findPropertyID( $label, $useAlias = true ) {
		return PropertyRegistry::getInstance()->findPropertyIdByLabel( $label, $useAlias );
	}

	/**
	 * @see PropertyRegistry::getPredefinedPropertyTypeId
	 *
	 * @deprecated since 1.9.3
	 */
	public static function getPredefinedPropertyTypeId( $key ) {
		return PropertyRegistry::getInstance()->getPredefinedPropertyTypeId( $key );
	}

	/**
	 * @see PropertyRegistry::findPropertyLabelById
	 *
	 * @deprecated since 1.9.3
	 */
	static public function findPropertyLabel( $id ) {
		return PropertyRegistry::getInstance()->findPropertyLabel( $id );
	}

	/**
	 * @see PropertyRegistry::registerProperty
	 *
	 * @deprecated since 1.9.3
	 */
	static public function registerProperty( $id, $typeid, $label = false, $show = false ) {
		PropertyRegistry::getInstance()->registerProperty( $id, $typeid, $label, $show);
	}

	/**
	 * @see PropertyRegistry::registerPropertyAlias
	 *
	 * @deprecated since 1.9.3
	 */
	static public function registerPropertyAlias( $id, $label ) {
		PropertyRegistry::getInstance()->registerPropertyAlias( $id, $label );
	}

}

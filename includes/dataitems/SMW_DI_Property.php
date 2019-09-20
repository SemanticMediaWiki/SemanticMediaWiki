<?php

namespace SMW;

use RuntimeException;
use SMW\Exception\DataItemException;
use SMW\Exception\DataTypeLookupException;
use SMW\Exception\PredefinedPropertyLabelMismatchException;
use SMW\Exception\PropertyLabelNotResolvedException;
use SMWDataItem;
use SMWDIUri;

/**
 * This class implements Property item
 *
 * @note PropertyRegistry class manages global registrations of predefined
 * (built-in) properties, and maintains an association of property IDs, localized
 * labels, and aliases.
 *
 * @since 1.6
 *
 * @author Markus Krötzsch
 * @author Jeroen De Dauw
 * @author mwjames
 */
class DIProperty extends SMWDataItem {

	/**
	 * @see PropertyRegistry::registerPredefinedProperties
	 */
	const TYPE_SUBOBJECT  = '_SOBJ';
	const TYPE_ERROR      = '_ERRP';
	const TYPE_CATEGORY = '_INST';
	const TYPE_SUBCATEGORY = '_SUBC';
	const TYPE_SORTKEY = '_SKEY';
	const TYPE_MODIFICATION_DATE = '_MDAT';
	const TYPE_CREATION_DATE = '_CDAT';
	const TYPE_LAST_EDITOR = '_LEDT';
	const TYPE_NEW_PAGE = '_NEWP';
	const TYPE_HAS_TYPE = '_TYPE';
	const TYPE_CONVERSION = '_CONV';
	const TYPE_ASKQUERY = '_ASK';
	const TYPE_MEDIA = '_MEDIA';
	const TYPE_MIME = '_MIME';
	const TYPE_DISPLAYTITLE = '_DTITLE';

	/**
	 * Change propagation
	 */
	const TYPE_CHANGE_PROP = '_CHGPRO';

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
	 * @var string
	 */
	private $propertyValueType;

	/**
	 * Interwiki prefix for when a property represents a non-local entity
	 *
	 * @var string
	 */
	private $interwiki = '';

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

		if ( ( $key === '' ) || ( $key[0] == '-' ) ) {
			throw new PropertyLabelNotResolvedException( "Illegal property key \"$key\"." );
		}

		if ( $key[0] == '_' ) {
			if ( !PropertyRegistry::getInstance()->isRegistered( $key ) ) {
				throw new PredefinedPropertyLabelMismatchException( "There is no predefined property with \"$key\"." );
			}
		}

		$this->m_key = $key;
		$this->m_inverse = $inverse;
	}

	/**
	 * @return integer
	 */
	public function getDIType() {
		return SMWDataItem::TYPE_PROPERTY;
	}

	/**
	 * @return string
	 */
	public function getKey() {
		return $this->m_key;
	}

	/**
	 * @return boolean
	 */
	public function isInverse() {
		return $this->m_inverse;
	}

	/**
	 * @since 3.1
	 *
	 * @return string
	 */
	public function getSha1() {
		return sha1( json_encode( [ $this->m_key, SMW_NS_PROPERTY, '', '' ] ) );
	}

	/**
	 * @return string
	 */
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

		if ( $this->isUserDefined() ) {
			return true;
		}

		return PropertyRegistry::getInstance()->isVisible( $this->m_key );
	}

	/**
	 * Return true if this is a usual wiki property that is defined by a
	 * wiki page, and not a property that is pre-defined in the wiki.
	 *
	 * @return boolean
	 */
	public function isUserDefined() {
		return $this->m_key[0] != '_';
	}

	/**
	 * Whether a user can freely use this property for value annotation or
	 * not.
	 *
	 * @since 3.0
	 *
	 * @return boolean
	 */
	public function isUserAnnotable() {

		// A user defined property is generally assumed to be unrestricted for
		// usage
		if ( $this->isUserDefined() ) {
			return true;
		}

		return PropertyRegistry::getInstance()->isAnnotable( $this->m_key );
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
		}

		return $prefix . PropertyRegistry::getInstance()->findPropertyLabelById( $this->m_key );
	}

	/**
	 * @since 2.4
	 *
	 * @return string
	 */
	public function getCanonicalLabel() {
		$prefix = $this->m_inverse ? '-' : '';

		if ( $this->isUserDefined() ) {
			return $prefix . str_replace( '_', ' ', $this->m_key );
		}

		return $prefix . PropertyRegistry::getInstance()->findCanonicalPropertyLabelById( $this->m_key );
	}

	/**
	 * Borrowing the skos:prefLabel definition where a preferred label is expected
	 * to have only one label per given language (skos:altLabel can have many
	 * alternative labels)
	 *
	 * An empty string signals that no preferred label is available in the current
	 * user language.
	 *
	 * @since 2.5
	 *
	 * @param string $languageCode
	 *
	 * @return string
	 */
	public function getPreferredLabel( $languageCode = '' ) {

		$label = PropertyRegistry::getInstance()->findPreferredPropertyLabelFromIdByLanguageCode(
			$this->m_key,
			$languageCode
		);

		if ( $label !== '' ) {
			return ( $this->m_inverse ? '-' : '' ) . $label;
		}

		return '';
	}

	/**
	 * @since 2.4
	 *
	 * @param string $interwiki
	 */
	public function setInterwiki( $interwiki ) {
		$this->interwiki = $interwiki;
	}

	/**
	 * Get an object of type DIWikiPage that represents the page which
	 * relates to this property, or null if no such page exists. The latter
	 * can happen for special properties without user-readable label.
	 *
	 * It is possible to construct subobjects of the property's wikipage by
	 * providing an optional subobject name.
	 *
	 * @param string $subobjectName
	 * @return DIWikiPage|null
	 */
	public function getDiWikiPage( $subobjectName = '' ) {

		$dbkey = $this->m_key;

		if ( !$this->isUserDefined() ) {
			$dbkey = $this->getLabel();
		}

		return $this->newDIWikiPage( $dbkey, $subobjectName );
	}

	/**
	 * @since 2.4
	 *
	 * @param string $subobjectName
	 *
	 * @return DIWikiPage|null
	 */
	public function getCanonicalDiWikiPage( $subobjectName = '' ) {

		if ( $this->isUserDefined() ) {
			$dbkey = $this->m_key;
		} elseif ( $this->m_key === $this->findPropertyTypeID() ) {
			// If _dat as property [[Date::...]] refers directly to its _dat type
			// then use the en-label as canonical representation
			$dbkey = PropertyRegistry::getInstance()->findPropertyLabelFromIdByLanguageCode( $this->m_key, 'en' );
		} else {
			$dbkey = PropertyRegistry::getInstance()->findCanonicalPropertyLabelById( $this->m_key );
		}

		return $this->newDIWikiPage( $dbkey, $subobjectName );
	}

	/**
	 * @since 2.4
	 *
	 * @return DIProperty
	 */
	public function getRedirectTarget() {

		if ( $this->m_inverse ) {
			return $this;
		}

		return ApplicationFactory::getInstance()->getStore()->getRedirectTarget( $this );
	}

	/**
	 * @deprecated since 3.0, use DIProperty::setPropertyValueType
	 */
	public function setPropertyTypeId( $valueType ) {
		return $this->setPropertyValueType( $valueType );
	}

	/**
	 * @since 3.0
	 *
	 * @param string $valueType
	 *
	 * @return self
	 * @throws DataTypeLookupException
	 * @throws RuntimeException
	 */
	public function setPropertyValueType( $valueType ) {

		if ( !DataTypeRegistry::getInstance()->isRegistered( $valueType ) ) {
			throw new DataTypeLookupException( "{$valueType} is an unknown type id" );
		}

		if ( $this->isUserDefined() && $this->propertyValueType === null ) {
			$this->propertyValueType = $valueType;
			return $this;
		}

		if ( !$this->isUserDefined() && $valueType === PropertyRegistry::getInstance()->getPropertyValueTypeById( $this->m_key ) ) {
			$this->propertyValueType = $valueType;
			return $this;
		}

		throw new RuntimeException( 'DataType cannot be altered for a predefined property' );
	}

	/**
	 * @deprecated since 3.0, use DIProperty::findPropertyValueType
	 */
	public function findPropertyTypeId() {
		return $this->findPropertyValueType();
	}

	/**
	 * Find the property's type ID, either by looking up its predefined ID
	 * (if any) or by retrieving the relevant information from the store.
	 * If no type is stored for a user defined property, the global default
	 * type will be used.
	 *
	 * @since 3.0
	 *
	 * @return string type ID
	 */
	public function findPropertyValueType() {

		if ( isset( $this->propertyValueType ) ) {
			return $this->propertyValueType;
		}

		if ( !$this->isUserDefined() ) {
			return $this->propertyValueType = PropertyRegistry::getInstance()->getPropertyValueTypeById( $this->m_key );
		}

		$diWikiPage = new DIWikiPage( $this->getKey(), SMW_NS_PROPERTY, $this->interwiki );
		$applicationFactory = ApplicationFactory::getInstance();

		$typearray = $applicationFactory->getPropertySpecificationLookup()->getSpecification(
			$this,
			new self( '_TYPE' )
		);

		if ( is_array( $typearray ) && count( $typearray ) >= 1 ) { // some types given, pick one (hopefully unique)
			$typeDataItem = reset( $typearray );

			if ( $typeDataItem instanceof SMWDIUri ) {
				$this->propertyValueType = $typeDataItem->getFragment();
			} else {
				// This is important. If a page has an invalid assignment to "has type", no
				// value will be stored, so the elseif case below occurs. But if the value
				// is retrieved within the same run, then the error value for "has type" is
				// cached and thus this case occurs. This is why it is important to tolerate
				// this case -- it is not necessarily a DB error.
				$this->propertyValueType = $applicationFactory->getSettings()->get( 'smwgPDefaultType' );
			}
		} else { // no type given
			$this->propertyValueType = $applicationFactory->getSettings()->get( 'smwgPDefaultType' );
		}

		return $this->propertyValueType;
	}

	/**
	 * @see DataItem::getSerialization
	 *
	 * @return string
	 */
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

		if ( $serialization[0] == '-' ) {
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
	 *
	 * To process wiki input, SMWPropertyValue should be used.
	 *
	 * @param $label string label for the property
	 * @param $inverse boolean states if the inverse of the property is constructed
	 *
	 * @return DIProperty object
	 */
	public static function newFromUserLabel( $label, $inverse = false, $languageCode = false ) {

		if ( $label !== '' && $label[0] == '-' ) {
			$label = substr( $label, 1 );
			$inverse = true;
		}

		// Special handling for when the user value contains a @LCODE marker
		if ( ( $annotatedLanguageCode = Localizer::getAnnotatedLanguageCodeFrom( $label ) ) !== false ) {
			$languageCode = $annotatedLanguageCode;
		}

		$id = false;
		$label = str_replace( '_', ' ', $label );

		if ( $languageCode ) {
			$id = PropertyRegistry::getInstance()->findPropertyIdFromLabelByLanguageCode(
				$label,
				$languageCode
			);
		}

		if ( $id !== false ) {
			return new self( $id, $inverse );
		}

		$id = PropertyRegistry::getInstance()->findPropertyIdByLabel(
			$label
		);

		if ( $id === false ) {
			return new self( str_replace( ' ', '_', $label ), $inverse );
		}

		return new self( $id, $inverse );
	}

	private function newDIWikiPage( $dbkey, $subobjectName ) {

		// If an inverse marker is present just omit the marker so a normal
		// property page link can be produced independent of its directionality
		if ( $dbkey !== '' && $dbkey[0] == '-'  ) {
			$dbkey = substr( $dbkey, 1 );
		}

		try {
			return new DIWikiPage( str_replace( ' ', '_', $dbkey ), SMW_NS_PROPERTY, $this->interwiki, $subobjectName );
		} catch ( DataItemException $e ) {
			return null;
		}
	}

}

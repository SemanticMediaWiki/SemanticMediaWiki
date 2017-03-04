<?php

namespace SMW;

/**
 * @license GNU GPL v2+
 * @since 2.1
 *
 * @author mwjames
 * @author Markus Krötzsch
 */
class PropertyRegistry {

	/**
	 * @var PropertyRegistry
	 */
	private static $instance = null;

	/**
	 * @var PropertyLabelFinder
	 */
	private $propertyLabelFinder = null;

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
	private $propertyTypes = array();

	/**
	 * @var string[]
	 */
	private $datatypeLabels = array();

	/**
	 * @var string[]
	 */
	private $propertyDescriptionMsgKeys = array();

	/**
	 * @var PropertyAliasFinder
	 */
	private $propertyAliasFinder;

	/**
	 * @var string[]
	 */
	private $dataTypePropertyExemptionList = array();

	/**
	 * @since 2.1
	 *
	 * @return PropertyRegistry
	 */
	public static function getInstance() {

		if ( self::$instance !== null ) {
			return self::$instance;
		}

		$propertyLabelFinder = ApplicationFactory::getInstance()->getPropertyLabelFinder();
		$extraneousLanguage = Localizer::getInstance()->getExtraneousLanguage();

		$propertyAliasFinder = new PropertyAliasFinder(
			$extraneousLanguage->getPropertyAliases(),
			$extraneousLanguage->getCanonicalPropertyAliases()
		);

		self::$instance = new self(
			DataTypeRegistry::getInstance(),
			$propertyLabelFinder,
			$propertyAliasFinder,
			$GLOBALS['smwgDataTypePropertyExemptionList']
		);

		self::$instance->registerPredefinedProperties( $GLOBALS['smwgUseCategoryHierarchy'] );

		return self::$instance;
	}

	/**
	 * @since 2.1
	 *
	 * @param DataTypeRegistry $datatypeRegistry
	 * @param PropertyLabelFinder $propertyLabelFinder
	 * @param PropertyAliasFinder $propertyAliasFinder
	 * @param array $dataTypePropertyExemptionList
	 */
	public function __construct( DataTypeRegistry $datatypeRegistry, PropertyLabelFinder $propertyLabelFinder, PropertyAliasFinder $propertyAliasFinder, array $dataTypePropertyExemptionList = array() ) {

		$this->datatypeLabels = $datatypeRegistry->getKnownTypeLabels();
		$this->propertyLabelFinder = $propertyLabelFinder;
		$this->propertyAliasFinder = $propertyAliasFinder;

		// To get an index access
		$this->dataTypePropertyExemptionList = array_flip( $dataTypePropertyExemptionList );

		foreach ( $this->datatypeLabels as $id => $label ) {

			if ( isset( $this->dataTypePropertyExemptionList[$label] ) ) {
				continue;
			}

			$this->registerPropertyLabel( $id, $label );
		}

		foreach ( $datatypeRegistry->getKnownTypeAliases() as $alias => $id ) {

			if ( isset( $this->dataTypePropertyExemptionList[$alias] ) ) {
				continue;
			}

			$this->registerPropertyAlias( $id, $alias );
		}
	}

	/**
	 * @since 2.1
	 */
	public static function clear() {
		self::$instance = null;
	}

	/**
	 * @since 2.1
	 *
	 * @return array
	 */
	public function getKnownPropertyTypes() {
		return $this->propertyTypes;
	}

	/**
	 * @since 2.1
	 *
	 * @return array
	 */
	public function getKnownPropertyAliases() {
		return $this->propertyAliasFinder->getKnownPropertyAliases();
	}

	/**
	 * A method for registering/overwriting predefined properties for SMW.
	 * It should be called from within the hook 'smwInitProperties' only.
	 * IDs should start with three underscores "___" to avoid current and
	 * future confusion with SMW built-ins.
	 *
	 * @param string $id
	 * @param string $typeId SMW type id
	 * @param string|bool $label user label or false (internal property)
	 * @param boolean $isVisible only used if label is given, see isShown()
	 * @param boolean $isAnnotable
	 *
	 * @note See self::isShown() for information it
	 */
	public function registerProperty( $id, $typeId, $label = false, $isVisible = false, $isAnnotable = true ) {

		$this->propertyTypes[$id] = array( $typeId, $isVisible, $isAnnotable );

		if ( $label !== false ) {
			$this->registerPropertyLabel( $id, $label );
		}
	}

	/**
	 * Add a new alias label to an existing property ID. Note that every ID
	 * should have a primary label, either provided by SMW or registered
	 * with registerProperty().
	 *
	 * @param $id string id of a property
	 * @param $label string alias label for the property
	 *
	 * @note Always use registerProperty() for the first label. No property
	 * that has used "false" for a label on registration should have an
	 * alias.
	 */
	public function registerPropertyAlias( $id, $label ) {
		$this->propertyAliasFinder->registerAliasByFixedLabel( $id, $label );
	}

	/**
	 * Register an alias using a message key to allow fetching localized
	 * labels dynamically (for when the user language is changed etc).
	 *
	 * @since 2.4
	 *
	 * @param string $id
	 * @param string $msgKey
	 */
	public function registerPropertyAliasByMsgKey( $id, $msgKey ) {
		$this->propertyAliasFinder->registerAliasByMsgKey( $id, $msgKey );
	}

	/**
	 * Register a description message key for allowing it to be displayed in a
	 * localized context.
	 *
	 * @since 2.5
	 *
	 * @param string $id
	 * @param string $msgKey
	 */
	public function registerPropertyDescriptionMsgKeyById( $id, $msgKey ) {
		$this->propertyDescriptionMsgKeys[$id] = $msgKey;
	}

	/**
	 * @since 2.5
	 *
	 * @param string $id
	 *
	 * @return string
	 */
	public function findPropertyDescriptionMsgKeyById( $id ) {
		return isset( $this->propertyDescriptionMsgKeys[$id] ) ? $this->propertyDescriptionMsgKeys[$id] : '';
	}

	/**
	 * Get the translated user label for a given internal property ID.
	 * Returns empty string for properties without a translation (these are
	 * usually internal, generated by SMW but not shown to the user).
	 *
	 * @note An empty string is returned for incomplete translation (language
	 * bug) or deliberately invisible property
	 *
	 * @since 2.1
	 *
	 * @param string $id
	 *
	 * @return string
	 */
	public function findPropertyLabelById( $id ) {

		// This is a hack but there is no other good way to make it work without
		// open a whole new can of worms
		// '__' indicates predefined properties of extensions that contain alias
		// and translated labels and if available we want the translated label
		if ( ( substr( $id, 0, 2 ) === '__' ) &&
			( $label = $this->propertyAliasFinder->findPropertyAliasById( $id ) ) ) {
			return $label;
		}

		// core has dedicated files per language so the label is available over
		// the invoked language
		return $this->propertyLabelFinder->findPropertyLabelById( $id );
	}

	/**
	 * @since 2.4
	 *
	 * @param string $id
	 *
	 * @return string
	 */
	public function findCanonicalPropertyLabelById( $id ) {
		return $this->propertyLabelFinder->findCanonicalPropertyLabelById( $id );
	}

	/**
	 * @since 2.5
	 *
	 * @param string $id
	 * @param string $languageCode
	 *
	 * @return string
	 */
	public function findPropertyLabelByLanguageCode( $id, $languageCode = '' ) {
		return $this->propertyLabelFinder->findPropertyLabelByLanguageCode( $id, $languageCode );
	}

	/**
	 * @deprecated since 2.1 use findPropertyLabelById instead
	 */
	public function findPropertyLabel( $id ) {
		return $this->findPropertyLabelById( $id );
	}

	/**
	 * Get the type ID of a predefined property, or '' if the property
	 * is not predefined.
	 * The function is guaranteed to return a type ID for keys of
	 * properties where isUserDefined() returns false.
	 *
	 * @param string $id
	 *
	 * @return string
	 */
	public function getPropertyTypeId( $id ) {

		if ( $this->isKnownPropertyId( $id ) ) {
			return $this->propertyTypes[$id][0];
		}

		return '';
	}

	/**
	 * @deprecated since 2.1 use getPropertyTypeId instead
	 */
	public function getPredefinedPropertyTypeId( $id ) {
		return $this->getPropertyTypeId( $id );
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
	public function findPropertyIdByLabel( $label, $useAlias = true ) {

		$id = $this->propertyLabelFinder->searchPropertyIdByLabel( $label );

		if ( $id !== false ) {
			return $id;
		} elseif ( $useAlias && $this->propertyAliasFinder->findPropertyIdByAlias( $label ) ) {
			return $this->propertyAliasFinder->findPropertyIdByAlias( $label );
		}

		return false;
	}

	/**
	 * @since 2.4
	 *
	 * @param string $label
	 * @param string $languageCode
	 *
	 * @return mixed string property ID or false
	 */
	public function findPropertyIdFromLabelByLanguageCode( $label, $languageCode = '' ) {

		$languageCode = mb_strtolower( trim( $languageCode ) );

		// Match the canonical form
		if ( $languageCode === '' ) {
			return $this->findPropertyIdByLabel( $label );
		}

		$extraneousLanguage = Localizer::getInstance()->getExtraneousLanguage(
			$languageCode
		);

		// Language dep. stored as aliases
		$aliases = $extraneousLanguage->getPropertyLabels() + $extraneousLanguage->getDatatypeLabels();

		if ( ( $id = array_search( $label, $aliases ) ) !== false && !isset( $this->dataTypePropertyExemptionList[$label] ) ) {
			return $id;
		}

		// Those are mostly from extension that register a msgKey as no dedicated
		// lang. file exists; maybe this should be cached somehow?
		foreach ( $this->propertyAliasFinder->getKnownPropertyAliasesWithMsgKey() as $key => $id ) {
			if ( $label === Message::get( $key, Message::TEXT, $languageCode ) ) {
				return $id;
			}
		}

		return false;
	}

	/**
	 * @since 2.5
	 *
	 * @param string $id
	 * @param string|null $languageCode
	 *
	 * @return string
	 */
	public function findPreferredPropertyLabelById( $id, $languageCode = '' ) {

		if ( $languageCode === false || $languageCode === '' ) {
			$languageCode = Localizer::getInstance()->getUserLanguage()->getCode();
		}

		return $this->propertyLabelFinder->findPreferredPropertyLabelByLanguageCode( $id, $languageCode );
	}

	/**
	 * @deprecated since 2.1 use findPropertyIdByLabel instead
	 */
	public function findPropertyId( $label, $useAlias = true ) {
		return $this->findPropertyIdByLabel( $label, $useAlias );
	}

	/**
	 * @since 2.1
	 *
	 * @param  string $id
	 *
	 * @return boolean
	 */
	public function isKnownPropertyId( $id ) {
		return isset( $this->propertyTypes[$id] ) || array_key_exists( $id, $this->propertyTypes );
	}

	/**
	 * @since 2.1
	 *
	 * @param  string $id
	 *
	 * @return boolean
	 */
	public function isVisibleToUser( $id ) {
		return $this->isKnownPropertyId( $id ) ? $this->propertyTypes[$id][1] : false;
	}

	/**
	 * @since 2.2
	 *
	 * @param  string $id
	 *
	 * @return boolean
	 */
	public function isUnrestrictedForAnnotationUse( $id ) {
		return $this->isKnownPropertyId( $id ) ? $this->propertyTypes[$id][2] : false;
	}

	/**
	 * @note All ids must start with underscores. The translation for each ID,
	 * if any, is defined in the language files. Properties without translation
	 * cannot be entered by or displayed to users, whatever their "show" value
	 * below.
	 */
	protected function registerPredefinedProperties( $useCategoryHierarchy ) {

		// array( Id, isVisibleToUser, isAnnotableByUser )

		$this->propertyTypes = array(
			'_TYPE'  => array( '__typ', true, true ), // "has type"
			'_URI'   => array( '__spu', true, true ), // "equivalent URI"
			'_INST'  => array( '__sin', false, true ), // instance of a category
			'_UNIT'  => array( '__sps', true, true ), // "displays unit"
			'_IMPO'  => array( '__imp', true, true ), // "imported from"
			'_CONV'  => array( '__sps', true, true ), // "corresponds to"
			'_SERV'  => array( '__sps', true, true ), // "provides service"
			'_PVAL'  => array( '__pval', true, true ), // "allows value"
			'_REDI'  => array( '__red', true, true ), // redirects to some page
			'_SUBP'  => array( '__sup', true, true ), // "subproperty of"
			'_SUBC'  => array( '__suc', !$useCategoryHierarchy, true ), // "subcategory of"
			'_CONC'  => array( '__con', false, true ), // associated concept
			'_MDAT'  => array( '_dat', false, false ), // "modification date"
			'_CDAT'  => array( '_dat', false, false ), // "creation date"
			'_NEWP'  => array( '_boo', false, false ), // "is a new page"
			'_EDIP'  => array( '_boo', true, true ), // "is edit protected"
			'_LEDT'  => array( '_wpg', false, false ), // "last editor is"
			'_ERRC'  => array( '__sob', false, false ), // "has error"
			'_ERRT'  => array( '__errt', false, false ), // "has error text"
			'_ERRP'  => array( '_wpp', false, false ), // "has improper value for"
			'_LIST'  => array( '__pls', true, true ), // "has fields"
			'_SKEY'  => array( '__key', false, true ), // sort key of a page

			// FIXME SF related properties to be removed with 3.0
			'_SF_DF' => array( '__spf', true, true ), // Semantic Form's default form property
			'_SF_AF' => array( '__spf', true, true ),  // Semantic Form's alternate form property

			'_SOBJ'  => array( '__sob', true, false ), // "has subobject"
			'_ASK'   => array( '__sob', false, false ), // "has query"
			'_ASKST' => array( '_cod', true, false ), // "Query string"
			'_ASKFO' => array( '_txt', true, false ), // "Query format"
			'_ASKSI' => array( '_num', true, false ), // "Query size"
			'_ASKDE' => array( '_num', true, false ), // "Query depth"
			'_ASKDU' => array( '_num', true, false ), // "Query duration"
			'_ASKSC' => array( '_txt', true, false ), // "Query source"
			'_ASKPA' => array( '_cod', true, false ), // "Query parameters"
			'_MEDIA' => array( '_txt', true, false ), // "has media type"
			'_MIME'  => array( '_txt', true, false ), // "has mime type"
			'_PREC'  => array( '_num', true, true ), // "Display precision of"
			'_LCODE' => array( '__lcode', true, true ), // "Language code"
			'_TEXT'  => array( '_txt', true, true ), // "Text"
			'_PDESC' => array( '_mlt_rec', true, true ), // "Property description"
			'_PVAP'  => array( '__pvap', true, true ), // "Allows pattern"
			'_PVALI'  => array( '__pvali', true, true ), // "Allows value list"
			'_DTITLE' => array( '_txt', false, true ), // "Display title of"
			'_PVUC'  => array( '__pvuc', true, true ), // Uniqueness constraint
			'_PEID'  => array( '_eid', true, true ), // External identifier
			'_PEFU'  => array( '__pefu', true, true ), // External formatter uri
			'_PPLB'  => array( '_mlt_rec', true, true ), // Preferred property label
		);

		foreach ( $this->datatypeLabels as $id => $label ) {
			$this->propertyTypes[$id] = array( $id, true, true );
		}

		// @deprecated since 2.1
		\Hooks::run( 'smwInitProperties' );

		\Hooks::run( 'SMW::Property::initProperties', array( $this ) );
	}

	private function registerPropertyLabel( $id, $label, $asCanonical = true ) {
		$this->propertyLabelFinder->registerPropertyLabel( $id, $label, $asCanonical );
	}

}

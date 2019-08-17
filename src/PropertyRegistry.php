<?php

namespace SMW;

use RuntimeException;

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
	private $propertyList = [];

	/**
	 * @var string[]
	 */
	private $datatypeLabels = [];

	/**
	 * @var string[]
	 */
	private $propertyDescriptionMsgKeys = [];

	/**
	 * @var PropertyAliasFinder
	 */
	private $propertyAliasFinder;

	/**
	 * @var string[]
	 */
	private $dataTypePropertyExemptionList = [];

	/**
	 * @since 2.1
	 *
	 * @return PropertyRegistry
	 */
	public static function getInstance() {

		if ( self::$instance !== null ) {
			return self::$instance;
		}

		$localizer = Localizer::getInstance();
		$applicationFactory = ApplicationFactory::getInstance();
		$lang = $localizer->getLang();

		$propertyAliasFinder = new PropertyAliasFinder(
			$applicationFactory->getCache(),
			$lang->getPropertyAliases(),
			$lang->getCanonicalPropertyAliases()
		);

		$propertyAliasFinder->setContentLanguageCode(
			$localizer->getContentLanguage()->getCode()
		);

		$settings = $applicationFactory->getSettings();

		self::$instance = new self(
			DataTypeRegistry::getInstance(),
			$applicationFactory->getPropertyLabelFinder(),
			$propertyAliasFinder,
			$settings->get( 'smwgDataTypePropertyExemptionList' )
		);

		self::$instance->initProperties(
			TypesRegistry::getPropertyList(
				$settings->isFlagSet( 'smwgCategoryFeatures', SMW_CAT_HIERARCHY )
			)
		);

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
	public function __construct( DataTypeRegistry $datatypeRegistry, PropertyLabelFinder $propertyLabelFinder, PropertyAliasFinder $propertyAliasFinder, array $dataTypePropertyExemptionList = [] ) {

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
	public function getPropertyList() {
		return $this->propertyList;
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
	 * @param string $valueType SMW type id
	 * @param string|bool $label user label or false (internal property)
	 * @param boolean $isVisible only used if label is given, see isShown()
	 * @param boolean $isAnnotable
	 * @param boolean $isDeclarative
	 */
	public function registerProperty( $id, $valueType, $label = false, $isVisible = false, $isAnnotable = true, $isDeclarative = false ) {

		$signature = [ $valueType, $isVisible, $isAnnotable, $isDeclarative ];

		// Don't override an existing property registration with a different signature
		if ( isset( $this->propertyList[$id] ) && $signature !== $this->propertyList[$id] ) {
			throw new RuntimeException( "Overriding the `$id` property with a different signature is not permitted!" );
		}

		$this->propertyList[$id] = $signature;

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
	public function registerPropertyDescriptionByMsgKey( $id, $msgKey ) {
		$this->propertyDescriptionMsgKeys[$id] = $msgKey;
	}

	/**
	 * @deprecated since 3.0, use PropertyRegistry::registerPropertyDescriptionByMsgKey
	 */
	public function registerPropertyDescriptionMsgKeyById( $id, $msgKey ) {
		$this->registerPropertyDescriptionByMsgKey( $id, $msgKey );
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
	public function findPropertyLabelFromIdByLanguageCode( $id, $languageCode = '' ) {
		return $this->propertyLabelFinder->findPropertyLabelFromIdByLanguageCode( $id, $languageCode );
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
	public function getPropertyValueTypeById( $id ) {

		if ( $this->isRegistered( $id ) ) {
			return $this->propertyList[$id][0];
		}

		return '';
	}

	/**
	 * @deprecated since 3.0, use PropertyRegistry::getPropertyValueTypeById instead
	 */
	public function getPropertyTypeId( $id ) {
		return $this->getPropertyValueTypeById( $id );
	}

	/**
	 * @deprecated since 2.1 use getPropertyValueTypeById instead
	 */
	public function getPredefinedPropertyTypeId( $id ) {
		return $this->getPropertyValueTypeById( $id );
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

		$lang = Localizer::getInstance()->getLang(
			$languageCode
		);

		// Language dep. stored as aliases
		$aliases = $lang->getPropertyLabels() + $lang->getDatatypeLabels();

		if ( ( $id = array_search( $label, $aliases ) ) !== false && !isset( $this->dataTypePropertyExemptionList[$label] ) ) {
			return $id;
		}

		// Those are mostly from extension that register a msgKey as no dedicated
		// lang. file exists; maybe this should be cached somehow?
		foreach ( $this->propertyAliasFinder->getKnownPropertyAliasesByLanguageCode( $languageCode ) as $alias => $id ) {
			if ( $label === $alias ) {
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
	public function findPreferredPropertyLabelFromIdByLanguageCode( $id, $languageCode = '' ) {

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
	 * @deprecated since 3.0 use isRegistered instead
	 */
	public function isKnownPropertyId( $id ) {
		return $this->isRegistered( $id );
	}

	/**
	 * @since 2.1
	 *
	 * @param  string $id
	 *
	 * @return boolean
	 */
	public function isRegistered( $id ) {
		return isset( $this->propertyList[$id] ) || array_key_exists( $id, $this->propertyList );
	}

	/**
	 * @since 2.1
	 *
	 * @param  string $id
	 *
	 * @return boolean
	 */
	public function isVisible( $id ) {
		return $this->isRegistered( $id ) ? $this->propertyList[$id][1] : false;
	}

	/**
	 * @since 3.0
	 *
	 * @param string $id
	 *
	 * @return boolean
	 */
	public function isAnnotable( $id ) {
		return $this->isRegistered( $id ) ? $this->propertyList[$id][2] : false;
	}

	/**
	 * @since 3.0
	 *
	 * @param string $id
	 *
	 * @return boolean
	 */
	public function isDeclarative( $id ) {

		if ( !$this->isRegistered( $id ) ) {
			return false;
		}

		return isset( $this->propertyList[$id][3] ) ? $this->propertyList[$id][3] : false;
	}

	/**
	 * @note All ids must start with underscores. The translation for each ID,
	 * if any, is defined in the language files. Properties without translation
	 * cannot be entered by or displayed to users, whatever their "show" value
	 * below.
	 */
	protected function initProperties( array $propertyList ) {

		$this->propertyList = $propertyList;

		foreach ( $this->datatypeLabels as $id => $label ) {
			$this->propertyList[$id] = [ $id, true, true, false ];
		}

		// @deprecated since 2.1
		\Hooks::run( 'smwInitProperties' );

		\Hooks::run( 'SMW::Property::initProperties', [ $this ] );
	}

	private function registerPropertyLabel( $id, $label, $asCanonical = true ) {
		$this->propertyLabelFinder->registerPropertyLabel( $id, $label, $asCanonical );
	}

}

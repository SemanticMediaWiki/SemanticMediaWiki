<?php

namespace SMW;

/**
 * @license GNU GPL v2+
 * @since 1.9.3
 *
 * @author Markus Krötzsch
 * @author Jeroen De Dauw
 * @author mwjames
 */
class PropertyRegistry {

	// Property subobject data item ID
	const TYPE_SUBOBJECT  = '_SOBJ';
	// Property improper value data item ID
	const TYPE_ERROR      = '_ERRP';
	// Property instance of a category
	const TYPE_INST = '_INST';
	const TYPE_CATEGORY = self::TYPE_INST;
	// Property "subcategory of"
	const TYPE_SUBC = '_SUBC';
	const TYPE_SUBCATEGORY = self::TYPE_SUBC;
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

	/** @var PropertyRegistry */
	private static $instance = null;

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
	 * Array with entries "property id" => "property label"
	 * @var string[]
	 */
	private $propertyLabels = array();

	/**
	 * Array with entries "property alias" => "property id"
	 * @var string[]
	 */
	private $propertyAliases = array();

	/** @var string[] */
	private $datatypeLabels = array();

	/**
	 * @since 1.9.3
	 *
	 * @return PropertyRegistry
	 */
	public static function getInstance() {

		if ( self::$instance === null ) {

			self::$instance = new self(
				DataTypeRegistry::getInstance(),
				$GLOBALS['smwgContLang']->getPropertyLabels(),
				$GLOBALS['smwgContLang']->getPropertyAliases()
			);

			self::$instance->registerBuildInProperties( $GLOBALS['smwgUseCategoryHierarchy'] );
		}

		return self::$instance;
	}

	/**
	 * @since 1.9.3
	 *
	 * @param DataTypeRegistry $datatypeRegistry
	 * @param array $propertyLabels
	 * @param array $propertyAliases
	 */
	public function __construct( DataTypeRegistry $datatypeRegistry, array $propertyLabels, array $propertyAliases ) {

		$this->datatypeLabels  = $datatypeRegistry->getKnownTypeLabels();
		$datatypeAliases = $datatypeRegistry->getKnownTypeAliases();

		foreach ( $this->datatypeLabels as $id => $label ) {
			$this->registerPropertyLabel( $id, $label );
		}

		foreach ( $datatypeAliases as $alias => $id ) {
			$this->registerPropertyAlias( $id, $alias );
		}

		foreach ( $propertyLabels as $id => $label ) {
			$this->registerPropertyLabel( $id, $label );
		}

		foreach ( $propertyAliases as $alias => $id ) {
			$this->registerPropertyAlias( $id, $alias );
		}
	}

	/**
	 * @since 1.9.3
	 */
	public static function clear() {
		self::$instance = null;
	}

	/**
	 * @since 1.9.3
	 *
	 * @return array
	 */
	public function getKnownPropertyTypes() {
		return $this->propertyTypes;
	}

	/**
	 * @since 1.9.3
	 *
	 * @return array
	 */
	public function getKnownPropertyLabels() {
		return $this->propertyLabels;
	}

	/**
	 * @since 1.9.3
	 *
	 * @return array
	 */
	public function getKnownPropertyAliases() {
		return $this->propertyAliases;
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
	public function registerProperty( $id, $typeid, $label = false, $show = false ) {

		$this->propertyTypes[ $id ] = array( $typeid, $show );

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
		$this->propertyAliases[ $label ] = $id;
	}

	/**
	 * Get the translated user label for a given internal property ID.
	 * Returns empty string for properties without a translation (these are
	 * usually internal, generated by SMW but not shown to the user).
	 *
	 * @note An empty string is returned for incomplete translation (language
	 * bug) or deliberately invisible property
	 *
	 * @since 1.9.3
	 *
	 * @param string $id
	 *
	 * @return string
	 */
	public function findPropertyLabelById( $id ) {

		if ( array_key_exists( $id, $this->propertyLabels ) ) {
			return $this->propertyLabels[ $id ];
		}

		return '';
	}

	/**
	 * @deprecated since 1.9.3 use findPropertyLabelById instead
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
	public function getDataTypeId( $id ) {

		if ( $this->isKnownProperty( $id ) ) {
			return $this->propertyTypes[$id][0];
		}

		return '';
	}

	/**
	 * @deprecated since 1.9.3 use getDataTypeId instead
	 */
	public function getPredefinedPropertyTypeId( $id ) {
		return $this->getDataTypeId( $id );
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

		$id = array_search( $label, $this->propertyLabels );

		if ( $id !== false ) {
			return $id;
		} elseif ( $useAlias && array_key_exists( $label, $this->propertyAliases ) ) {
			return $this->propertyAliases[ $label ];
		}

		return false;
	}

	/**
	 * @deprecated since 1.9.3 use findPropertyIdByLabel instead
	 */
	public function findPropertyId( $label, $useAlias = true ) {
		return $this->findPropertyIdByLabel( $label, $useAlias );
	}

	/**
	 * @since 1.9.3
	 *
	 * @param  string $id
	 *
	 * @return boolean
	 */
	public function isKnownProperty( $id ) {
		return isset( $this->propertyTypes[ $id ] ) || array_key_exists( $id, $this->propertyTypes );
	}

	/**
	 * @since 1.9.3
	 *
	 * @param  string $id
	 *
	 * @return boolean
	 */
	public function getPropertyVisibility( $id ) {
		return $this->isKnownProperty( $id ) ? $this->propertyTypes[ $id ][1] : false;
	}

	/**
	 * @note All ids must start with underscores. The translation for each ID,
	 * if any, is defined in the language files. Properties without translation
	 * cannot be entered by or displayed to users, whatever their "show" value
	 * below.
	 */
	protected function registerBuildInProperties( $useCategoryHierarchy ) {

		$this->propertyTypes = array(
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
			self::TYPE_SUBCATEGORY =>  array( '__suc', !$useCategoryHierarchy ), // "subcategory of"
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
			self::TYPE_SUBOBJECT =>  array( '_wpg', true ), // "has subobject"
			self::TYPE_ASKQUERY  =>  array( '_wpg', false ), // "has query"
			'_ASKST' =>  array( '_cod', true ), // "has query string"
			'_ASKFO' =>  array( '_txt', true ), // "has query format"
			'_ASKSI' =>  array( '_num', true ), // "has query size"
			'_ASKDE' =>  array( '_num', true ), // "has query depth"
			'_ASKDU' =>  array( '_num', true ), // "has query duration"
			self::TYPE_MEDIA => array( '_txt', true ), // "has media type"
			self::TYPE_MIME  => array( '_txt', true ), // "has mime type"
		);

		foreach ( $this->datatypeLabels as $id => $label ) {
			$this->propertyTypes[ $id ] = array( $id, true );
		}

		wfRunHooks( 'smwInitProperties' );
		wfRunHooks( 'SMW::Property::initProperties' );
	}

	private function registerPropertyLabel( $id, $label ) {
		$this->propertyLabels[ $id ] = $label;
	}

}

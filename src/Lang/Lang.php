<?php

namespace SMW\Lang;

/**
 * This class provides "extraneous" language functions independent from MediaWiki
 * to handle certain language options in a way required by Semantic MediaWiki and
 * its registration system.
 *
 * @license GNU GPL v2+
 * @since 2.4
 *
 * @author mwjames
 */
class Lang {

	/**
	 * @var Lang
	 */
	private static $instance = null;

	/**
	 * @var LanguageContents
	 */
	private $languageContents;

	/**
	 * @var string
	 */
	private $languageCode = 'en';

	/**
	 * @var string
	 */
	private $canonicalFallbackLanguageCode = 'en';

	/**
	 * @var array
	 */
	private $propertyIdByLabelMap = [];

	/**
	 * @var array
	 */
	private $dateFormatsMap = [];

	/**
	 * @var array
	 */
	private $monthMap = [];

	/**
	 * @since 2.4
	 *
	 * @param LanguageContents $languageContents
	 */
	public function __construct( LanguageContents $languageContents ) {
		$this->languageContents = $languageContents;
	}

	/**
	 * @since 2.4
	 *
	 * @return Lang
	 */
	public static function getInstance() {

		if ( self::$instance !== null ) {
			return self::$instance;
		}

		// $cache = ApplicationFactory::getInstance()->getCache()

		$jsonContentsFileReader = new JsonContentsFileReader();

		self::$instance = new self(
			new LanguageContents(
				$jsonContentsFileReader,
				new FallbackFinder( $jsonContentsFileReader )
			)
		);

		return self::$instance;
	}

	/**
	 * @since 2.4
	 */
	public static function clear() {
		self::$instance = null;
	}

	/**
	 * @since 2.4
	 *
	 * @return string
	 */
	public function getCode() {
		return $this->languageCode;
	}

	/**
	 * @deprecated since 3.0, use Lang::fetch
	 * @since 2.4
	 *
	 * @return string
	 */
	public function fetchByLanguageCode( $languageCode ) {
		return $this->fetch( $languageCode );
	}

	/**
	 * @since 2.4
	 *
	 * @return string
	 */
	public function fetch( $languageCode ) {

		$this->languageCode = strtolower( trim( $languageCode ) );

		if ( !$this->languageContents->isLoaded( $this->languageCode ) ) {
			$this->languageContents->load( $this->languageCode );
		}

		$this->canonicalFallbackLanguageCode = $this->languageContents->getCanonicalFallbackLanguageCode();

		return $this;
	}

	/**
	 * Function that returns an array of namespace identifiers.
	 *
	 * @since 2.4
	 *
	 * @return array
	 */
	public function getNamespaces() {

		$namespaces = $this->languageContents->get(
			'namespace.labels',
			$this->languageCode
		);

		$namespaces += $this->languageContents->get(
			'namespace.labels',
			$this->canonicalFallbackLanguageCode
		);

		foreach ( $namespaces as $key => $value ) {
			unset( $namespaces[$key] );

			if ( defined( $key ) ) {
				$namespaces[constant($key)] = $value;
			}
		}

		return $namespaces;
	}

	/**
	 * Function that returns an array of namespace aliases, if any
	 *
	 * @since 2.4
	 *
	 * @return array
	 */
	public function getNamespaceAliases() {

		$namespaceAliases = $this->languageContents->get(
			'namespace.aliases',
			$this->languageCode
		);

		$namespaceAliases += $this->languageContents->get(
			'namespace.aliases',
			$this->canonicalFallbackLanguageCode
		);

		foreach ( $namespaceAliases as $alias => $namespace ) {
			if ( defined( $namespace ) ) {
				$namespaceAliases[$alias] = constant( $namespace );
			}
		}

		return $namespaceAliases;
	}

	/**
	 * Return all labels that are available as names for built-in datatypes. Those
	 * are the types that users can access via [[has type::...]] (more built-in
	 * types may exist for internal purposes but the user won't need to
	 * know this). The returned array is indexed by (internal) type ids.
	 *
	 * @since 2.4
	 *
	 * @return array
	 */
	public function getDatatypeLabels() {

		$datatypeLabels = $this->languageContents->get(
			'datatype.labels',
			$this->languageCode
		);

		$datatypeLabels += $this->languageContents->get(
			'datatype.labels',
			$this->canonicalFallbackLanguageCode
		);

		return $datatypeLabels;
	}

	/**
	 * @since 2.5
	 *
	 * @param string $label
	 *
	 * @return string
	 */
	public function findDatatypeByLabel( $label ) {

		$label = mb_strtolower( $label );

		$datatypeLabels = $this->getDatatypeLabels();
		$datatypeLabels = array_flip( $datatypeLabels );
		$datatypeLabels += $this->getDatatypeAliases();

		foreach ( $datatypeLabels as $key => $id ) {
			if ( mb_strtolower( $key ) === $label ) {
				return $id;
			}
		}

		return '';
	}

	/**
	 * @since 2.4
	 *
	 * @return array
	 */
	public function getCanonicalDatatypeLabels() {

		$datatypeLabels = $this->languageContents->get(
			'datatype.labels',
			$this->canonicalFallbackLanguageCode
		);

		$canonicalPropertyLabels = array_flip( $datatypeLabels );

		return $canonicalPropertyLabels;
	}

	/**
	 * Return an array that maps aliases to internal type ids. All ids used here
	 * should also have a primary label defined in m_DatatypeLabels.
	 *
	 * @since 2.4
	 *
	 * @return array
	 */
	public function getDatatypeAliases() {

		$datatypeAliases = $this->languageContents->get(
			'datatype.aliases',
			$this->languageCode
		);

		$datatypeAliases += $this->languageContents->get(
			'datatype.aliases',
			$this->canonicalFallbackLanguageCode
		);

		return $datatypeAliases;
	}

	/**
	 * @since 2.4
	 *
	 * @return array
	 */
	public function getCanonicalPropertyLabels() {

		$canonicalPropertyLabels = $this->languageContents->get(
			'property.labels',
			$this->canonicalFallbackLanguageCode
		);

		$canonicalPropertyLabels = array_flip( $canonicalPropertyLabels );

		$canonicalPropertyLabels += $this->languageContents->get(
			'property.aliases',
			$this->canonicalFallbackLanguageCode
		);

		$canonicalPropertyLabels += $this->languageContents->get(
			'datatype.aliases',
			$this->canonicalFallbackLanguageCode
		);

		return $canonicalPropertyLabels;
	}

	/**
	 * Function that returns the labels for predefined properties.
	 *
	 * @since 2.4
	 *
	 * @return array
	 */
	public function getPropertyLabels() {

		$propertyLabels = $this->languageContents->get(
			'property.labels',
			$this->languageCode
		);

		$propertyLabels += $this->languageContents->get(
			'property.labels',
			$this->canonicalFallbackLanguageCode
		);

		return $propertyLabels;
	}

	/**
	 * Aliases for predefined properties, if any.
	 *
	 * @since 2.4
	 *
	 * @return array
	 */
	public function getCanonicalPropertyAliases() {

		$canonicalPropertyAliases = $this->languageContents->get(
			'property.aliases',
			$this->canonicalFallbackLanguageCode
		);

		// Add standard property lables from the canonical language as
		// aliases
		$propertyLabels = $this->languageContents->get(
			'property.labels',
			$this->canonicalFallbackLanguageCode
		);

		$canonicalPropertyAliases += array_flip( $propertyLabels );

		return $canonicalPropertyAliases;
	}

	/**
	 * Aliases for predefined properties, if any.
	 *
	 * @since 2.4
	 *
	 * @return array
	 */
	public function getPropertyAliases() {

		$propertyAliases = $this->languageContents->get(
			'property.aliases',
			$this->languageCode
		);

		$propertyLabels = $this->languageContents->get(
			'property.labels',
			$this->languageCode
		);

		$propertyAliases += array_flip( $propertyLabels );

		return $propertyAliases;
	}

	/**
	 * @deprecated use getPropertyIdByLabel
	 */
	protected function getPropertyId( $propertyLabel ) {

		$list += $this->languageContents->get(
			'property.aliases',
			$this->languageCode
		);

		$list += $this->languageContents->get(
			'property.aliases',
			$this->canonicalFallbackLanguageCode
		);

		return $list;
	}

	/**
	 * Function receives property name (for example, `Modificatino date') and
	 * returns a property id (for example, `_MDAT'). Property name may be
	 * localized one. If property name is not recognized, a null value returned.
	 *
	 * @since 2.4
	 *
	 * @return string|null
	 */
	public function getPropertyIdByLabel( $label ) {

		$this->initPropertyIdByLabelMap( $this->languageCode );

		if ( isset( $this->propertyIdByLabelMap[$this->languageCode]['label'][$label] ) ) {
			return $this->propertyIdByLabelMap[$this->languageCode]['label'][$label];
		};

		if ( isset( $this->propertyIdByLabelMap[$this->languageCode]['alias'][$label] ) ) {
			return $this->propertyIdByLabelMap[$this->languageCode]['alias'][$label];
		};

		return null;
	}

	/**
	 * @since 3.0
	 *
	 * @return array
	 */
	public function getPropertyLabelList() {

		$this->initPropertyIdByLabelMap( $this->languageCode );

		if ( isset( $this->propertyIdByLabelMap[$this->languageCode] ) ) {
			return $this->propertyIdByLabelMap[$this->languageCode];
		}

		return [];
	}

	/**
	 * Function that returns the preferred date formats
	 *
	 * Preferred interpretations for dates with 1, 2, and 3 components. There
	 * is an array for each case, and the constants define the obvious order
	 * (e.g. SMW_YDM means "first Year, then Day, then Month). Unlisted
	 * combinations will not be accepted at all.
	 *
	 * @since 2.4
	 *
	 * @return array
	 */
	public function getDateFormats() {

		$languageCode = $this->languageCode;

		if ( !isset( $this->dateFormatsMap[$languageCode] ) || $this->dateFormatsMap[$languageCode] === [] ) {
			$this->dateFormatsMap[$languageCode] = $this->getDateFormatsByLanguageCode( $languageCode );
		}

		return $this->dateFormatsMap[$languageCode];
	}

	/**
	 * @since 2.4
	 *
	 * @param integer|null $precision
	 *
	 * @return string
	 */
	public function getPreferredDateFormatByPrecision( $precision = null ) {

		$dateOutputFormats = $this->languageContents->get(
			'date.precision',
			$this->languageCode
		);

		foreach ( $dateOutputFormats as $key => $format ) {
			if ( @constant( $key ) === $precision ) {
				return $format;
			}
		}

		// Fallback
		return 'd F Y H:i:s';
	}

	/**
	 * @deprecated use findMonthNumberByLabel
	 */
	public function findMonth( $label ) {
		return $this->findMonthNumberByLabel( $label );
	}

	/**
	 * Function looks up a month and returns the corresponding number.
	 *
	 * @since 2.4
	 *
	 * @param string $label
	 *
	 * @return false|integer
	 */
	public function findMonthNumberByLabel( $label ) {

		$languageCode = $this->languageCode;

		if ( !isset( $this->months[$languageCode] ) || $this->months[$languageCode] === [] ) {
			$this->months[$languageCode] = $this->languageContents->get( 'date.months', $languageCode );
		}

		foreach ( $this->months[$languageCode] as $key => $value ) {
			if ( strcasecmp( $value[0], $label ) == 0 || strcasecmp( $value[1], $label ) == 0 ) {
				return $key + 1; // array starts with 0
			}
		}

		return false;
	}

	/**
	 * @deprecated use getMonthLabelByNumber
	 */
	public function getMonthLabel( $number ) {
		return $this->getMonthLabelByNumber( $number );
	}

	/**
	 * Return the name of the month with the given number.
	 *
	 * @since 2.4
	 *
	 * @param integer $number
	 *
	 * @return array
	 */
	public function getMonthLabelByNumber( $number ) {

		$languageCode = $this->languageCode;
		$number = (int)( $number - 1 ); // array starts with 0

		if ( !isset( $this->months[$languageCode] ) || $this->months[$languageCode] === [] ) {
			$this->months[$languageCode] = $this->languageContents->get( 'date.months', $languageCode );
		}

		if ( ( ( $number >= 0 ) && ( $number <= 11 ) ) && isset( $this->months[$languageCode][$number]) ) {
			return $this->months[$languageCode][$number][0]; // Long name
		}

		return '';
	}

	private function getDateFormatsByLanguageCode( $languageCode ) {

		$dateformats = [];

		foreach ( $this->languageContents->get( 'date.format', $languageCode ) as $row ) {
			$internalNumberFormat = [];

			foreach ( $row as $value ) {
				$internalNumberFormat[] = constant( $value );
			}

			$dateformats[] = $internalNumberFormat;
		}

		return $dateformats;
	}

	private function initPropertyIdByLabelMap( $languageCode ) {

		if ( isset( $this->propertyIdByLabelMap[$languageCode] ) && $this->propertyIdByLabelMap[$languageCode] !== [] ) {
			return;
		}

		$this->propertyIdByLabelMap[$languageCode] = [];

		$propertyLabels = $this->languageContents->get(
			'property.labels',
			$languageCode
		);

		$propertyLabels += $this->languageContents->get(
			'datatype.labels',
			$languageCode
		);

		foreach ( $propertyLabels as $id => $label ) {
			$this->propertyIdByLabelMap[$languageCode]['label'][$label] = $id;
		}

		$propertyAliases = $this->languageContents->get(
			'property.aliases',
			$languageCode
		);

		$propertyAliases += $this->languageContents->get(
			'property.aliases',
			$this->canonicalFallbackLanguageCode
		);

		foreach ( $propertyAliases as $label => $id ) {
			$this->propertyIdByLabelMap[$languageCode]['alias'][$label] = $id;
		}
	}

}

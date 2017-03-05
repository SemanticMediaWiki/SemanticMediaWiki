<?php

namespace SMW\ExtraneousLanguage;

use RuntimeException;

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
class ExtraneousLanguage {

	/**
	 * @var ExtraneousLanguage
	 */
	private static $instance = null;

	/**
	 * @var LanguageContents
	 */
	private $languageContents;

	/**
	 * @var boolean
	 */
	private $historicTypeNamespace = false;

	/**
	 * @var string
	 */
	private $languageCode = 'en';

	/**
	 * @var array
	 */
	private $propertyIdByLabelMap = array();

	/**
	 * @var array
	 */
	private $dateFormatsMap = array();

	/**
	 * @var array
	 */
	private $monthMap = array();

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
	 * @return ExtraneousLanguage
	 */
	public static function getInstance() {

		if ( self::$instance !== null ) {
			return self::$instance;
		}

		// $cache = ApplicationFactory::getInstance()->getCache()

		$jsonLanguageContentsFileReader = new JsonLanguageContentsFileReader();
		//$languageFileContentsReader->setCachePrefix( $cacheFactory->getCachePrefix() )

		self::$instance = new self(
			new LanguageContents(
				$jsonLanguageContentsFileReader,
				new LanguageFallbackFinder( $jsonLanguageContentsFileReader )
			)
		);

		self::$instance->setHistoricTypeNamespace(
			$GLOBALS['smwgHistoricTypeNamespace']
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
	 * @since 2.5
	 *
	 * @param boolean $historicTypeNamespace
	 */
	public function setHistoricTypeNamespace( $historicTypeNamespace ) {
		$this->historicTypeNamespace = (bool)$historicTypeNamespace;
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
	 * @since 2.4
	 *
	 * @return string
	 */
	public function fetchByLanguageCode( $languageCode ) {

		$this->languageCode = strtolower( trim( $languageCode ) );

		if ( !$this->languageContents->has( $this->languageCode ) ) {
			$this->languageContents->prepareWithLanguage( $this->languageCode );
		}

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

		$namespaces = $this->languageContents->getContentsByLanguageWithIndex(
			$this->languageCode,
			'namespaces'
		);

		$namespaces += $this->languageContents->getContentsByLanguageWithIndex(
			$this->languageContents->getCanonicalFallbackLanguageCode(),
			'namespaces'
		);

		foreach ( $namespaces as $key => $value ) {
			unset( $namespaces[$key] );
			$namespaces[constant($key)] = $value;
		}

		if ( $this->historicTypeNamespace ) {
			return $namespaces;
		}

		unset( $namespaces[SMW_NS_TYPE] );
		unset( $namespaces[SMW_NS_TYPE_TALK] );

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

		$namespaceAliases = $this->languageContents->getContentsByLanguageWithIndex(
			$this->languageCode,
			'namespaceAliases'
		);

		$namespaceAliases += $this->languageContents->getContentsByLanguageWithIndex(
			$this->languageContents->getCanonicalFallbackLanguageCode(),
			'namespaceAliases'
		);

		foreach ( $namespaceAliases as $alias => $namespace ) {
			$namespaceAliases[$alias] = constant( $namespace );
		}

		if ( $this->historicTypeNamespace ) {
			return $namespaceAliases;
		}

		foreach ( $namespaceAliases as $alias => $namespace ) {
			if ( $namespace === SMW_NS_TYPE || $namespace === SMW_NS_TYPE_TALK ) {
				unset( $namespaceAliases[$alias] );
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

		$datatypeLabels = $this->languageContents->getContentsByLanguageWithIndex(
			$this->languageCode,
			'dataTypeLabels'
		);

		$datatypeLabels += $this->languageContents->getContentsByLanguageWithIndex(
			$this->languageContents->getCanonicalFallbackLanguageCode(),
			'dataTypeLabels'
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

		$datatypeLabels = $this->languageContents->getContentsByLanguageWithIndex(
			$this->languageContents->getCanonicalFallbackLanguageCode(),
			'dataTypeLabels'
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

		$datatypeAliases = $this->languageContents->getContentsByLanguageWithIndex(
			$this->languageCode,
			'dataTypeAliases'
		);

		$datatypeAliases += $this->languageContents->getContentsByLanguageWithIndex(
			$this->languageContents->getCanonicalFallbackLanguageCode(),
			'dataTypeAliases'
		);

		return $datatypeAliases;
	}

	/**
	 * @since 2.4
	 *
	 * @return array
	 */
	public function getCanonicalPropertyLabels() {

		$canonicalPropertyLabels = $this->languageContents->getContentsByLanguageWithIndex(
			$this->languageContents->getCanonicalFallbackLanguageCode(),
			'propertyLabels'
		);

		$canonicalPropertyLabels = array_flip( $canonicalPropertyLabels );

		$canonicalPropertyLabels += $this->languageContents->getContentsByLanguageWithIndex(
			$this->languageContents->getCanonicalFallbackLanguageCode(),
			'propertyAliases'
		);

		$canonicalPropertyLabels += $this->languageContents->getContentsByLanguageWithIndex(
			$this->languageContents->getCanonicalFallbackLanguageCode(),
			'dataTypeAliases'
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

		$propertyLabels = $this->languageContents->getContentsByLanguageWithIndex(
			$this->languageCode,
			'propertyLabels'
		);

		$propertyLabels += $this->languageContents->getContentsByLanguageWithIndex(
			$this->languageContents->getCanonicalFallbackLanguageCode(),
			'propertyLabels'
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

		$canonicalPropertyAliases = $this->languageContents->getContentsByLanguageWithIndex(
			$this->languageContents->getCanonicalFallbackLanguageCode(),
			'propertyAliases'
		);

		// Add standard property lables from the canonical language as
		// aliases
		$propertyLabels = $this->languageContents->getContentsByLanguageWithIndex(
			$this->languageContents->getCanonicalFallbackLanguageCode(),
			'propertyLabels'
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

		$propertyAliases = $this->languageContents->getContentsByLanguageWithIndex(
			$this->languageCode,
			'propertyAliases'
		);

		$propertyLabels = $this->languageContents->getContentsByLanguageWithIndex(
			$this->languageCode,
			'propertyLabels'
		);

		$propertyAliases += array_flip( $propertyLabels );

		return $propertyAliases;
	}

	/**
	 * @deprecated use getPropertyIdByLabel
	 */
	protected function getPropertyId( $propertyLabel ) {

		$list += $this->languageContents->getContentsByLanguageWithIndex(
			$this->languageCode,
			'propertyAliases'
		);

		$list += $this->languageContents->getContentsByLanguageWithIndex(
			$this->languageContents->getCanonicalFallbackLanguageCode(),
			'propertyAliases'
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
	public function getPropertyIdByLabel( $propertyLabel ) {

		$languageCode = $this->languageCode;

		if ( !isset( $this->propertyIdByLabelMap[$languageCode] ) || $this->propertyIdByLabelMap[$languageCode] === array() ) {
			foreach ( $this->getPropertyLabels() as $id => $label ) {
				$this->propertyIdByLabelMap[$languageCode][$label] = $id;
			}
		}

		if ( isset( $this->propertyIdByLabelMap[$languageCode][$propertyLabel] ) ) {
			return $this->propertyIdByLabelMap[$languageCode][$propertyLabel];
		};

		$propertyAliases = $this->getPropertyAliases();

		if ( isset( $propertyAliases[$propertyLabel] ) ) {
			return $propertyAliases[$propertyLabel];
		}

		return null;
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

		if ( !isset( $this->dateFormatsMap[$languageCode] ) || $this->dateFormatsMap[$languageCode] === array() ) {
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

		$dateOutputFormats = $this->languageContents->getContentsByLanguageWithIndex(
			$this->languageCode,
			'dateFormatsByPrecision'
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

		if ( !isset( $this->months[$languageCode] ) || $this->months[$languageCode] === array() ) {
			$this->months[$languageCode] = $this->languageContents->getContentsByLanguageWithIndex( $languageCode, 'months' );
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

		if ( !isset( $this->months[$languageCode] ) || $this->months[$languageCode] === array() ) {
			$this->months[$languageCode] = $this->languageContents->getContentsByLanguageWithIndex( $languageCode, 'months' );
		}

		if ( ( ( $number >= 0 ) && ( $number <= 11 ) ) && isset( $this->months[$languageCode][$number]) ) {
			return $this->months[$languageCode][$number][0]; // Long name
		}

		return '';
	}

	private function getDateFormatsByLanguageCode( $languageCode ) {

		$dateformats = array();

		foreach ( $this->languageContents->getContentsByLanguageWithIndex( $languageCode, 'dateFormats' ) as $row ) {
			$internalNumberFormat = array();

			foreach ( $row as $value ) {
				$internalNumberFormat[] = constant( $value );
			}

			$dateformats[] = $internalNumberFormat;
		}

		return $dateformats;
	}

}

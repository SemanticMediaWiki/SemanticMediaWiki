<?php

namespace SMW\Localizer\LocalLanguage;

/**
 * This class provides "local" language functions independent from MediaWiki
 * to handle certain language options in a way required by Semantic MediaWiki
 * and its registration system.
 *
 * @license GPL-2.0-or-later
 * @since 2.4
 *
 * @author mwjames
 */
class LocalLanguage {

	private static ?LocalLanguage $instance = null;

	private string $languageCode = 'en';

	private string $canonicalFallbackLanguageCode = 'en';

	private array $propertyIdByLabelMap = [];

	private array $dateFormatsMap = [];

	private array $monthMap = [];

	private array $months = [];

	/**
	 * @since 2.4
	 */
	public function __construct( private readonly LanguageContents $languageContents ) {
	}

	/**
	 * @since 2.4
	 */
	public static function getInstance(): LocalLanguage {
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
	public static function clear(): void {
		self::$instance = null;
	}

	/**
	 * @since 2.4
	 */
	public function getCode(): string {
		return $this->languageCode;
	}

	/**
	 * @deprecated since 3.0, use Lang::fetch
	 * @since 2.4
	 */
	public function fetchByLanguageCode( $languageCode ): static {
		return $this->fetch( $languageCode );
	}

	/**
	 * @since 2.4
	 */
	public function fetch( string $languageCode ): static {
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
	 */
	public function getNamespaces(): array {
		$namespaces = (array)$this->languageContents->get(
			'namespace.labels',
			$this->languageCode
		);

		$namespaces += (array)$this->languageContents->get(
			'namespace.labels',
			$this->canonicalFallbackLanguageCode
		);

		foreach ( $namespaces as $key => $value ) {
			unset( $namespaces[$key] );

			if ( defined( $key ) ) {
				$namespaces[constant( $key )] = $value;
			}
		}

		return $namespaces;
	}

	/**
	 * Function that returns an array of namespace aliases, if any
	 *
	 * @since 2.4
	 */
	public function getNamespaceAliases(): array {
		$namespaceAliases = (array)$this->languageContents->get(
			'namespace.aliases',
			$this->languageCode
		);

		$namespaceAliases += (array)$this->languageContents->get(
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
	 */
	public function getDatatypeLabels(): array {
		$datatypeLabels = (array)$this->languageContents->get(
			'datatype.labels',
			$this->languageCode
		);

		$datatypeLabels += (array)$this->languageContents->get(
			'datatype.labels',
			$this->canonicalFallbackLanguageCode
		);

		return $datatypeLabels;
	}

	/**
	 * @since 2.5
	 */
	public function findDatatypeByLabel( string $label ): string {
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
	 */
	public function getCanonicalDatatypeLabels(): array {
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
	 */
	public function getDatatypeAliases(): array {
		$datatypeAliases = (array)$this->languageContents->get(
			'datatype.aliases',
			$this->languageCode
		);

		$datatypeAliases += (array)$this->languageContents->get(
			'datatype.aliases',
			$this->canonicalFallbackLanguageCode
		);

		return $datatypeAliases;
	}

	/**
	 * @since 2.4
	 */
	public function getCanonicalPropertyLabels(): array {
		$canonicalPropertyLabels = (array)$this->languageContents->get(
			'property.labels',
			$this->canonicalFallbackLanguageCode
		);

		$canonicalPropertyLabels = array_flip( $canonicalPropertyLabels );

		$canonicalPropertyLabels += (array)$this->languageContents->get(
			'property.aliases',
			$this->canonicalFallbackLanguageCode
		);

		$canonicalPropertyLabels += (array)$this->languageContents->get(
			'datatype.aliases',
			$this->canonicalFallbackLanguageCode
		);

		return $canonicalPropertyLabels;
	}

	/**
	 * Function that returns the labels for predefined properties.
	 *
	 * @since 2.4
	 */
	public function getPropertyLabels(): array {
		$propertyLabels = (array)$this->languageContents->get(
			'property.labels',
			$this->languageCode
		);

		$propertyLabels += (array)$this->languageContents->get(
			'property.labels',
			$this->canonicalFallbackLanguageCode
		);

		return $propertyLabels;
	}

	/**
	 * Aliases for predefined properties, if any.
	 *
	 * @since 2.4
	 */
	public function getCanonicalPropertyAliases(): array {
		$canonicalPropertyAliases = (array)$this->languageContents->get(
			'property.aliases',
			$this->canonicalFallbackLanguageCode
		);

		// Add standard property lables from the canonical language as
		// aliases
		$propertyLabels = (array)$this->languageContents->get(
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
	 */
	public function getPropertyAliases(): array {
		$propertyAliases = (array)$this->languageContents->get(
			'property.aliases',
			$this->languageCode
		);

		$propertyLabels = (array)$this->languageContents->get(
			'property.labels',
			$this->languageCode
		);

		$propertyAliases += array_flip( $propertyLabels );

		return $propertyAliases;
	}

	/**
	 * @deprecated use getPropertyIdByLabel
	 */
	protected function getPropertyId( $propertyLabel ): array {
		$list = (array)$this->languageContents->get(
			'property.aliases',
			$this->languageCode
		);

		$list += (array)$this->languageContents->get(
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
	 */
	public function getPropertyIdByLabel( $label ): ?string {
		$this->initPropertyIdByLabelMap( $this->languageCode );

		if ( isset( $this->propertyIdByLabelMap[$this->languageCode]['label'][$label] ) ) {
			return $this->propertyIdByLabelMap[$this->languageCode]['label'][$label];
		}

		if ( isset( $this->propertyIdByLabelMap[$this->languageCode]['alias'][$label] ) ) {
			return $this->propertyIdByLabelMap[$this->languageCode]['alias'][$label];
		}

		return null;
	}

	/**
	 * @since 3.0
	 */
	public function getPropertyLabelList(): array {
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
	 */
	public function getDateFormats(): array {
		$languageCode = $this->languageCode;

		if ( !isset( $this->dateFormatsMap[$languageCode] ) || $this->dateFormatsMap[$languageCode] === [] ) {
			$this->dateFormatsMap[$languageCode] = $this->getDateFormatsByLanguageCode( $languageCode );
		}

		return $this->dateFormatsMap[$languageCode];
	}

	/**
	 * @since 2.4
	 */
	public function getPreferredDateFormatByPrecision( ?int $precision = null ): string {
		$dateOutputFormats = (array)$this->languageContents->get(
			'date.precision',
			$this->languageCode
		);

		foreach ( $dateOutputFormats as $key => $format ) {
			if ( defined( $key ) && constant( $key ) === $precision ) {
				return $format;
			}
		}

		// Fallback
		return 'd F Y H:i:s';
	}

	/**
	 * @deprecated use findMonthNumberByLabel
	 */
	public function findMonth( $label ): int|float|false {
		return $this->findMonthNumberByLabel( $label );
	}

	/**
	 * Function looks up a month and returns the corresponding number.
	 *
	 * @since 2.4
	 *
	 * @param string $label
	 *
	 * @return false|int
	 */
	public function findMonthNumberByLabel( $label ): int|float|false {
		$languageCode = $this->languageCode;

		if ( !isset( $this->months[$languageCode] ) || $this->months[$languageCode] === [] ) {
			$this->months[$languageCode] = $this->languageContents->get( 'date.months', $languageCode );
		}

		foreach ( $this->months[$languageCode] as $key => $value ) {
			foreach ( $value as $variant ) {
				if ( strcasecmp( $variant, $label ) == 0 ) {
					return $key + 1; // array starts with 0
				}
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
	 */
	public function getMonthLabelByNumber( int $number ): string {
		$languageCode = $this->languageCode;
		$number = (int)( $number - 1 ); // array starts with 0

		if ( !isset( $this->months[$languageCode] ) || $this->months[$languageCode] === [] ) {
			$this->months[$languageCode] = $this->languageContents->get( 'date.months', $languageCode );
		}

		if ( ( ( $number >= 0 ) && ( $number <= 11 ) ) && isset( $this->months[$languageCode][$number] ) ) {
			return $this->months[$languageCode][$number][0]; // Long name
		}

		return '';
	}

	private function getDateFormatsByLanguageCode( $languageCode ): array {
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

	private function initPropertyIdByLabelMap( $languageCode ): void {
		if ( isset( $this->propertyIdByLabelMap[$languageCode] ) && $this->propertyIdByLabelMap[$languageCode] !== [] ) {
			return;
		}

		$this->propertyIdByLabelMap[$languageCode] = [];

		$propertyLabels = (array)$this->languageContents->get(
			'property.labels',
			$languageCode
		);

		$propertyLabels += (array)$this->languageContents->get(
			'datatype.labels',
			$languageCode
		);

		foreach ( $propertyLabels as $id => $label ) {
			$this->propertyIdByLabelMap[$languageCode]['label'][$label] = $id;
		}

		$propertyAliases = (array)$this->languageContents->get(
			'property.aliases',
			$languageCode
		);

		$propertyAliases += (array)$this->languageContents->get(
			'property.aliases',
			$this->canonicalFallbackLanguageCode
		);

		foreach ( $propertyAliases as $label => $id ) {
			$this->propertyIdByLabelMap[$languageCode]['alias'][$label] = $id;
		}
	}

}

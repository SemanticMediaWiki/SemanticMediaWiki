<?php

namespace SMW;

/**
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
	 * @var ExtraneousLanguageFileHandler
	 */
	private $extraneousLanguageFileHandler;

	/**
	 * @var boolean
	 */
	private $historicTypeNamespace;

	/**
	 * @var string
	 */
	private $languageCode = 'en';

	/**
	 * @var SMWlanguage
	 */
	private $language = null;

	/**
	 * @since 2.4
	 *
	 * @param ExtraneousLanguageFileHandler $extraneousLanguageFileHandler
	 * @param boolean $historicTypeNamespace
	 */
	public function __construct( ExtraneousLanguageFileHandler $extraneousLanguageFileHandler, $historicTypeNamespace = false ) {
		$this->extraneousLanguageFileHandler = $extraneousLanguageFileHandler;
		$this->historicTypeNamespace = $historicTypeNamespace;
	}

	/**
	 * @since 2.4
	 *
	 * @return ExtraneousLanguage
	 */
	public static function getInstance() {

		if ( self::$instance === null ) {
			self::$instance = new self(
				new ExtraneousLanguageFileHandler(),
				$GLOBALS['smwgHistoricTypeNamespace']
			);
		}

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
	 * @since 2.4
	 *
	 * @return string
	 */
	public function fetchByLanguageCode( $languageCode ) {

		$this->languageCode = strtolower( trim( $languageCode ) );

		if ( !isset( $this->language[$this->languageCode] ) ) {
			$this->language[$this->languageCode] = $this->extraneousLanguageFileHandler->newByLanguageCode( $this->languageCode );
		}

		return $this;
	}

	protected function getLanguage() {

		if ( !isset( $this->language[$this->languageCode] ) ) {
			$this->fetchByLanguageCode( $this->languageCode );
		}

		return $this->language[$this->languageCode];
	}

	/**
	 * Function that returns an array of namespace identifiers.
	 *
	 * @since 2.4
	 *
	 * @return array
	 */
	public function getNamespaces() {
		return $this->getLanguage()->getNamespaces();
	}

	/**
	 * Function that returns an array of namespace aliases, if any
	 *
	 * @since 2.4
	 *
	 * @return array
	 */
	public function getNamespaceAliases() {
		return $this->getLanguage()->getNamespaceAliases();
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
		return $this->getLanguage()->getDatatypeLabels();
	}

	/**
	 * @since 2.4
	 *
	 * @return array
	 */
	public function getCanonicalDatatypeLabels() {
		return $this->getLanguage()->getCanonicalDatatypeLabels();
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
		return $this->getLanguage()->getDatatypeAliases();
	}

	/**
	 * @since 2.4
	 *
	 * @return array
	 */
	public function getCanonicalPropertyLabels() {
		return $this->getLanguage()->getCanonicalPropertyLabels();
	}

	/**
	 * Function that returns the labels for predefined properties.
	 *
	 * @since 2.4
	 *
	 * @return array
	 */
	public function getPropertyLabels() {
		return $this->getLanguage()->getPropertyLabels();
	}

	/**
	 * Aliases for predefined properties, if any.
	 *
	 * @since 2.4
	 *
	 * @return array
	 */
	public function getCanonicalPropertyAliases() {
		return $this->getLanguage()->getCanonicalPropertyAliases();
	}

	/**
	 * Aliases for predefined properties, if any.
	 *
	 * @since 2.4
	 *
	 * @return array
	 */
	public function getPropertyAliases() {
		return $this->getLanguage()->getPropertyAliases();
	}

	/**
	 * @deprecated use findMonthNumberByLabel
	 */
	public function getPropertyId( $propertyLabel ) {
		return $this->getPropertyIdByLabel( $propertyLabel );
	}

	/**
	 * Function receives property name (for example, `Modificatino date') and returns property id
	 * (for example, `_MDAT'). Property name may be localized one. If property name is not
	 * recognized, null value returned.
	 *
	 * @since 2.4
	 *
	 * @return string|null
	 */
	public function getPropertyIdByLabel( $propertyLabel ) {
		return $this->getLanguage()->getPropertyId( $propertyLabel );
	}

	/**
	 * Function that returns the preferred date formats
	 *
	 * @since 2.4
	 *
	 * @return array
	 */
	public function getDateFormats() {
		return $this->getLanguage()->getDateFormats();
	}

	/**
	 * @since 2.4
	 *
	 * @param integer|null $precision
	 *
	 * @return string
	 */
	public function getPreferredDateFormatByPrecision( $precision = null ) {

		$dateOutputFormats = $this->getLanguage()->getPreferredDateFormats();

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
		return $this->getLanguage()->findMonth( $label );
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
		return $this->getLanguage()->getMonthLabel( $number );
	}

}

<?php

namespace SMW;

/**
 * @license GNU GPL v2+
 * @since 2.2
 *
 * @author mwjames
 */
class PropertyLabelFinder {

	/**
	 * @var Store
	 */
	private $store;

	/**
	 * Array with entries "property id" => "property label"
	 *
	 * @var string[]
	 */
	private $languageDependentPropertyLabels = array();

	/**
	 * Array with entries "property label" => "property id"
	 *
	 * @var string[]
	 */
	private $canonicalPropertyLabels = array();

	/**
	 * @var string[]
	 */
	private $canonicalDatatypeLabels = array();

	/**
	 * @since 2.2
	 *
	 * @param Store $store
	 * @param array $languageDependentPropertyLabels
	 * @param array $canonicalPropertyLabels
	 */
	public function __construct( Store $store, array $languageDependentPropertyLabels = array(), array $canonicalPropertyLabels = array(), array $canonicalDatatypeLabels = array() ) {
		$this->store = $store;
		$this->languageDependentPropertyLabels = $languageDependentPropertyLabels;
		$this->canonicalPropertyLabels = $canonicalPropertyLabels;
		$this->canonicalDatatypeLabels = $canonicalDatatypeLabels;
	}

	/**
	 * @since 2.2
	 *
	 * @return array
	 */
	public function getKownPredefinedPropertyLabels() {
		return $this->languageDependentPropertyLabels;
	}

	/**
	 * @since 2.4
	 *
	 * @param string $id
	 *
	 * @return string|boolean
	 */
	public function findCanonicalPropertyLabelById( $id ) {

		// Due to mapped lists avoid possible mismatch on dataTypes
		// (e.g. Text -> _TEXT vs. Text -> _txt)
		if ( ( $label = array_search( $id, $this->canonicalDatatypeLabels ) ) ) {
			return $label;
		}

		return array_search( $id, $this->canonicalPropertyLabels );
	}

	/**
	 * @note An empty string is returned for incomplete translation (language
	 * bug) or deliberately invisible property
	 *
	 * @since 2.2
	 *
	 * @param string $id
	 *
	 * @return string
	 */
	public function findPropertyLabelById( $id ) {

		if ( array_key_exists( $id, $this->languageDependentPropertyLabels ) ) {
			return $this->languageDependentPropertyLabels[$id];
		}

		return '';
	}

	/**
	 * @note An empty string is returned for incomplete translation (language
	 * bug) or deliberately invisible property
	 *
	 * @since 2.5
	 *
	 * @param string $id
	 * @param string $languageCode
	 *
	 * @return string
	 */
	public function findPropertyLabelByLanguageCode( $id, $languageCode = '' ) {

		if ( $languageCode === '' ) {
			return $this->findPropertyLabelById( $id );
		}

		$extraneousLanguage = Localizer::getInstance()->getExtraneousLanguage(
			mb_strtolower( trim( $languageCode ) )
		);

		$labels = $extraneousLanguage->getPropertyLabels() + $extraneousLanguage->getDatatypeLabels();

		if ( isset( $labels[$id] ) ) {
			return $labels[$id];
		}

		return '';
	}

	/**
	 * @since 2.5
	 *
	 * @param string $id
	 * @param string $languageCode
	 *
	 * @return string
	 */
	public function findPreferredPropertyLabelByLanguageCode( $id, $languageCode = '' ) {

		if ( $id === '' || $id === false ) {
			return '';
		}

		// Lookup is cached in PropertySpecificationLookup
		$propertySpecificationLookup = ApplicationFactory::getInstance()->getPropertySpecificationLookup();

		$preferredPropertyLabel = $propertySpecificationLookup->getPreferredPropertyLabelBy(
			new DIProperty( str_replace( ' ', '_', $id ) ),
			$languageCode
		);

		return $preferredPropertyLabel;
	}

	/**
	 * @since 2.5
	 *
	 * @param string $text
	 * @param string $languageCode
	 *
	 * @return DIProperty[]|[]
	 */
	public function findPropertyListFromLabelByLanguageCode( $text, $languageCode = '' ) {

		if ( $text === '' ) {
			return array();
		}

		if ( $languageCode === '' ) {
			$languageCode = Localizer::getInstance()->getContentLanguage()->getCode();
		}

		$dataValue = DataValueFactory::getInstance()->newDataValueByProperty(
			new DIProperty( '_PPLB' )
		);

		$dataValue->setUserValue(
			$dataValue->getTextWithLanguageTag( $text, $languageCode )
		);

		$queryFactory = ApplicationFactory::getInstance()->getQueryFactory();
		$descriptionFactory = $queryFactory->newDescriptionFactory();

		$description = $descriptionFactory->newConjunction( array(
			$descriptionFactory->newNamespaceDescription( SMW_NS_PROPERTY ),
			$descriptionFactory->newFromDataValue( $dataValue )
		) );

		$propertyList = array();

		$query = $queryFactory->newQuery( $description );
		$query->setOption( $query::PROC_CONTEXT, 'PropertyLabelFinder' );

		$queryResult = $this->store->getQueryResult(
			$query
		);

		if ( !$queryResult instanceof \SMWQueryResult ) {
			return $propertyList;
		}

		foreach ( $queryResult->getResults() as $result ) {
			$propertyList[] = DIProperty::newFromUserLabel( $result->getDBKey() );
		}

		return $propertyList;
	}

	/**
	 * @since 2.2
	 *
	 * @param string $label
	 *
	 * @return string|false
	 */
	public function searchPropertyIdByLabel( $label ) {
		return array_search( $label, $this->languageDependentPropertyLabels );
	}

	/**
	 * @since 2.2
	 *
	 * @param string $id
	 * @param string $label
	 */
	public function registerPropertyLabel( $id, $label, $asCanonical = true ) {

		// Prevent an extension from overriding an already registered
		// canonical label that may point to a different ID
		if ( isset( $this->canonicalPropertyLabels[$label] ) && $this->canonicalPropertyLabels[$label] !== $id ) {
			return;
		}

		$this->languageDependentPropertyLabels[$id] = $label;

		// This is done so extensions can register the property id/label as being
		// canonical in their representation while the alias may hold translated
		// language depedendant matches
		if ( $asCanonical ) {
			$this->canonicalPropertyLabels[$label] = $id;
		}
	}

}

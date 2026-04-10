<?php

namespace SMW;

use SMW\DataItems\Property;
use SMW\Localizer\Localizer;
use SMW\Query\QueryResult;
use SMW\Services\ServicesFactory as ApplicationFactory;

/**
 * @license GPL-2.0-or-later
 * @since 2.2
 *
 * @author mwjames
 */
class PropertyLabelFinder {

	private Store $store;

	/**
	 * Array with entries "property id" => "property label"
	 *
	 * @var string[]
	 */
	private array $languageDependentPropertyLabels;

	/**
	 * Array with entries "property label" => "property id"
	 *
	 * @var string[]
	 */
	private array $canonicalPropertyLabels;

	/**
	 * @var string[]
	 */
	private array $canonicalDatatypeLabels;

	/**
	 * @since 2.2
	 *
	 * @param Store $store
	 * @param array $languageDependentPropertyLabels
	 * @param array $canonicalPropertyLabels
	 */
	public function __construct( Store $store, array $languageDependentPropertyLabels = [], array $canonicalPropertyLabels = [], array $canonicalDatatypeLabels = [] ) {
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
	public function getKownPredefinedPropertyLabels(): array {
		return $this->languageDependentPropertyLabels;
	}

	/**
	 * @since 2.4
	 *
	 * @param string $id
	 *
	 * @return string|bool
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
	public function findPropertyLabelById( $id ): string {
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
	public function findPropertyLabelFromIdByLanguageCode( $id, $languageCode = '' ) {
		if ( $languageCode === '' ) {
			return $this->findPropertyLabelById( $id );
		}

		$lang = Localizer::getInstance()->getLang(
			mb_strtolower( trim( $languageCode ) )
		);

		$labels = $lang->getPropertyLabels() + $lang->getDatatypeLabels();

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

		$preferredPropertyLabel = $propertySpecificationLookup->getPreferredPropertyLabelByLanguageCode(
			new Property( str_replace( ' ', '_', $id ) ),
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
	 * @return mixed[]
	 */
	public function findPropertyListFromLabelByLanguageCode( $text, $languageCode = '' ): array {
		if ( $text === '' ) {
			return [];
		}

		if ( $languageCode === '' ) {
			$languageCode = Localizer::getInstance()->getContentLanguage()->getCode();
		}

		$dataValue = DataValueFactory::getInstance()->newDataValueByProperty(
			new Property( '_PPLB' )
		);

		$dataValue->setUserValue(
			$dataValue->getTextWithLanguageTag( $text, $languageCode )
		);

		$queryFactory = ApplicationFactory::getInstance()->getQueryFactory();
		$descriptionFactory = $queryFactory->newDescriptionFactory();

		$description = $descriptionFactory->newConjunction( [
			$descriptionFactory->newNamespaceDescription( SMW_NS_PROPERTY ),
			$descriptionFactory->newFromDataValue( $dataValue )
		] );

		$propertyList = [];

		$query = $queryFactory->newQuery( $description );
		$query->setOption( $query::PROC_CONTEXT, 'PropertyLabelFinder' );
		$query->setLimit( 100 );

		$queryResult = $this->store->getQueryResult(
			$query
		);

		if ( !$queryResult instanceof QueryResult ) {
			return $propertyList;
		}

		foreach ( $queryResult->getResults() as $result ) {
			$propertyList[] = Property::newFromUserLabel( $result->getDBKey() );
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
	public function searchPropertyIdByLabel( $label ): int|string|false {
		return array_search( $label, $this->languageDependentPropertyLabels );
	}

	/**
	 * @since 2.2
	 *
	 * @param string $id
	 * @param string $label
	 */
	public function registerPropertyLabel( $id, $label, $asCanonical = true ): void {
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

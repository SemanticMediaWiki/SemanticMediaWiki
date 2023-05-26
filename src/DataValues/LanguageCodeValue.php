<?php

namespace SMW\DataValues;

use SMW\Localizer;
use SMWDIBlob as DIBlob;

/**
 * Handles a string value to adhere the BCP47 normative content declaration for
 * a language code tag
 *
 * @see https://en.wikipedia.org/wiki/IETF_language_tag
 *
 * @license GNU GPL v2+
 * @since 2.4
 *
 * @author mwjames
 */
class LanguageCodeValue extends StringValue {

	/**
	 * DV identifier
	 */
	const TYPE_ID = '__lcode';

	/**
	 * nonstandardLanguageCodeMapping
	 */
	private $nonstandardLanguageCodeMapping;

	/**
	 * @param string $typeid
	 */
	public function __construct( $typeid = '' ) {
		parent::__construct( self::TYPE_ID );
		
		$this->nonstandardLanguageCodeMapping = \LanguageCode::getNonstandardLanguageCodeMapping();
	}

	/**
	 * @see DataValue::parseUserValue
	 *
	 * @param string $value
	 */
	protected function parseUserValue( $userValue ) {

		$languageCode = Localizer::asBCP47FormattedLanguageCode( $userValue );

		if ( $languageCode === '' ) {
			$this->addErrorMsg( [
				'smw-datavalue-languagecode-missing',
				$this->m_property !== null ? $this->m_property->getLabel() : 'UNKNOWN'
			] );
			return;
		}

		// Checks whether the language tag is valid in MediaWiki for when
		// it is not executed in a query context
		
		// ensure non-standard language codes are mapped to
		// their canonical form (e.g. de-x-formal to de-formal)

		$mappedLanguageCode = !in_array( $languageCode, $this->nonstandardLanguageCodeMapping ) ? $languageCode
			: array_search( $languageCode, $this->nonstandardLanguageCodeMapping );
		
		if ( !$this->getOption( self::OPT_QUERY_CONTEXT ) && !Localizer::isKnownLanguageTag( $mappedLanguageCode ) ) {
			$this->addErrorMsg( [
				'smw-datavalue-languagecode-invalid',
				$mappedLanguageCode
			] );
			return;
		}

		$this->m_dataitem = new DIBlob( $mappedLanguageCode );
	}

}

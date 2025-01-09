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
 * @license GPL-2.0-or-later
 * @since 2.4
 *
 * @author mwjames
 * @reviewer thomas-topway-it
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
	 * @param string $userValue
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

		// ensure non-standard language codes are mapped to
		// their canonical form (e.g. de-x-formal to de-formal)
		$mappedLanguageCode = array_search( $languageCode, $this->nonstandardLanguageCodeMapping ) ?: $languageCode;

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

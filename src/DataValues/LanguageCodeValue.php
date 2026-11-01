<?php

namespace SMW\DataValues;

use MediaWiki\Language\LanguageCode;
use SMW\DataItems\Blob;
use SMW\Localizer\Localizer;

// phpcs:disable MediaWiki.Commenting.ClassAnnotations.UnrecognizedAnnotation

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
	 * @var array|null
	 */
	private $nonstandardLanguageCodeMapping;

	/**
	 * @param string $typeid
	 */
	public function __construct( $typeid = '' ) {
		parent::__construct( self::TYPE_ID );
		$this->nonstandardLanguageCodeMapping = LanguageCode::getNonstandardLanguageCodeMapping();
	}

	/**
	 * @see DataValue::parseUserValue
	 *
	 * @param string $userValue
	 */
	protected function parseUserValue( $userValue ): void {
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

		if ( !$this->getOption( self::OPT_QUERY_CONTEXT ) && !$this->isKnownLanguageCode( $mappedLanguageCode ) ) {
			$this->addErrorMsg( [
				'smw-datavalue-languagecode-invalid',
				$mappedLanguageCode
			] );
			return;
		}

		$this->m_dataitem = new Blob( $mappedLanguageCode );
	}

	/**
	 * MediaWiki only knows a limited set of tags, so also accept a well-formed
	 * BCP47 tag (e.g. ja-Latn-hepburn) when its primary language is known
	 */
	private function isKnownLanguageCode( string $languageCode ): bool {
		if ( Localizer::isKnownLanguageTag( $languageCode ) ) {
			return true;
		}

		return LanguageCode::isWellFormedLanguageTag( $languageCode ) &&
			Localizer::isKnownLanguageTag( explode( '-', $languageCode )[0] );
	}

}

<?php

namespace SMW\DataValues;

use Revision;
use SMW\MediaWiki\MediaWikiNsContentReader;
use Title;

/**
 * @note A controlled vocabulary is a list of terms, with terms being unambiguous,
 * and non-redundant. Vocabulary definitions adhere only a limited set of rules/constraints
 * (e.g. Type/Label)
 *
 * @license GNU GPL v2+
 * @since 2.2
 *
 * @author mwjames
 */
class ControlledVocabularyImportContentFetcher {

	/**
	 * @var MediaWikiNsContentReader
	 */
	private $mediaWikiNsContentReader;

	/**
	 * @var array
	 */
	private $importedVocabularies = array();

	/**
	 * @since 2.2
	 *
	 * @param MediaWikiNsContentReader $mediaWikiNsContentReader
	 */
	public function __construct( MediaWikiNsContentReader $mediaWikiNsContentReader ) {
		$this->mediaWikiNsContentReader = $mediaWikiNsContentReader;
	}

	/**
	 * @since 2.2
	 *
	 * @param string $namespace
	 *
	 * @return boolean
	 */
	public function contains( $namespace ) {

		if ( !isset( $this->importedVocabularies[$namespace] ) || $this->importedVocabularies[$namespace] === '' ) {
			$this->importedVocabularies[$namespace] = $this->mediaWikiNsContentReader->read( "smw_import_$namespace" );
		}

		return $this->importedVocabularies[$namespace] !== '';
	}

	/**
	 * @since 2.2
	 *
	 * @param string $namespace
	 *
	 * @return array
	 */
	public function fetchFor( $namespace ) {

		if ( !$this->contains( $namespace ) ) {
			$this->importedVocabularies[$namespace] = '';
		}

		return $this->importedVocabularies[$namespace];
	}

}

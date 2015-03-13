<?php

namespace SMW;

use Revision;
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
class ControlledVocabularyImportFetcher {

	/**
	 * @var array
	 */
	private $importedVocabularies = array();

	/**
	 * @var boolean
	 */
	private $useDatabaseForFallback = true;

	/**
	 * @since 2.2
	 *
	 * @param array $importedVocabularies
	 */
	public function __construct( array $importedVocabularies = array() ) {
		$this->importedVocabularies = $importedVocabularies;
	}

	/**
	 * @since 2.2
	 *
	 * @param string $namespace
	 *
	 * @return boolean
	 */
	public function contains( $namespace ) {

		if ( !isset( $this->importedVocabularies[ $namespace ] ) || $this->importedVocabularies[ $namespace ] === '' ) {
			$this->fetchForNamespace( $namespace );
		}

		return $this->importedVocabularies[ $namespace ] !== '';
	}

	/**
	 * @since 2.2
	 *
	 * @param string $namespace
	 *
	 * @return array
	 */
	public function fetch( $namespace ) {

		if ( !$this->contains( $namespace ) ) {
			$this->importedVocabularies[ $namespace ] = '';
		}

		return $this->importedVocabularies[ $namespace ];
	}

	private function fetchForNamespace( $namespace ) {

		$content = '';

		if ( wfMessage( "smw_import_$namespace" )->exists() ) {
			$content = wfMessage( "smw_import_$namespace" )->inContentLanguage()->text();
		}

		if ( $content === '' && $this->useDatabaseForFallback ) {
			$content = $this->tryLoadingFromDatabase( $namespace );
		}

		$this->importedVocabularies[ $namespace ] = $content;
	}

	private function tryLoadingFromDatabase( $namespace ) {

		$title = Title::makeTitle( NS_MEDIAWIKI, "Smw_import_$namespace" );

		if ( $title === null ) {
			return '';
		}

		// Revision::READ_LATEST is not specified in MW 1.19
		$revisionReadFlag = defined( 'Revision::READ_LATEST' ) ? Revision::READ_LATEST : 0;

		$revision = Revision::newFromTitle( $title, false, $revisionReadFlag );

		if ( $revision === null ) {
			return '';
		}

		if ( class_exists( 'WikitextContent' ) ) {
			return $revision->getContent()->getNativeData();
		}

		return $revision->getRawText();
	}

}

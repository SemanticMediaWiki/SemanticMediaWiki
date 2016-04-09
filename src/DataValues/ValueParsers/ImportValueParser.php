<?php

namespace SMW\DataValues\ValueParsers;

use SMW\DataValues\ControlledVocabularyImportContentFetcher;

/**
 * @private
 *
 * @license GNU GPL v2+
 * @since 2.2
 *
 * @author mwjames
 */
class ImportValueParser implements ValueParser {

	/**
	 * @var ControlledVocabularyImportContentFetcher
	 */
	private $controlledVocabularyImportContentFetcher;

	/**
	 * @var array
	 */
	private $errors = array();

	/**
	 * @since 2.2
	 *
	 * @param ControlledVocabularyImportContentFetcher $controlledVocabularyImportContentFetcher
	 */
	public function __construct( ControlledVocabularyImportContentFetcher $controlledVocabularyImportContentFetcher ) {
		$this->controlledVocabularyImportContentFetcher = $controlledVocabularyImportContentFetcher;
	}

	/**
	 * @since 2.2
	 *
	 * @return array
	 */
	public function getErrors() {
		return $this->errors;
	}

	/**
	 * @since 2.2
	 *
	 * @return array|null
	 */
	public function parse( $value ) {

		list( $namespace, $section ) = $this->tryToSplitByNamespaceSection(
			$value
		);

		if ( $this->errors !== array() ) {
			return null;
		}

		list( $uri, $name, $typelist ) = $this->doParse(
			$this->controlledVocabularyImportContentFetcher->fetchFor( $namespace )
		);

		$type = $this->checkForValidType(
			$namespace,
			$section,
			$uri,
			$typelist
		);

		if ( $this->errors !== array() ) {
			return null;
		}

		return array(
			$namespace,
			$section,
			$uri,
			$name,
			$type
		);
	}

	/**
	 * @return array|null
	 */
	private function tryToSplitByNamespaceSection( $value ) {

		if ( strpos( $value, ':' ) === false ) {

			$this->errors[] = array(
				'smw-datavalue-import-invalidvalue',
				$value
			);

			return null;
		}

		list( $namespace, $section ) = explode( ':', $value, 2 );

		// Check that elements exists for the namespace
		if ( !$this->controlledVocabularyImportContentFetcher->contains( $namespace ) ) {

			$this->errors[] = array(
				'smw-datavalue-import-unknownns',
				$namespace
			);

			return null;
		}

		return array( $namespace, $section );
	}

	/**
	 * @return array|null
	 */
	private function checkForValidType( $namespace, $section, $uri, $typelist ) {

		if ( $uri === '' ) {

			$this->errors[] = array(
				'smw-datavalue-import-missing-nsuri',
				$namespace
			);

			return null;
		}

		if ( !isset( $typelist[$section] ) ) {

			$this->errors[] = array(
				'smw-datavalue-import-missing-type',
				$section,
				$namespace
			);

			return null;
		}

		return $typelist[$section];
	}

	/**
	 * @return array|null
	 */
	private function doParse( $contents ) {

		$list = array();

		if ( $contents === '' ) {
			return null;
		}

		$importDefintions = array_map( 'trim', preg_split( "([\n][\s]?)", $contents ) );

		// Get definition from first line
		$fristLine = array_shift( $importDefintions );

		if ( strpos( $fristLine, '|' ) === false ) {
			return;
		}

		list( $uri, $name ) = explode( '|', $fristLine, 2 );

		foreach ( $importDefintions as $importDefintion ) {

			if ( strpos( $importDefintion, '|' ) === false ) {
				continue;
			}

			list( $secname, $typestring ) = explode( '|', $importDefintion, 2 );
			$list[trim( $secname )] = $typestring;
		}

		return array( $uri, $name, $list );
	}

}

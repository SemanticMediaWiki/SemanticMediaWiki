<?php

namespace SMW\DataValues\ValueParsers;

use SMW\DataValues\ImportValue;
use SMW\MediaWiki\MediaWikiNsContentReader;

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
	 * @var MediaWikiNsContentReader
	 */
	private $mediaWikiNsContentReader;

	/**
	 * @var array
	 */
	private $errors = [];

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

		list( $namespace, $section, $controlledVocabulary ) = $this->splitByNamespaceSection(
			$value
		);

		if ( $this->errors !== [] ) {
			return null;
		}

		list( $uri, $name, $typelist ) = $this->doParse(
			$controlledVocabulary
		);

		$type = $this->checkForValidType(
			$namespace,
			$section,
			$uri,
			$typelist
		);

		if ( $this->errors !== [] ) {
			return null;
		}

		return [
			$namespace,
			$section,
			$uri,
			$name,
			$type
		];
	}

	/**
	 * @return array|null
	 */
	private function splitByNamespaceSection( $value ) {

		if ( strpos( $value, ':' ) === false ) {

			$this->errors[] = [
				'smw-datavalue-import-invalid-value',
				$value
			];

			return null;
		}

		list( $namespace, $section ) = explode( ':', $value, 2 );

		/*
		 * A controlled vocabulary is a list of terms, with terms being unambiguous,
		 * and non-redundant. Vocabulary definitions adhere only a limited set of
		 * rules/constraints (e.g. Type/Label)
		 */
		$controlledVocabulary = $this->mediaWikiNsContentReader->read(
			ImportValue::IMPORT_PREFIX . $namespace
		);

		// Check that elements exists for the namespace
		if ( $controlledVocabulary === '' ) {

			$this->errors[] = [
				'smw-datavalue-import-unknown-namespace',
				$namespace
			];

			return null;
		}

		return [ $namespace, $section, $controlledVocabulary ];
	}

	/**
	 * @return array|null
	 */
	private function checkForValidType( $namespace, $section, $uri, $typelist ) {

		if ( $uri === '' ) {

			$this->errors[] = [
				'smw-datavalue-import-missing-namespace-uri',
				$namespace
			];

			return null;
		}

		if ( !isset( $typelist[$section] ) ) {

			$this->errors[] = [
				'smw-datavalue-import-missing-type',
				$section,
				$namespace
			];

			return null;
		}

		return $typelist[$section];
	}

	/**
	 * @return array|null
	 */
	private function doParse( $controlledVocabulary ) {

		$list = [];
		$importDefintions = array_map( 'trim', preg_split( "([\n][\s]?)", $controlledVocabulary ) );

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

		return [ $uri, $name, $list ];
	}

}

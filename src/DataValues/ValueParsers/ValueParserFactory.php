<?php

namespace SMW\DataValues\ValueParsers;

use SMW\ControlledVocabularyImportContentFetcher;
use SMW\MediaWiki\MediaWikiNsContentReader;

/**
 * @license GNU GPL v2+
 * @since 2.2
 *
 * @author mwjames
 */
class ValueParserFactory {

	/**
	 * @var ValueParserFactory
	 */
	private static $instance = null;

	/**
	 * @since 2.2
	 *
	 * @return self
	 */
	public static function getInstance() {

		if ( self::$instance === null ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * @since 2.2
	 */
	public static function clear() {
		self::$instance = null;
	}

	/**
	 * @since 2.2
	 *
	 * @return ImportValueParser
	 */
	public function newImportValueParser() {

		$controlledVocabularyImportContentFetcher = new ControlledVocabularyImportContentFetcher(
			new MediaWikiNsContentReader()
		);

		return new ImportValueParser( $controlledVocabularyImportContentFetcher );
	}

}

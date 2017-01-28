<?php

namespace SMW\Parser;

use SMW\Parser\Obfuscator;
use SMW\InTextAnnotationParser;

/**
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class SemanticLinksParser {

	/**
	 * @var LinksProcessor
	 */
	private $linksProcessor;

	/**
	 * @since 2.5
	 *
	 * @param LinksProcessor $linksProcessor
	 */
	public function __construct( LinksProcessor $linksProcessor ) {
		$this->linksProcessor = $linksProcessor;
	}

	/**
	 * @since 2.5
	 *
	 * @param $text
	 *
	 * @return array
	 */
	public function parse( $text ) {

		$matches = array();

		preg_match(
			$this->linksProcessor->getRegexpPattern(),
			$text,
			$matches
		);

		if ( $matches === array() ) {
			return array();
		}

		$semanticLinks = $this->linksProcessor->preprocess( $matches );

		if ( is_string( $semanticLinks ) ) {
			return array();
		}

		$semanticLinks = $this->linksProcessor->process( $semanticLinks );

		if ( is_string( $semanticLinks ) ) {
			return array();
		}

		return $semanticLinks;
	}

}

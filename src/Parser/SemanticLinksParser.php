<?php

namespace SMW\Parser;

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

		$matches = [];

		preg_match(
			$this->linksProcessor->getRegexpPattern(),
			$text,
			$matches
		);

		if ( $matches === [] ) {
			return [];
		}

		$semanticLinks = $this->linksProcessor->preprocess( $matches );

		if ( is_string( $semanticLinks ) ) {
			return [];
		}

		$semanticLinks = $this->linksProcessor->process( $semanticLinks );

		if ( is_string( $semanticLinks ) ) {
			return [];
		}

		return $semanticLinks;
	}

}

<?php

namespace SMW\MediaWiki\Hooks;

use OutputPage;
use SpecialSearch;
use SMW\Message;
use SMW\MediaWiki\Search\Search as SMWSearch;

/**
 * @see https://www.mediawiki.org/wiki/Manual:Hooks/SpecialSearchResultsPrepend
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class SpecialSearchResultsPrepend extends HookHandler {

	/**
	 * @var SpecialSearch
	 */
	private $specialSearch;

	/**
	 * @var OutputPage
	 */
	private $outputPage;

	/**
	 * @since  3.0
	 *
	 * @param SpecialSearch $specialSearch
	 * @param OutputPage &$outputPage
	 */
	public function __construct( SpecialSearch $specialSearch, OutputPage $outputPage ) {
		$this->specialSearch = $specialSearch;
		$this->outputPage = $outputPage;
	}

	/**
	 * @since 3.0
	 *
	 * @param string $term
	 *
	 * @return boolean
	 */
	public function process( $term ) {

		$html = '';

		if ( $this->specialSearch->getSearchEngine() instanceof SMWSearch ) {
			$html .= Message::get(
				'smw-search-syntax-support',
				Message::TEXT,
				Message::USER_LANGUAGE
			);
		}

		if ( $this->outputPage->getUser()->getOption( 'smw-prefs-general-options-suggester-textinput' ) ) {
			$html .= ' ' . Message::get(
				'smw-search-input-assistance',
				Message::PARSE,
				Message::USER_LANGUAGE
			);
		}

		if ( $html !== '' ) {
			$this->outputPage->addHtml(
				"<div class='smw-search-results-prepend plainlinks'>$html</div>"
			);
		}

		return true;
	}

}

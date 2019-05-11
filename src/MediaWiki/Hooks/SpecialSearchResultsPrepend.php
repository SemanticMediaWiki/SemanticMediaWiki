<?php

namespace SMW\MediaWiki\Hooks;

use Html;
use OutputPage;
use SMW\MediaWiki\Search\ExtendedSearchEngine;
use SMW\Message;
use SMW\Utils\HtmlModal;
use SpecialSearch;

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

		if ( $this->specialSearch->getSearchEngine() instanceof ExtendedSearchEngine ) {
			$this->outputPage->addModuleStyles( [  'smw.ui.styles', 'smw.special.search.styles' ] );
			$this->outputPage->addModules( [ 'smw.special.search', 'smw.ui' ] );

			$this->outputPage->addModuleStyles( HtmlModal::getModuleStyles() );
			$this->outputPage->addModules( HtmlModal::getModules() );

			$html .=  HtmlModal::link(
				'<span class="smw-icon-info" style="margin-left: -5px; padding: 10px 12px 12px 12px;"></span>',
				[
					'data-id' => 'smw-search-cheat-sheet'
				]
			);

			$html .= Message::get(
				'smw-search-syntax-support',
				Message::PARSE,
				Message::USER_LANGUAGE
			);

			if ( $this->getOption( 'prefs-suggester-textinput' ) ) {
				$html .= ' ' . Message::get(
					'smw-search-input-assistance',
					Message::PARSE,
					Message::USER_LANGUAGE
				);
			}

			$html .= HtmlModal::modal(
				Message::get( 'smw-cheat-sheet', Message::TEXT, Message::USER_LANGUAGE ),
				$this->search_sheet( $this->getOption( 'prefs-suggester-textinput' ) ),
				[
					'id' => 'smw-search-cheat-sheet',
					'class' => 'plainlinks',
					'style' => 'display:none;'
				]
			);
		}

		if ( $html !== '' && !$this->getOption( 'prefs-disable-search-info' ) ) {
			$this->outputPage->addHtml(
				"<div class='smw-search-results-prepend plainlinks'>$html</div>"
			);
		}

		return true;
	}

	private function search_sheet( $inputAssistance ) {

		$text = $this->msg( 'smw-search-help-intro' );
		$text .= $this->section( 'smw-search-input' );

		$text .= $this->msg( 'smw-search-help-structured' );
		$text .= $this->msg( 'smw-search-help-proximity' );

		if ( $inputAssistance ) {
			$text .= $this->section( 'smw-ask-input-assistance' );
			$text .= $this->msg( 'smw-search-help-input-assistance' );
		}

		$text .= $this->section( 'smw-search-syntax' );
		$text .= $this->msg( 'smw-search-help-ask' );

		return $text;
	}

	private function section( $msg, $attributes = [] ) {
		return Html::rawElement(
			'div',
			[
				'class' => 'smw-text-strike',
				'style' => 'padding: 5px 0 5px 0;'
			],
			Html::rawElement(
				'span',
				[
					'style' => 'font-size: 1.2em; margin-left:0px'
				],
				Message::get( $msg, Message::TEXT, Message::USER_LANGUAGE )
			)
		);
	}

	private function msg( $msg, $html = '', $attributes = [] ) {
		return Html::rawElement(
			'div',
			[
				'class' => $msg
			] + $attributes,
			Message::get( $msg, Message::PARSE, Message::USER_LANGUAGE ) . $html
		);
	}

}

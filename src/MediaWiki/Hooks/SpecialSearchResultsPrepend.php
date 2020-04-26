<?php

namespace SMW\MediaWiki\Hooks;

use Html;
use OutputPage;
use SMW\MediaWiki\Search\ExtendedSearchEngine;
use SMW\MediaWiki\Preference\PreferenceExaminer;
use SMW\Message;
use SMW\Utils\HtmlModal;
use SpecialSearch;
use SMW\MediaWiki\HookListener;
use SMW\OptionsAwareTrait;
use SMW\Localizer\MessageLocalizerTrait;

/**
 * @see https://www.mediawiki.org/wiki/Manual:Hooks/SpecialSearchResultsPrepend
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class SpecialSearchResultsPrepend implements HookListener {

	use OptionsAwareTrait;
	use MessageLocalizerTrait;

	/**
	 * @var PreferenceExaminer
	 */
	private $preferenceExaminer;

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
	 * @param PreferenceExaminer $preferenceExaminer
	 * @param SpecialSearch $specialSearch
	 * @param OutputPage &$outputPage
	 */
	public function __construct( PreferenceExaminer $preferenceExaminer, SpecialSearch $specialSearch, OutputPage $outputPage ) {
		$this->preferenceExaminer = $preferenceExaminer;
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

		if ( !$this->specialSearch->getSearchEngine() instanceof ExtendedSearchEngine ) {
			return true;
		}

		$this->outputPage->addModuleStyles( [ 'smw.ui.styles', 'smw.special.search.styles' ] );
		$this->outputPage->addModules( [ 'smw.special.search', 'smw.ui' ] );

		$this->outputPage->addModuleStyles( HtmlModal::getModuleStyles() );
		$this->outputPage->addModules( HtmlModal::getModules() );

		$html = HtmlModal::link(
			'<span class="smw-icon-info" style="margin-left: -5px; padding: 10px 12px 12px 12px;"></span>',
			[
				'data-id' => 'smw-search-cheat-sheet'
			]
		);

		$html .= $this->msg( 'smw-search-syntax-support', Message::PARSE );

		if ( $this->preferenceExaminer->hasPreferenceOf( GetPreferences::ENABLE_ENTITY_SUGGESTER ) ) {
			$html .= ' ' . $this->msg( 'smw-search-input-assistance', Message::PARSE );
		}

		$html .= HtmlModal::modal(
			$this->msg( 'smw-cheat-sheet' ),
			$this->search_sheet(),
			[
				'id' => 'smw-search-cheat-sheet',
				'class' => 'plainlinks',
				'style' => 'display:none;'
			]
		);

		if ( !$this->preferenceExaminer->hasPreferenceOf( GetPreferences::DISABLE_SEARCH_INFO ) ) {
			$this->outputPage->addHtml(
				"<div class='smw-search-results-prepend plainlinks'>$html</div>"
			);
		}

		return true;
	}

	private function search_sheet() {

		$text = $this->element( 'smw-search-help-intro' );
		$text .= $this->section( 'smw-search-input' );

		$text .= $this->element( 'smw-search-help-structured' );
		$text .= $this->element( 'smw-search-help-proximity' );

		if ( $this->preferenceExaminer->hasPreferenceOf( GetPreferences::ENABLE_ENTITY_SUGGESTER ) ) {
			$text .= $this->section( 'smw-ask-input-assistance' );
			$text .= $this->element( 'smw-search-help-input-assistance' );
		}

		$text .= $this->section( 'smw-search-syntax' );
		$text .= $this->element( 'smw-search-help-ask' );

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
				$this->msg( $msg )
			)
		);
	}

	private function element( $msg, $html = '', $attributes = [] ) {
		return Html::rawElement(
			'div',
			[
				'class' => $msg
			] + $attributes,
			$this->msg( $msg, Message::PARSE ) . $html
		);
	}

}

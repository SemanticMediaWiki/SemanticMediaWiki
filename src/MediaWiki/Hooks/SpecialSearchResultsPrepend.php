<?php

namespace SMW\MediaWiki\Hooks;

use MediaWiki\Hook\SpecialSearchResultsPrependHook;
use MediaWiki\Html\Html;
use MediaWiki\User\Options\UserOptionsLookup;
use SMW\Localizer\Message;
use SMW\Localizer\MessageLocalizerTrait;
use SMW\MediaWiki\Search\ExtendedSearchEngine;
use SMW\Utils\HtmlModal;

/**
 * @see https://www.mediawiki.org/wiki/Manual:Hooks/SpecialSearchResultsPrepend
 *
 * @license GPL-2.0-or-later
 * @since 3.0
 *
 * @author mwjames
 */
class SpecialSearchResultsPrepend implements SpecialSearchResultsPrependHook {

	use MessageLocalizerTrait;

	/**
	 * @since 7.0.0
	 */
	public function __construct( private readonly UserOptionsLookup $userOptionsLookup ) {
	}

	/**
	 * @since 7.0.0
	 */
	public function onSpecialSearchResultsPrepend( $specialSearch, $output, $term ) {
		if ( !$specialSearch->getSearchEngine() instanceof ExtendedSearchEngine ) {
			return true;
		}

		$user = $output->getUser();

		$output->addModuleStyles( [ 'smw.ui.styles', 'smw.special.search.styles' ] );
		$output->addModules( [ 'smw.special.search', 'smw.ui' ] );

		$output->addModuleStyles( HtmlModal::getModuleStyles() );
		$output->addModules( HtmlModal::getModules() );

		$html = HtmlModal::link(
			'<span class="smw-icon-info" style="margin-left: -5px; padding: 10px 12px 12px 12px;"></span>',
			[
				'data-id' => 'smw-search-cheat-sheet'
			]
		);

		$html .= $this->msg( 'smw-search-syntax-support', Message::PARSE );

		if ( $this->userOptionsLookup->getOption( $user, GetPreferences::ENABLE_ENTITY_SUGGESTER, false ) ) {
			$html .= ' ' . $this->msg( 'smw-search-input-assistance', Message::PARSE );
		}

		$html .= HtmlModal::modal(
			$this->msg( 'smw-cheat-sheet' ),
			$this->search_sheet( $user ),
			[
				'id' => 'smw-search-cheat-sheet',
				'class' => 'plainlinks',
				'style' => 'display:none;'
			]
		);

		if ( !$this->userOptionsLookup->getOption( $user, GetPreferences::DISABLE_SEARCH_INFO, false ) ) {
			$output->addHtml(
				"<div class='smw-search-results-prepend plainlinks'>$html</div>"
			);
		}

		return true;
	}

	private function search_sheet( $user ): string {
		$text = $this->element( 'smw-search-help-intro' );
		$text .= $this->section( 'smw-search-input' );

		$text .= $this->element( 'smw-search-help-structured' );
		$text .= $this->element( 'smw-search-help-proximity' );

		if ( $this->userOptionsLookup->getOption( $user, GetPreferences::ENABLE_ENTITY_SUGGESTER, false ) ) {
			$text .= $this->section( 'smw-ask-input-assistance' );
			$text .= $this->element( 'smw-search-help-input-assistance' );
		}

		$text .= $this->section( 'smw-search-syntax' );
		$text .= $this->element( 'smw-search-help-ask' );

		return $text;
	}

	private function section( string $msg ) {
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

	private function element( string $msg, string $html = '', array $attributes = [] ) {
		return Html::rawElement(
			'div',
			[
				'class' => $msg
			] + $attributes,
			$this->msg( $msg, Message::PARSE ) . $html
		);
	}

}

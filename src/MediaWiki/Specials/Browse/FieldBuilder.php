<?php

namespace SMW\MediaWiki\Specials\Browse;

use Html;
use SMW\Message;
use SpecialPage;

/**
 * @private
 *
 * This class should eventually be injected instead of relying on static methods,
 * for now this is the easiest way to unclutter the mammoth Browse class and
 * splitting up responsibilities.
 *
 * @license GPL-2.0-or-later
 * @since   2.5
 *
 * @author mwjames
 */
class FieldBuilder {

	/**
	 * Get the Mustache data for the query form in order to quickly switch to a specific article.
	 *
	 * @since 5.0
	 */
	public static function getQueryFormData( $articletext = '', $lang = Message::USER_LANGUAGE ): array {
		$title = SpecialPage::getTitleFor( 'Browse' );

		return [
			'button-value' => Message::get( 'smw_browse_go', Message::ESCAPED, $lang ),
			'form-action' => htmlspecialchars( $title->getLocalURL() ),
			'form-title' => $title->getPrefixedText(),
			'input-placeholder' => Message::get( 'smw_browse_article', Message::ESCAPED, $lang ),
			'input-value' => htmlspecialchars( $articletext )
		];
	}

	/**
	 * Creates the HTML for a link to this page, with some parameters set.
	 *
	 * @since 2.5
	 *
	 * @param string $linkMsg
	 * @param array $parameters
	 *
	 * @return string
	 */
	public static function createLink( $linkMsg, array $parameters, $lang = Message::USER_LANGUAGE ) {
		$title = SpecialPage::getSafeTitleFor( 'Browse' );
		$fragment = $linkMsg === 'smw_browse_show_incoming' ? '#smw_browse_incoming' : '';

		return Html::element(
			'a',
			[
				'href' => $title->getLocalURL( $parameters ) . $fragment,
				'class' => $linkMsg
			],
			Message::get( $linkMsg, Message::TEXT, $lang )
		);
	}

}

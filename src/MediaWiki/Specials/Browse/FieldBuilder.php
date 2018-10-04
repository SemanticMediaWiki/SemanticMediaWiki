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
 * @license GNU GPL v2+
 * @since   2.5
 *
 * @author mwjames
 */
class FieldBuilder {

	/**
	 * Creates the query form in order to quickly switch to a specific article.
	 *
	 * @since 2.5
	 *
	 * @return string
	 */
	public static function createQueryForm( $articletext = '' ) {

		$title = SpecialPage::getTitleFor( 'Browse' );
		$dir = $title->getPageLanguage()->isRTL() ? 'rtl' : 'ltr';

		$html = "<div class=\"smwb-form\">". Html::rawElement(
			'div',
			[ 'style' => 'margin-top:15px;' ],
			''
		);

		$html .= Html::rawElement(
			'form',
			[
				'name'   => 'smwbrowse',
				'action' => htmlspecialchars( $title->getLocalURL() ),
				'method' => 'get'
			],
			Html::rawElement(
				'input',
				[
					'type'  => 'hidden',
					'name'  => 'title',
					'value' => $title->getPrefixedText()
				],
				 Message::get( 'smw_browse_article', Message::ESCAPED, Message::USER_LANGUAGE )
			) .
			Html::rawElement(
				'div',
				[
					'class' => 'smwb-input'
				],
				Html::rawElement(
					'div',
					[
						'class' => 'input-field'
					],
					Html::rawElement(
						'input',
						[
							'type'  => 'text',
							'dir'   => $dir,
							'name'  => 'article',
							'size'  => 40,
							'id'    => 'smw-page-input',
							'class' => 'input smw-page-input autocomplete-arrow mw-ui-input',
							'value' => htmlspecialchars( $articletext )
						]
					)
				) .
				Html::rawElement(
					'div',
					[
						'class' => 'button-field'
					],
					Html::rawElement(
						'input',
						[
							'type'  => 'submit',
							'class' => 'input-button mw-ui-button',
							'value' => Message::get( 'smw_browse_go', Message::ESCAPED, Message::USER_LANGUAGE )
						]
					)
				)
			)
		);

		return $html . "</div>";
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
	public static function createLink( $linkMsg, array $parameters ) {

		$title = SpecialPage::getSafeTitleFor( 'Browse' );
		$fragment = $linkMsg === 'smw_browse_show_incoming' ? '#smw_browse_incoming' : '';

		return Html::element(
			'a',
			[
				'href' => $title->getLocalURL( $parameters ) . $fragment,
				'class' => $linkMsg
			],
			Message::get( $linkMsg, Message::TEXT, Message::USER_LANGUAGE )
		);
	}

}

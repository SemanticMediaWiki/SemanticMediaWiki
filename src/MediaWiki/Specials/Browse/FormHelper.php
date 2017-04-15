<?php

namespace SMW\MediaWiki\Specials\Browse;

use SMW\Message;
use SpecialPage;
use Html;

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
class FormHelper {

	/**
	 * Creates the query form in order to quickly switch to a specific article.
	 *
	 * @since 2.5
	 *
	 * @return string
	 */
	public static function getQueryForm( $articletext = '' ) {

		$title = SpecialPage::getTitleFor( 'Browse' );
		$dir = $title->getPageLanguage()->isRTL() ? 'rtl' : 'ltr';

		$html = "<div class=\"smwb-form\">". Html::rawElement(
			'div',
			array( 'style' => 'margin-top:15px;' ),
			''
		);

		$html .= Html::rawElement(
			'form',
			array(
				'name'   => 'smwbrowse',
				'action' => htmlspecialchars( $title->getLocalURL() ),
				'method' => 'get'
			),
			Html::rawElement(
				'input',
				array(
					'type'  => 'hidden',
					'name'  => 'title',
					'value' => $title->getPrefixedText()
				),
				 Message::get( 'smw_browse_article', Message::ESCAPED, Message::USER_LANGUAGE )
			) .
			Html::rawElement(
				'div',
				array(
					'class' => 'browse-input-resp'
				),
				Html::rawElement(
					'div',
					array(
						'class' => 'input-field'
					),
					Html::rawElement(
						'input',
						array(
							'type'  => 'text',
							'dir'   => $dir,
							'name'  => 'article',
							'size'  => 40,
							'id'    => 'smwb-page-search',
							'class' => 'input mw-ui-input',
							'value' => htmlspecialchars( $articletext )
						)
					)
				) .
				Html::rawElement(
					'div',
					array(
						'class' => 'button-field'
					),
					Html::rawElement(
						'input',
						array(
							'type'  => 'submit',
							'class' => 'input-button mw-ui-button',
							'value' => Message::get( 'smw_browse_go', Message::ESCAPED, Message::USER_LANGUAGE )
						)
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
	public static function createLinkFromMessage( $linkMsg, array $parameters ) {

		$title = SpecialPage::getSafeTitleFor( 'Browse' );
		$fragment = $linkMsg === 'smw_browse_show_incoming' ? '#smw_browse_incoming' : '';

		return Html::element(
			'a',
			array(
				'href' => $title->getLocalURL( $parameters ) . $fragment
			),
			Message::get( $linkMsg, Message::TEXT, Message::USER_LANGUAGE )
		);
	}

}

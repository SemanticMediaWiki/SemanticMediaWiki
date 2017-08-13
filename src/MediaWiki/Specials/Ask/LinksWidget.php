<?php

namespace SMW\MediaWiki\Specials\Ask;

use Html;
use SMWInfolink as Infolink;
use SMW\Message;
use Title;

/**
 * @license GNU GPL v2+
 * @since   2.5
 *
 * @author mwjames
 */
class LinksWidget {

	/**
	 * @return array
	 */
	public static function getModules() {
		return [ 'onoi.clipboard' ];
	}

	/**
	 * @since 2.5
	 *
	 * @param boolean $isEmpty
	 *
	 * @return string
	 */
	public static function embeddedCodeLink( $isEmpty = false ) {

		if ( $isEmpty ) {
			return '';
		}

		//show|hide inline embed code
		$embedShow = "document.getElementById('inlinequeryembed').style.display='block';" .
			"document.getElementById('embed_hide').style.display='inline';" .
			"document.getElementById('embed_show').style.display='none';" .
			"document.getElementById('inlinequeryembedarea').select();";

		$embedHide = "document.getElementById('inlinequeryembed').style.display='none';" .
			"document.getElementById('embed_show').style.display='inline';" .
			"document.getElementById('embed_hide').style.display='none';";

		return Html::rawElement( 'span', [ 'class' => 'smw-ask-button smw-ask-button-lblue' ], Html::rawElement(
			'span',
			array(
				'id'  => 'embed_show'
			), Html::rawElement(
				'a',
				array(
					'href'  => '#embed_show',
					'rel'   => 'nofollow',
					'onclick' => $embedShow
				), wfMessage( 'smw_ask_show_embed' )->escaped()
			)
		) . Html::rawElement(
			'span',
			array(
				'id'  => 'embed_hide',
				'style'  => 'display: none;'
			), Html::rawElement(
				'a',
				array(
					'href'  => '#embed_hide',
					'rel'   => 'nofollow',
					'onclick' => $embedHide
				), wfMessage( 'smw_ask_hide_embed' )->escaped()
			)
		) );
	}

	/**
	 * @since 2.5
	 *
	 * @param string $code
	 *
	 * @return string
	 */
	public static function embeddedCodeBlock( $code ) {

		return Html::rawElement(
			'div',
			array(
				'id'  => 'inlinequeryembed',
				'style' => 'display: none'
			), Html::rawElement(
				'div',
				array(
					'id' => 'inlinequeryembedinstruct'
				), wfMessage( 'smw_ask_embed_instr' )->escaped()
			) . Html::rawElement(
				'textarea',
				array(
					'id' => 'inlinequeryembedarea',
					'readonly' => 'yes',
					'cols' => 20,
					'rows' => substr_count( $code, "\n" ) + 1,
					'onclick' => 'this.select()'
				), $code
			)
		);
	}

	/**
	 * @since 2.5
	 *
	 * @param boolean $isEmpty
	 *
	 * @return string
	 */
	public static function resultSubmitLink( $isEmpty = false ) {

		if ( !$isEmpty ) {
			return '';
		}

		return Html::rawElement( 'span', [ 'class' => 'smw-ask-button smw-ask-button-dblue' ], Html::element(
			'input',
			array(
				'type'  => 'submit',
				'class' => '',
				'value' => wfMessage( 'smw_ask_submit' )->escaped()
			), ''
		) . ' ' . Html::element(
			'input',
			array(
				'type'  => 'hidden',
				'name'  => 'eq',
				'value' => 'yes'
			), ''
		) );
	}

	/**
	 * @since 2.5
	 *
	 * @param Title $title
	 * @param string $urlTail
	 * @param boolean $hideForm
	 * @param boolean $isEmpty
	 *
	 * @return string
	 */
	public static function showHideLink( Title $title, $urlTail = '', $hideForm = false, $isEmpty = false ) {

		if ( $isEmpty ) {
			return '';
		}

		return Html::rawElement(
			'span',
			[
				'class' => 'smw-ask-button smw-ask-button-lblue'
			], Html::element(
				'a',
				[
					'href'  => $title->getLocalURL( $urlTail ),
					'rel'   => 'nofollow'
				],
				wfMessage( ( $hideForm ? 'smw_ask_hidequery' : 'smw_ask_editquery' ) )->text()
			)
		);
	}

	/**
	 * @since 3.0
	 *
	 * @param Title $title
	 * @param string $urlTail
	 * @param boolean $isEmpty
	 *
	 * @return string
	 */
	public static function debugLink( Title $title, $urlTail = '', $isEmpty = false ) {

		if ( $isEmpty ) {
			return '';
		}

		return Html::rawElement(
			'span',
			[
				'class' => 'smw-ask-button smw-ask-button-right'
			],
			Html::element(
				'a',
				[
					'class' => '',
					'href'  => $title->getLocalURL( $urlTail . '&debug=true&eq=yes#search' ),
					'rel'   => 'nofollow'
				],
				Message::get( 'smw-ask-debug', Message::TEXT, Message::USER_LANGUAGE )
			)
		);
	}

	/**
	 * @since 2.5
	 *
	 * @param Infolink|null $infolink
	 *
	 * @return string
	 */
	public static function clipboardLink( Infolink $infolink = null ) {

		if ( $infolink === null ) {
			return '';
		}

		return Html::rawElement(
			'span',
			[
				'class' => 'smw-ask-button smw-ask-button-right smw-ask-button-lgrey'
			],
			Html::element(
				'a',
				[
					'data-clipboard-action' => 'copy',
					'data-clipboard-target' => '.clipboard',
					'data-onoi-clipboard-field' => 'value',
					'class' => 'clipboard',
					'value' => $infolink->getURL(),
					'title' =>  wfMessage( 'smw-clipboard-copy-link' )->text()
				],
				'⧟'
			)
		);
	}

}

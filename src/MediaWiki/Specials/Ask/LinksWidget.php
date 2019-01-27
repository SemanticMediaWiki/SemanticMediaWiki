<?php

namespace SMW\MediaWiki\Specials\Ask;

use Html;
use SMW\Message;
use SMWInfolink as Infolink;
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
	 * @since 3.0
	 *
	 * @param string $html
	 *
	 * @return string
	 */
	public static function fieldset( $html = '' ) {

		$html = '<p></p>' . $html;

		return Html::rawElement(
			'fieldset',
			[],
			Html::rawElement(
				'legend',
				[],
				Message::get( 'smw-ask-search', Message::TEXT, Message::USER_LANGUAGE )
			) . $html
		);
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

		return Html::rawElement(
			'span',
			[
				'id' => 'ask-embed',
				'class' => 'smw-ask-button smw-ask-button-lblue'
			],
			Html::rawElement(
				'span',
				[
					'id'  => 'embed_show'
				], Html::rawElement(
					'a',
					[
						'href'  => '#embed_show',
						'rel'   => 'nofollow',
						'onclick' => $embedShow
					], wfMessage( 'smw_ask_show_embed' )->escaped()
				)
			) . Html::rawElement(
				'span',
				[
					'id'  => 'embed_hide',
					'style'  => 'display: none;'
				], Html::rawElement(
					'a',
					[
						'href'  => '#embed_hide',
						'rel'   => 'nofollow',
						'onclick' => $embedHide
					], wfMessage( 'smw_ask_hide_embed' )->escaped()
				)
			)
		);
	}

	/**
	 * @since 3.0
	 *
	 * @param string $href
	 *
	 * @return string
	 */
	public static function editLink( $href ) {
		return Html::rawElement(
				'a',
				[
					'href'  => $href . '#search',
					'rel'   => 'href',
					'style' => 'display:block; width:60px'
				],
				Html::rawElement(
				'span',
				[
					'class' => 'smw-icon-pen',
					'title' => wfMessage( 'smw_ask_editquery' )->text(),
				],
				''
			)
		);
	}

	/**
	 * @since 3.0
	 *
	 * @param string $href
	 *
	 * @return string
	 */
	public static function hideLink( $href ) {
		return Html::rawElement(
				'a',
				[
					'href'  => $href,
					'rel'   => 'nofollow',
					'style' => 'display:block; width:60px'
				],
				Html::rawElement(
				'span',
				[
					'class' => 'smw-icon-compact',
					'title' => wfMessage( 'smw_ask_hidequery' )->text()
				],
				''
			)
		);
	}

	/**
	 * @since 2.5
	 *
	 * @param string $code
	 *
	 * @return string
	 */
	public static function embeddedCodeBlock( $code, $raw = false ) {

		$code = Html::rawElement(
			'pre',
			[
				'id' => 'inlinequeryembedarea',
				'readonly' => 'yes',
				'cols' => 20,
				'rows' => substr_count( $code, "\n" ) + 1,
				'onclick' => 'this.select()'
			],
			$code
		);

		if ( $raw ) {
			return '<p>' . wfMessage( 'smw_ask_embed_instr' )->escaped() . '</p>' . $code;
		}

		return Html::rawElement(
			'div',
			[
				'id'  => 'inlinequeryembed',
				'style' => 'display: none;'
			], Html::rawElement(
				'div',
				[
					'id' => 'inlinequeryembedinstruct'
				], wfMessage( 'smw_ask_embed_instr' )->escaped()
			) . $code
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

		return  Html::rawElement(
			'div',
				[
					'id' => 'ask-change-info'
				]
			) . Html::rawElement(
			'div',
			[
				'class' => 'smw-ask-button-submit'
			], Html::element(
				'input',
				[
					'id' => 'search-action',
					'type'  => 'submit',
					'value' => wfMessage( 'smw_ask_submit' )->escaped()
				]
			) . Html::element(
				'input',
				[
					'type'  => 'hidden',
					'name'  => 'eq',
					'value' => 'yes'
				]
			)
		);
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
	public static function showHideLink( Title $title, UrlArgs $urlArgs, $hideForm = false, $isEmpty = false ) {

		if ( $isEmpty || $hideForm === false ) {
			return '';
		}

		return Html::rawElement(
			'span',
			[
				'id' => 'ask-showhide',
				'class' => 'smw-ask-button smw-ask-button-lblue'
			], Html::element(
				'a',
				[
					'href'  => $title->getLocalURL( $urlArgs ),
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
	public static function debugLink( Title $title, UrlArgs $urlArgs, $isEmpty = false, $raw = false ) {

		if ( $isEmpty ) {
			return '';
		}

		$urlArgs->set( 'eq', 'yes' );
		$urlArgs->set( 'debug', 'true' );
		$urlArgs->setFragment( 'search' );

		$link = Html::element(
			'a',
			[
				'class' => '',
				'href'  => $title->getLocalURL( $urlArgs ),
				'rel'   => 'nofollow',
				'title' => Message::get( 'smw-ask-debug-desc', Message::TEXT, Message::USER_LANGUAGE )
			],
			$raw ? Message::get( 'smw-ask-debug', Message::TEXT, Message::USER_LANGUAGE ) : 'ℹ'
		);

		if ( $raw ) {
			return $link;
		}

		return Html::rawElement(
			'span',
			[
				'id' => 'ask-debug',
				'class' => 'smw-ask-button smw-ask-button-right',
				'title' => Message::get( 'smw-ask-debug-desc', Message::TEXT, Message::USER_LANGUAGE )
			],
			$link
		);
	}

	/**
	 * @since 3.0
	 *
	 * @param Title $title
	 * @param string $urlTail
	 * @param boolean $isFromCache
	 *
	 * @return string
	 */
	public static function noQCacheLink( Title $title, UrlArgs $urlArgs, $isFromCache = false ) {

		if ( $isFromCache === false ) {
			return '';
		}

		$urlArgs->set( 'cache', 'no' );
		$urlArgs->delete( 'debug' );

		$urlArgs->setFragment( 'search' );

		return Html::rawElement(
			'span',
			[
				'id' => 'ask-cache',
				'class' => '',
				'title' => Message::get( 'smw-ask-no-cache-desc', Message::TEXT, Message::USER_LANGUAGE )
			],
			Html::element(
				'a',
				[
					'class' => '',
					'href'  => $title->getLocalURL( $urlArgs ),
					'rel'   => 'nofollow'
				],
				Message::get( 'smw-ask-no-cache', Message::TEXT, Message::USER_LANGUAGE )
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
				'id' => 'ask-clipboard ',
			//	'class' => 'smw-ask-button smw-ask-button-right smw-ask-button-lgrey'
			],
			Html::element(
				'a',
				[
					'data-clipboard-action' => 'copy',
					'data-clipboard-target' => '.clipboard',
					'data-onoi-clipboard-field' => 'value',
					'class' => 'clipboard smw-icon-bookmark',
					'value' => $infolink->getURL(),
					'title' =>  wfMessage( 'smw-clipboard-copy-link' )->text()
				]
			)
		);
	}

}

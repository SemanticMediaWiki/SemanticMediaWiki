<?php

namespace SMW\MediaWiki\Specials\Ask;

use Html;
use SMWInfolink as Infolink;
use Title;

/**
 * @license GNU GPL v2+
 * @since   2.5
 *
 * @author mwjames
 */
class InputFormWidget {

	/**
	 * @var array
	 */
	private $resourceModules = array();

	/**
	 * @since 2.5
	 *
	 * @return string $resourceModule
	 */
	public function addResourceModule( $resourceModule ) {
		$this->resourceModules[] = $resourceModule;
	}

	/**
	 * @return array
	 */
	public function getResourceModules() {
		return $this->resourceModules;
	}

	/**
	 * @since 2.5
	 *
	 * @return string
	 */
	public function createEmbeddedCodeLinkElement( $isEmpty = false ) {

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
	 * @return string
	 */
	public function createEmbeddedCodeElement( $code ) {
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
	public function createFindResultLinkElement( $isEmpty = false ) {

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
	 *
	 * @return string
	 */
	public function createShowHideLinkElement( Title $title, $urlTail = '', $hideForm = false, $isEmpty = false ) {

		if ( $isEmpty ) {
			return '';
		}

		return Html::rawElement( 'span', [ 'class' => 'smw-ask-button smw-ask-button-lblue' ], Html::element(
			'a',
			array(
				'href'  => $title->getLocalURL( $urlTail ),
				'rel'   => 'nofollow'
			), wfMessage( ( $hideForm ? 'smw_ask_hidequery' : 'smw_ask_editquery' ) )->text()
		) );
	}

	/**
	 * @since 3.0
	 *
	 * @param Title $title
	 * @param string $urlTail
	 * @param boolean $hideForm
	 *
	 * @return string
	 */
	public function createDebugLinkElement( Title $title, $urlTail = '', $isEmpty = false ) {

		if ( $isEmpty ) {
			return '';
		}

		return Html::rawElement( 'span', [ 'class' => 'smw-ask-button smw-ask-button-right' ], Html::element(
			'a',
			array(
				'class' => '',
				'href'  => $title->getLocalURL( $urlTail . '&debug=true&eq=yes' ),
				'rel'   => 'nofollow'
			), 'Debug'
		) );
	}

	/**
	 * @since 2.5
	 *
	 * @param Infolink|null $infolink
	 *
	 * @return string
	 */
	public function createClipboardLinkElement( Infolink $infolink = null ) {

		if ( $infolink === null ) {
			return '';
		}

		$this->addResourceModule( 'onoi.clipboard' );

		return Html::rawElement( 'span', [ 'class' => 'smw-ask-button smw-ask-button-right smw-ask-button-lgrey' ], Html::element(
			'a',
			array(
				'data-clipboard-action' => 'copy',
				'data-clipboard-target' => '.clipboard',
				'data-onoi-clipboard-field' => 'value',
				'class' => 'clipboard',
				'value' => $infolink->getURL(),
				'title' =>  wfMessage( 'smw-clipboard-copy-link' )->text()
			), '⧟'
		) );
	}

}

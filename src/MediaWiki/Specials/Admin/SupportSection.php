<?php

namespace SMW\MediaWiki\Specials\Admin;

use SMW\ApplicationFactory;
use SMW\MediaWiki\Renderer\HtmlFormRenderer;
use SMW\Message;
use Html;

/**
 * @license GNU GPL v2+
 * @since   2.5
 *
 * @author mwjames
 */
class SupportSection {

	/**
	 * @var HtmlFormRenderer
	 */
	private $htmlFormRenderer;

	/**
	 * @since 2.5
	 *
	 * @param HtmlFormRenderer $htmlFormRenderer
	 */
	public function __construct( HtmlFormRenderer $htmlFormRenderer ) {
		$this->htmlFormRenderer = $htmlFormRenderer;
	}

	/**
	 * @since 2.5
	 *
	 * @return string
	 */
	public function getForm() {

		$html = $this->htmlFormRenderer
			->setName( 'announce' )
			->setMethod( 'get' )
			->setActionUrl( 'https://wikiapiary.com/wiki/WikiApiary:Semantic_MediaWiki_Registry' )
			->addHeader( 'h2', Message::get('smw_smwadmin_announce', Message::TEXT, Message::USER_LANGUAGE ) )
			->addParagraph( Message::get( 'smw_smwadmin_announce_text', Message::TEXT, Message::USER_LANGUAGE ) )
			->addSubmitButton( Message::get( 'smw_smwadmin_announce', Message::TEXT, Message::USER_LANGUAGE ) )
			->getForm();

		$html .= Html::element( 'p', array(), '' );

		$html .= $this->htmlFormRenderer
			->setName( 'support' )
			->addHeader( 'h2', Message::get('smw_smwadmin_support', Message::TEXT, Message::USER_LANGUAGE ) )
			->addParagraph( Message::get( 'smw_smwadmin_supportdocu', Message::TEXT, Message::USER_LANGUAGE ) )
			->addParagraph(
				Html::rawElement( 'ul', array(),
					Html::rawElement( 'li', array(), Message::get( 'smw_smwadmin_installfile', Message::TEXT, Message::USER_LANGUAGE ) ) .
					Html::rawElement( 'li', array(), Message::get( 'smw_smwadmin_smwhomepage', Message::TEXT, Message::USER_LANGUAGE ) ) .
					Html::rawElement( 'li', array(), Message::get( 'smw_smwadmin_mediazilla', Message::TEXT, Message::USER_LANGUAGE ) ) .
					Html::rawElement( 'li', array(), Message::get( 'smw_smwadmin_questions', Message::TEXT, Message::USER_LANGUAGE ) )
				) )
			->getForm();

		return $html;
	}

}

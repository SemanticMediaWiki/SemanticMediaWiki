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
class SupportWidget {

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
			->addHeader( 'h2', $this->getMessage( 'smw-admin-announce' ) )
			->addParagraph( $this->getMessage( 'smw-admin-announce-text' ) )
			->addSubmitButton(
				$this->getMessage( 'smw-admin-announce' ),
				array(
					'class' => ''
				)
			)
			->getForm();

		$html .= Html::element( 'p', array(), '' );

		$html .= $this->htmlFormRenderer
			->setName( 'support' )
			->addHeader( 'h2', $this->getMessage('smw-admin-support' ) )
			->addParagraph( $this->getMessage( 'smw-admin-supportdocu' ) )
			->addParagraph(
				Html::rawElement( 'ul', array(),
					Html::rawElement( 'li', array(), $this->getMessage( 'smw-admin-installfile' ) ) .
					Html::rawElement( 'li', array(), $this->getMessage( 'smw-admin-smwhomepage' ) ) .
					Html::rawElement( 'li', array(), $this->getMessage( 'smw-admin-bugsreport' ) ) .
					Html::rawElement( 'li', array(), $this->getMessage( 'smw-admin-questions' ) )
				) )
			->getForm();

		return Html::rawElement( 'div', array(), $html );
	}

	private function getMessage( $key, $type = Message::TEXT ) {
		return Message::get( $key, $type, Message::USER_LANGUAGE );
	}

}

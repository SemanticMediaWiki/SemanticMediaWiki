<?php

namespace SMW\MediaWiki\Specials\Admin;

use SMW\ApplicationFactory;
use SMW\MediaWiki\Renderer\HtmlFormRenderer;
use SMW\Message;
use WebRequest;
use Html;

/**
 * @license GNU GPL v2+
 * @since   2.5
 *
 * @author mwjames
 */
class SupportListTaskHandler extends TaskHandler {

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
	 * {@inheritDoc}
	 */
	public function isTaskFor( $task ) {
		return $false;
	}

	/**
	 * @since 2.5
	 *
	 * {@inheritDoc}
	 */
	public function getHtml() {

		$html = $this->htmlFormRenderer
			->setName( 'announce' )
			->setMethod( 'get' )
			->setActionUrl( 'https://wikiapiary.com/wiki/WikiApiary:Semantic_MediaWiki_Registry' )
			->addHeader( 'h2', $this->getMessageAsString( 'smw-admin-announce' ) )
			->addParagraph( $this->getMessageAsString( 'smw-admin-announce-text' ) )
			->addSubmitButton(
				$this->getMessageAsString( 'smw-admin-announce' ),
				array(
					'class' => ''
				)
			)
			->getForm();

		$html .= Html::element( 'p', array(), '' );

		$html .= $this->htmlFormRenderer
			->setName( 'support' )
			->addHeader( 'h2', $this->getMessageAsString('smw-admin-support' ) )
			->addParagraph( $this->getMessageAsString( 'smw-admin-supportdocu' ) )
			->addParagraph(
				Html::rawElement( 'ul', array(),
					Html::rawElement( 'li', array(), $this->getMessageAsString( 'smw-admin-installfile' ) ) .
					Html::rawElement( 'li', array(), $this->getMessageAsString( 'smw-admin-smwhomepage' ) ) .
					Html::rawElement( 'li', array(), $this->getMessageAsString( 'smw-admin-bugsreport' ) ) .
					Html::rawElement( 'li', array(), $this->getMessageAsString( 'smw-admin-questions' ) )
				) )
			->getForm();

		return Html::rawElement( 'div', array(), $html );
	}

	/**
	 * @since 2.5
	 *
	 * {@inheritDoc}
	 */
	public function handleRequest( WebRequest $webRequest ) {
	}

}

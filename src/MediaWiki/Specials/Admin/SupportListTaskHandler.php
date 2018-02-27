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
	 * @since 3.0
	 *
	 * {@inheritDoc}
	 */
	public function getSection() {
		return self::SECTION_SUPPORT;
	}

	/**
	 * @since 3.0
	 *
	 * {@inheritDoc}
	 */
	public function hasAction() {
		return false;
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

		$html = $this->createSupportForm() . $this->createRegistryForm();
		$html .= Html::element( 'p', array(), '' );

		return Html::rawElement( 'div', array(), $html );
	}

	/**
	 * @since 2.5
	 *
	 * @return string
	 */
	public function createSupportForm() {
		$this->htmlFormRenderer
			->setName( 'support' )
			->addHeader( 'h3', $this->getMessageAsString('smw-admin-support' ) )
			->addParagraph( $this->getMessageAsString( 'smw-admin-supportdocu' ) )
			->addParagraph(
				Html::rawElement( 'ul', array(),
					Html::rawElement( 'li', array(), $this->getMessageAsString( 'smw-admin-installfile' ) ) .
					Html::rawElement( 'li', array(), $this->getMessageAsString( 'smw-admin-smwhomepage' ) ) .
					Html::rawElement( 'li', array(), $this->getMessageAsString( 'smw-admin-bugsreport' ) ) .
					Html::rawElement( 'li', array(), $this->getMessageAsString( 'smw-admin-questions' ) )
				)
			);

		return $this->htmlFormRenderer->getForm();
	}

	/**
	 * @since 2.5
	 *
	 * @return string
	 */
	public function createRegistryForm() {

		$this->htmlFormRenderer
			->setName( 'announce' )
			->setMethod( 'get' )
			->setActionUrl( 'https://wikiapiary.com/wiki/WikiApiary:Semantic_MediaWiki_Registry' )
			->addHeader( 'h3', $this->getMessageAsString( 'smw-admin-announce' ) )
			->addParagraph( $this->getMessageAsString( 'smw-admin-announce-text' ) )
			->addSubmitButton(
				$this->getMessageAsString( 'smw-admin-announce' ),
				array(
					'class' => ''
				)
			);

		return $this->htmlFormRenderer->getForm();
	}

	/**
	 * @since 2.5
	 *
	 * {@inheritDoc}
	 */
	public function handleRequest( WebRequest $webRequest ) {
	}

}

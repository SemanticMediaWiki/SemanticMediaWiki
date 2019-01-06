<?php

namespace SMW\MediaWiki\Specials\Admin;

use Html;
use SMW\MediaWiki\Renderer\HtmlFormRenderer;
use WebRequest;

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
		$html .= Html::element( 'p', [], '' );

		return Html::rawElement( 'div', [], $html );
	}

	/**
	 * @since 2.5
	 *
	 * @return string
	 */
	public function createSupportForm() {
		$this->htmlFormRenderer
			->setName( 'support' )
			->addHeader( 'h3', $this->msg('smw-admin-support' ) )
			->addParagraph( $this->msg( 'smw-admin-supportdocu' ) )
			->addParagraph(
				Html::rawElement( 'ul', [],
					Html::rawElement( 'li', [], $this->msg( 'smw-admin-installfile' ) ) .
					Html::rawElement( 'li', [], $this->msg( 'smw-admin-smwhomepage' ) ) .
					Html::rawElement( 'li', [], $this->msg( 'smw-admin-bugsreport' ) ) .
					Html::rawElement( 'li', [], $this->msg( 'smw-admin-questions' ) )
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
			->addHeader( 'h3', $this->msg( 'smw-admin-announce' ) )
			->addParagraph( $this->msg( 'smw-admin-announce-text' ) )
			->addSubmitButton(
				$this->msg( 'smw-admin-announce' ),
				[
					'class' => ''
				]
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

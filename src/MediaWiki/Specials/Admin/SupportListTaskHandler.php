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
		return false;
	}

	/**
	 * @since 2.5
	 *
	 * {@inheritDoc}
	 */
	public function getHtml() {

		$html = Html::rawElement(
			'p',
			[],
			$this->msg( 'smw-admin-docu' )
		);

		$html .= $this->ennvironmentSection();
		$html .= $this->supportForm();
		$html .= $this->registryForm();

		return $html;
	}

	/**
	 * @since 2.5
	 *
	 * {@inheritDoc}
	 */
	public function handleRequest( WebRequest $webRequest ) {}

	private function ennvironmentSection() {

		$info = $this->getStore()->getInfo() + [
			'smw' => SMW_VERSION,
			'mediawiki' => MW_VERSION
		] + (
			defined( 'HHVM_VERSION' ) ? [ 'hhvm' => HHVM_VERSION ] : [ 'php' => PHP_VERSION ]
		);

		return Html::rawElement(
			'h3',
			[],
			$this->msg( 'smw-admin-environment' )
		) . Html::rawElement(
			'pre',
			[],
			json_encode( $info, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE )
		);
	}

	private function supportForm() {
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

	private function registryForm() {

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

}

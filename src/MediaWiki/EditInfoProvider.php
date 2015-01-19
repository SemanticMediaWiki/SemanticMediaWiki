<?php

namespace SMW\MediaWiki;

use Revision;
use User;
use WikiPage;

/**
 * @license GNU GPL v2+
 * @since 2.0
 *
 * @author mwjames
 */
class EditInfoProvider {

	/**
	 * @var WikiPage
	 */
	private $wikiPage = null;

	/**
	 * @var Revision
	 */
	private $revision = null;

	/**
	 * @var User
	 */
	private $user = null;

	/**
	 * @var ParserOutput
	 */
	private $parserOutput = null;

	/**
	 * @since 1.9
	 *
	 * @param WikiPage $wikiPage
	 * @param Revision $revision
	 * @param User|null $user
	 */
	public function __construct( WikiPage $wikiPage, Revision $revision, User $user = null ) {
		$this->wikiPage = $wikiPage;
		$this->revision = $revision;
		$this->user = $user;
	}

	/**
	 * @since 2.0
	 *
	 * @return ParserOutput|null
	 */
	public function getOutput() {
		return $this->parserOutput;
	}

	/**
	 * @since 2.0
	 *
	 * @return EditInfoProvider
	 */
	public function fetchEditInfo() {

		$editInfo = $this->hasContentForEditMethod() ? $this->prepareContentForEdit() : $this->prepareTextForEdit();

		$this->parserOutput = isset( $editInfo->output ) ? $editInfo->output : null;

		return $this;
	}

	/**
	 * FIXME MW 1.21-
	 */
	protected function hasContentForEditMethod() {
		return method_exists( 'WikiPage', 'prepareContentForEdit' );
	}

	private function prepareContentForEdit() {
		$content  = $this->revision->getContent();

		return $this->wikiPage->prepareContentForEdit(
			$content,
			null,
			$this->user,
			$content->getContentHandler()->getDefaultFormat()
		);
	}

	private function prepareTextForEdit() {
		return $this->wikiPage->prepareTextForEdit(
			$this->revision->getRawText(),
			null,
			$this->user
		);
	}

}

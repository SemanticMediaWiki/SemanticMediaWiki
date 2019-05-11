<?php

namespace SMW\MediaWiki;

use Revision;
use SMW\ParserData;
use User;
use WikiPage;

/**
 * @license GNU GPL v2+
 * @since 2.0
 *
 * @author mwjames
 */
class EditInfo {

	/**
	 * @var WikiPage
	 */
	private $page;

	/**
	 * @var Revision
	 */
	private $revision;

	/**
	 * @var User
	 */
	private $user;

	/**
	 * @var ParserOutput
	 */
	private $parserOutput;

	/**
	 * @since 1.9
	 *
	 * @param WikiPage $page
	 * @param Revision $revision
	 * @param User|null $user
	 */
	public function __construct( WikiPage $page, Revision $revision = null, User $user = null ) {
		$this->page = $page;
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
	 * @since 2.5
	 *
	 * @return SemanticData|null
	 */
	public function fetchSemanticData() {

		$parserOutput = $this->fetchEditInfo()->getOutput();

		if ( $parserOutput === null ) {
			return null;
		}

		return $parserOutput->getExtensionData( ParserData::DATA_ID );
	}

	/**
	 * @since 2.0
	 *
	 * @return EditInfoProvider
	 */
	public function fetchEditInfo() {

		if ( $this->page !== null && $this->revision === null ) {
			$this->revision = $this->page->getRevision();
		}

		if ( !$this->revision instanceof Revision ) {
			return $this;
		}

		$content = $this->revision->getContent();

		$prepareEdit = $this->page->prepareContentForEdit(
			$content,
			null,
			$this->user,
			$content->getContentHandler()->getDefaultFormat()
		);

		// #3943
		// https://github.com/wikimedia/mediawiki/commit/fdbb64f3546e6fda0ee0ce003467b4cfb13a090f
		if ( method_exists( $prepareEdit, 'getOutput' ) ) {
			$this->parserOutput = $prepareEdit->getOutput();
		} else {
			$this->parserOutput = isset( $prepareEdit->output ) ? $prepareEdit->output : null;
		}

		return $this;
	}

}

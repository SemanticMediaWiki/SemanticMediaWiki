<?php

namespace SMW\MediaWiki;

use MediaWiki\Revision\RevisionRecord;
use MediaWiki\Revision\SlotRecord;
use ParserOutput;
use SMW\ParserData;
use SMW\SemanticData;
use User;
use WikiPage;

/**
 * @license GNU GPL v2+
 * @since 2.0
 *
 * @author mwjames
 */
class EditInfo {

	use RevisionGuardAwareTrait;

	/**
	 * @var WikiPage
	 */
	private $page;

	/**
	 * @var RevisionRecord|null
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
	 */
	public function __construct( WikiPage $page, ?RevisionRecord $revision, User $user ) {
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
	 */
	public function fetchEditInfo() : self {

		if ( $this->page !== null && $this->revision === null ) {
			$this->revision = $this->revisionGuard->newRevisionFromPage( $this->page );
		}

		if ( !$this->revision instanceof RevisionRecord ) {
			return $this;
		}

		$content = $this->revision->getContent( SlotRecord::MAIN );

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

<?php

namespace SMW\MediaWiki;

use MediaWiki\Parser\ParserOutput;
use MediaWiki\Revision\RevisionRecord;
use MediaWiki\Revision\SlotRecord;
use MediaWiki\User\User;
use SMW\DataModel\SemanticData;
use SMW\ParserData;
use WikiPage;

/**
 * @license GPL-2.0-or-later
 * @since 2.0
 *
 * @author mwjames
 */
class EditInfo {

	use RevisionGuardAwareTrait;

	/**
	 * @var ParserOutput
	 */
	private $parserOutput;

	/**
	 * @since 1.9
	 */
	public function __construct(
		private WikiPage $page,
		private ?RevisionRecord $revision,
		private User $user,
	) {
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
	public function fetchEditInfo(): self {
		if ( $this->revision === null ) {
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

		$this->parserOutput = $prepareEdit->getOutput();

		return $this;
	}

}

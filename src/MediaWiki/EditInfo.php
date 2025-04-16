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
 * @license GPL-2.0-or-later
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
	 * @var array
	 */
	private $extraSemanticSlots;

	/**
	 * @since 1.9
	 */
	public function __construct( WikiPage $page, ?RevisionRecord $revision, User $user, array $extraSemanticSlots = [] ) {
		$this->page = $page;
		$this->revision = $revision;
		$this->user = $user;
		$this->extraSemanticSlots = $extraSemanticSlots;
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

		$this->parserOutput = $prepareEdit->getOutput();

		if ( $this->parserOutput !== null ) {
			$this->combineSlotOutput();
		}

		return $this;
	}

	private function combineSlotOutput() {
		foreach ( $this->extraSemanticSlots as $semanticSlot ) {
			if ( !$this->revision->hasSlot( $semanticSlot ) || $semanticSlot === SlotRecord::MAIN ) {
				continue;
			}

			$content = $this->revision->getContent( $semanticSlot );

			if ( $content === null ) {
				continue;
			}

			$prepareEdit = $this->page->prepareContentForEdit(
				$content,
				null,
				$this->user,
				$content->getContentHandler()->getDefaultFormat()
			);

			if ( method_exists( $prepareEdit, 'getOutput' ) ) {
				$parserOutput = $prepareEdit->getOutput();
			} else {
				$parserOutput = isset( $prepareEdit->output ) ? $prepareEdit->output : null;
			}

			if ( $parserOutput === null ) {
				continue;
			}

			$slotSemanticData = $parserOutput->getExtensionData( ParserData::DATA_ID );

			if ( $slotSemanticData !== null ) {
				$semanticData = $this->parserOutput->getExtensionData( ParserData::DATA_ID );

				if ( $semanticData === null ) {
					$this->parserOutput->setExtensionData( ParserData::DATA_ID, $slotSemanticData );
				} else {
					$semanticData->importDataFrom( $slotSemanticData );
				}
			}
		}
	}

}

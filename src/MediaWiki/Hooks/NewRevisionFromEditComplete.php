<?php

namespace SMW\MediaWiki\Hooks;

use ParserOutput;
use SMW\ApplicationFactory;
use SMW\MediaWiki\EditInfoProvider;

/**
 * Hook: NewRevisionFromEditComplete called when a revision was inserted
 * due to an edit
 *
 * Fetch additional information that is related to the saving that has just happened,
 * e.g. regarding the last edit date. In runs where this hook is not triggered, the
 * last DB entry (of MW) will be used to fill such properties.
 *
 * Called from LocalFile.php, SpecialImport.php, Article.php, Title.php
 *
 * @see https://www.mediawiki.org/wiki/Manual:Hooks/NewRevisionFromEditComplete
 *
 * @license GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class NewRevisionFromEditComplete {

	/**
	 * @var WikiPage
	 */
	private $wikiPage = null;

	/**
	 * @var Revision
	 */
	private $revision = null;

	/**
	 * @var integer
	 */
	private $baseId = null;

	/**
	 * @var User|null
	 */
	private $user = null;

	/**
	 * @var ParserOutput|null
	 */
	private $parserOutput = null;

	/**
	 * @since  1.9
	 *
	 * @param WikiPage $article the article edited
	 * @param Revision $rev the new revision. Revision object
	 * @param $baseId the revision ID this was based off, if any
	 * @param User $user the revision author. User object
	 */
	public function __construct( $wikiPage, $revision, $baseId, $user = null ) {
		$this->wikiPage = $wikiPage;
		$this->revision = $revision;
		$this->baseId = $baseId;
		$this->user = $user;
	}

	/**
	 * @since 1.9
	 *
	 * @return boolean
	 */
	public function process() {
		return $this->getParserOutputFromEditInfo() instanceof ParserOutput ? $this->performUpdate() : true;
	}

	protected function getParserOutputFromEditInfo() {
		$editInfoProvider = new EditInfoProvider( $this->wikiPage, $this->revision, $this->user );
		return $this->parserOutput = $editInfoProvider->fetchEditInfo()->getOutput();
	}

	protected function performUpdate() {

		$applicationFactory = ApplicationFactory::getInstance();

		$parserData = $applicationFactory
			->newParserData( $this->wikiPage->getTitle(), $this->parserOutput );

		$pageInfoProvider = $applicationFactory
			->newMwCollaboratorFactory()
			->newPageInfoProvider( $this->wikiPage, $this->revision, $this->user );

		$propertyAnnotator = $applicationFactory
			->newPropertyAnnotatorFactory()
			->newPredefinedPropertyAnnotator( $parserData->getSemanticData(), $pageInfoProvider );

		$propertyAnnotator->addAnnotation();
		$parserData->pushSemanticDataToParserOutput();

		return true;
	}

}

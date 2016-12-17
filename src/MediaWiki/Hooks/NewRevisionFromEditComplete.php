<?php

namespace SMW\MediaWiki\Hooks;

use ParserOutput;
use SMW\ApplicationFactory;
use SMW\EventHandler;
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

		$parserOutput = $this->fetchParserOutputFromEditInfo();

		if ( !$parserOutput instanceof ParserOutput ) {
			return true;
		}

		$this->doExtendParserOutput( $parserOutput );
		$title = $this->wikiPage->getTitle();

		$dispatchContext = EventHandler::getInstance()->newDispatchContext();
		$dispatchContext->set( 'title', $title );
		$dispatchContext->set( 'context', 'NewRevisionFromEditComplete' );

		EventHandler::getInstance()->getEventDispatcher()->dispatch(
			'cached.prefetcher.reset',
			$dispatchContext
		);

		// If the concept was altered make sure to delete the cache
		if ( $title->getNamespace() === SMW_NS_CONCEPT ) {
			ApplicationFactory::getInstance()->getStore()->deleteConceptCache( $title );
		}

		return true;
	}

	private function fetchParserOutputFromEditInfo() {

		$editInfoProvider = new EditInfoProvider(
			$this->wikiPage,
			$this->revision,
			$this->user
		);

		return $editInfoProvider->fetchEditInfo()->getOutput();
	}

	private function doExtendParserOutput( $parserOutput ) {

		$applicationFactory = ApplicationFactory::getInstance();
		$title = $this->wikiPage->getTitle();

		$parserData = $applicationFactory->newParserData(
			$title,
			$parserOutput
		);

		$pageInfoProvider = $applicationFactory->newMwCollaboratorFactory()->newPageInfoProvider(
			$this->wikiPage,
			$this->revision,
			$this->user
		);

		$propertyAnnotatorFactory = $applicationFactory->singleton( 'PropertyAnnotatorFactory' );

		$propertyAnnotator = $propertyAnnotatorFactory->newNullPropertyAnnotator(
			$parserData->getSemanticData()
		);

		$propertyAnnotator = $propertyAnnotatorFactory->newPredefinedPropertyAnnotator(
			$propertyAnnotator,
			$pageInfoProvider
		);

		$propertyAnnotator->addAnnotation();

		$parserData->pushSemanticDataToParserOutput();
	}

}

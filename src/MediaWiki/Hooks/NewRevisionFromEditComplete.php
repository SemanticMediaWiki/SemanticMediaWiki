<?php

namespace SMW\MediaWiki\Hooks;

use ParserOutput;
use SMW\ApplicationFactory;
use SMW\EventHandler;
use SMW\MediaWiki\EditInfoProvider;
use SMW\MediaWiki\PageInfoProvider;
use Title;

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
class NewRevisionFromEditComplete extends HookHandler {

	/**
	 * @var Title
	 */
	private $title;

	/**
	 * @var EditInfoProvider
	 */
	private $editInfoProvider;

	/**
	 * @var PageInfoProvider
	 */
	private $pageInfoProvider;

	/**
	 * @since 1.9
	 *
	 * @param Title $title
	 * @param EditInfoProvider $editInfoProvider
	 * @param PageInfoProvider $pageInfoProvider
	 */
	public function __construct( Title $title, EditInfoProvider $editInfoProvider, PageInfoProvider $pageInfoProvider ) {
		parent::__construct();
		$this->title = $title;
		$this->editInfoProvider = $editInfoProvider;
		$this->pageInfoProvider = $pageInfoProvider;
	}

	/**
	 * @since 1.9
	 *
	 * @return boolean
	 */
	public function process() {

		$parserOutput = $this->editInfoProvider->fetchEditInfo()->getOutput();

		if ( !$parserOutput instanceof ParserOutput ) {
			return true;
		}

		$this->addPredefinedPropertyAnnotation(
			$parserOutput
		);

		$dispatchContext = EventHandler::getInstance()->newDispatchContext();
		$dispatchContext->set( 'title', $this->title );
		$dispatchContext->set( 'context', 'NewRevisionFromEditComplete' );

		EventHandler::getInstance()->getEventDispatcher()->dispatch(
			'cached.prefetcher.reset',
			$dispatchContext
		);

		// If the concept was altered make sure to delete the cache
		if ( $this->title->getNamespace() === SMW_NS_CONCEPT ) {
			ApplicationFactory::getInstance()->getStore()->deleteConceptCache( $this->title );
		}

		return true;
	}

	private function addPredefinedPropertyAnnotation( $parserOutput ) {

		$applicationFactory = ApplicationFactory::getInstance();

		$parserData = $applicationFactory->newParserData(
			$this->title,
			$parserOutput
		);

		$propertyAnnotatorFactory = $applicationFactory->singleton( 'PropertyAnnotatorFactory' );

		$propertyAnnotator = $propertyAnnotatorFactory->newNullPropertyAnnotator(
			$parserData->getSemanticData()
		);

		$propertyAnnotator = $propertyAnnotatorFactory->newPredefinedPropertyAnnotator(
			$propertyAnnotator,
			$this->pageInfoProvider
		);

		$propertyAnnotator->addAnnotation();

		$parserData->pushSemanticDataToParserOutput();
	}

}

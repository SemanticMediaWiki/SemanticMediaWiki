<?php

namespace SMW\MediaWiki\Hooks;

use Parser;
use SMW\ApplicationFactory;

/**
 * Hook: ParserAfterTidy to add some final processing to the
 * fully-rendered page output
 *
 * @see http://www.mediawiki.org/wiki/Manual:Hooks/ParserAfterTidy
 *
 * @license GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class ParserAfterTidy {

	/**
	 * @var Parser
	 */
	private $parser = null;

	/**
	 * @var string
	 */
	private $text;

	/**
	 * @var ApplicationFactory
	 */
	private $applicationFactory = null;

	/**
	 * @since  1.9
	 *
	 * @param Parser $parser
	 * @param string $text
	 */
	public function __construct( Parser &$parser, &$text ) {
		$this->parser = $parser;
		$this->text =& $text;
	}

	/**
	 * @since 1.9
	 *
	 * @return true
	 */
	public function process() {
		return $this->canPerformUpdate() ? $this->performUpdate() : true;
	}

	protected function canPerformUpdate() {

		if ( $this->parser->getTitle()->isSpecialPage() ) {
			return false;
		}

		if ( $this->parser->getOutput()->getProperty( 'smw-semanticdata-status' ) ||
			$this->parser->getOutput()->getCategoryLinks() ||
			$this->parser->getDefaultSort() ) {
			return true;
		}

		return false;
	}

	protected function performUpdate() {

		$this->applicationFactory = ApplicationFactory::getInstance();

		$parserData = $this->applicationFactory
			->newParserData( $this->parser->getTitle(), $this->parser->getOutput() );

		$propertyAnnotator = $this->applicationFactory
			->newPropertyAnnotatorFactory()
			->newSortkeyPropertyAnnotator(
				$parserData->getSemanticData(),
				$this->parser->getDefaultSort() );

		$propertyAnnotator->addAnnotation();

		$propertyAnnotator = $this->applicationFactory
			->newPropertyAnnotatorFactory()
			->newCategoryPropertyAnnotator(
				$parserData->getSemanticData(),
				$this->parser->getOutput()->getCategoryLinks() );

		$propertyAnnotator->addAnnotation();

		$parserData->pushSemanticDataToParserOutput();

		$this->checkForRequestedUpdateByPagePurge( $parserData );

		return true;
	}

	/**
	 * @note Article purge: In case an article was manually purged/moved
	 * the store is updated as well; for all other cases LinksUpdateConstructed
	 * will handle the store update
	 */
	private function checkForRequestedUpdateByPagePurge( $parserData ) {

		$cache = $this->applicationFactory->getCache();

		$cache->setKey( ArticlePurge::newCacheId( $this->parser->getTitle()->getArticleID() ) );

		if( $cache->get() ) {
			$cache->delete();

			// FIXME
			// I don't remember why we do an updateStore and not a simple
			// pageUpdate which would only refresh the ParserCache instead
			// of doing a full back-end update
			$parserData->updateStore();

			//$pageUpdater = $this->applicationFactory->newMwCollaboratorFactory()->newPageUpdater();
			//$pageUpdater->addPage( $parserData->getTitle() );
			//$pageUpdater->doPurgeParserCache();
		}

		return true;
	}

}

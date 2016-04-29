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

		// ParserOptions::getInterfaceMessage is being used to identify whether a
		// parse was initiated by `Message::parse`
		if ( $this->parser->getTitle()->isSpecialPage() || $this->parser->getOptions()->getInterfaceMessage() ) {
			return false;
		}

		// @see ParserData::setSemanticDataStateToParserOutputProperty
		if ( $this->parser->getOutput()->getProperty( 'smw-semanticdata-status' ) ||
			$this->parser->getOutput()->getProperty( 'displaytitle' ) ||
			$this->parser->getOutput()->getCategoryLinks() ||
			$this->parser->getDefaultSort() ) {
			return true;
		}

		return false;
	}

	protected function performUpdate() {

		$this->applicationFactory = ApplicationFactory::getInstance();

		$parserData = $this->applicationFactory->newParserData(
			$this->parser->getTitle(),
			$this->parser->getOutput()
		);

		$this->updateAnnotionsForAfterParse(
			$this->applicationFactory->newPropertyAnnotatorFactory(),
			$parserData->getSemanticData()
		);

		$parserData->pushSemanticDataToParserOutput();

		$this->checkForRequestedUpdateByPagePurge( $parserData );

		return true;
	}

	private function updateAnnotionsForAfterParse( $propertyAnnotatorFactory, $semanticData ) {

		$propertyAnnotator = $propertyAnnotatorFactory->newCategoryPropertyAnnotator(
			$semanticData,
			$this->parser->getOutput()->getCategoryLinks()
		);

		$propertyAnnotator->addAnnotation();

		$propertyAnnotator = $propertyAnnotatorFactory->newMandatoryTypePropertyAnnotator(
			$semanticData
		);

		$propertyAnnotator->addAnnotation();

		$propertyAnnotator = $propertyAnnotatorFactory->newDisplayTitlePropertyAnnotator(
			$semanticData,
			$this->parser->getOutput()->getProperty( 'displaytitle' ),
			$this->parser->getDefaultSort()
		);

		$propertyAnnotator->addAnnotation();

		$propertyAnnotator = $propertyAnnotatorFactory->newSortKeyPropertyAnnotator(
			$semanticData,
			$this->parser->getDefaultSort()
		);

		$propertyAnnotator->addAnnotation();
	}

	/**
	 * @note Article purge: In case an article was manually purged/moved
	 * the store is updated as well; for all other cases LinksUpdateConstructed
	 * will handle the store update
	 *
	 * @note The purge action is isolated from any other request therefore using
	 * a static variable or any other messaging that is not persistent will not
	 * work hence the reliance on the cache as temporary persistence marker
	 */
	private function checkForRequestedUpdateByPagePurge( $parserData ) {

		// Only carry out a purge where InTextAnnotationParser have set
		// an appropriate context reference otherwise it is assumed that the hook
		// call is part of another non SMW related parse
		if ( $parserData->getSemanticData()->getSubject()->getContextReference() === null ) {
			return true;
		}

		$cache = $this->applicationFactory->getCache();
		$start = microtime( true );

		$key = $this->applicationFactory->newCacheFactory()->getPurgeCacheKey(
			$this->parser->getTitle()->getArticleID()
		);

		if( $cache->contains( $key ) && $cache->fetch( $key ) ) {
			$cache->delete( $key );

			// Set a timestamp explicitly to create a new hash for the property
			// table change row differ and force a data comparison (this doesn't
			// change the _MDAT annotation)
			$parserData->getSemanticData()->setLastModified( wfTimestamp( TS_UNIX ) );
			$parserData->updateStore( true );

			$parserData->addLimitReport(
				'pagepurge-storeupdatetime',
				number_format( ( microtime( true ) - $start ), 3 )
			);
		}

		return true;
	}

}

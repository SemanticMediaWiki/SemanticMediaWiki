<?php

namespace SMW\MediaWiki\Hooks;

use Parser;
use SMW\ApplicationFactory;
use SMW\SemanticData;

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
			$this->parser->getTitle()->isProtected( 'edit' ) ||
			$this->parser->getOutput()->getCategoryLinks() ||
			$this->parser->getDefaultSort() ) {
			return true;
		}

		return false;
	}

	protected function performUpdate() {

		$applicationFactory = ApplicationFactory::getInstance();

		$parserData = $applicationFactory->newParserData(
			$this->parser->getTitle(),
			$this->parser->getOutput()
		);

		$this->updateAnnotationsForAfterParse(
			$applicationFactory->singleton( 'PropertyAnnotatorFactory' ),
			$parserData->getSemanticData()
		);

		$parserData->pushSemanticDataToParserOutput();

		$this->checkForRequestedUpdateByPagePurge( $parserData );

		return true;
	}

	private function updateAnnotationsForAfterParse( $propertyAnnotatorFactory, $semanticData ) {

		$propertyAnnotator = $propertyAnnotatorFactory->newNullPropertyAnnotator(
			$semanticData
		);

		$propertyAnnotator = $propertyAnnotatorFactory->newCategoryPropertyAnnotator(
			$propertyAnnotator,
			$this->parser->getOutput()->getCategoryLinks()
		);

		$propertyAnnotator = $propertyAnnotatorFactory->newMandatoryTypePropertyAnnotator(
			$propertyAnnotator
		);

		$propertyAnnotator = $propertyAnnotatorFactory->newEditProtectedPropertyAnnotator(
			$propertyAnnotator,
			$this->parser->getTitle()
		);

		// Special case! belongs to the EditProtectedPropertyAnnotator instance
		$propertyAnnotator->addTopIndicatorTo(
			$this->parser->getOutput()
		);

		$propertyAnnotator = $propertyAnnotatorFactory->newDisplayTitlePropertyAnnotator(
			$propertyAnnotator,
			$this->parser->getOutput()->getProperty( 'displaytitle' ),
			$this->parser->getDefaultSort()
		);

		$propertyAnnotator = $propertyAnnotatorFactory->newSortKeyPropertyAnnotator(
			$propertyAnnotator,
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

		$cache = ApplicationFactory::getInstance()->getCache();
		$start = microtime( true );

		$key = ApplicationFactory::getInstance()->getCacheFactory()->getPurgeCacheKey(
			$this->parser->getTitle()->getArticleID()
		);

		if( $cache->contains( $key ) && $cache->fetch( $key ) ) {
			$cache->delete( $key );

			$parserData->setOrigin( 'ParserAfterTidy' );

			// Set a timestamp explicitly to create a new hash for the property
			// table change row differ and force a data comparison (this doesn't
			// change the _MDAT annotation)
			$parserData->getSemanticData()->setOption(
				SemanticData::OPT_LAST_MODIFIED,
				wfTimestamp( TS_UNIX )
			);

			$parserData->updateStore( true );

			$parserData->addLimitReport(
				'pagepurge-storeupdatetime',
				number_format( ( microtime( true ) - $start ), 3 )
			);
		}

		return true;
	}

}

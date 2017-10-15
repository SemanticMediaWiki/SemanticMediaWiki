<?php

namespace SMW\MediaWiki\Hooks;

use Parser;
use SMW\ApplicationFactory;
use SMW\SemanticData;
use SMW\MediaWiki\MediaWiki;

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
class ParserAfterTidy extends HookHandler {

	/**
	 * @var Parser
	 */
	private $parser;

	/**
	 * @var NamespaceExaminer
	 */
	private $namespaceExaminer;

	/**
	 * @var boolean
	 */
	private $isCommandLineMode = false;

	/**
	 * @var boolean
	 */
	private $isReadOnly = false;

	/**
	 * @since  1.9
	 *
	 * @param Parser $parser
	 */
	public function __construct( Parser &$parser ) {
		$this->parser = $parser;
		$this->namespaceExaminer = ApplicationFactory::getInstance()->getNamespaceExaminer();
	}

	/**
	 * @see https://www.mediawiki.org/wiki/Manual:$wgCommandLineMode
	 *
	 * @since 2.5
	 *
	 * @param boolean $isCommandLineMode
	 */
	public function isCommandLineMode( $isCommandLineMode ) {
		$this->isCommandLineMode = (bool)$isCommandLineMode;
	}

	/**
	 * @since 3.0
	 *
	 * @param boolean $isReadOnly
	 */
	public function isReadOnly( $isReadOnly ) {
		$this->isReadOnly = (bool)$isReadOnly;
	}

	/**
	 * @since 1.9
	 *
	 * @param string $text
	 *
	 * @return true
	 */
	public function process( &$text ) {

		if ( $this->canPerformUpdate() ) {
			$this->performUpdate( $text );
		}

		return true;
	}

	private function canPerformUpdate() {

		// #2432 avoid access to the DBLoadBalancer while being in readOnly mode
		// when for example Title::isProtected is accessed
		if ( $this->isReadOnly ) {
			return false;
		}

		$title = $this->parser->getTitle();

		if ( !$this->namespaceExaminer->isSemanticEnabled( $title->getNamespace() ) ) {
			return false;
		}

		// ParserOptions::getInterfaceMessage is being used to identify whether a
		// parse was initiated by `Message::parse`
		if ( $title->isSpecialPage() || $this->parser->getOptions()->getInterfaceMessage() ) {
			return false;
		}

		// @see ParserData::setSemanticDataStateToParserOutputProperty
		if ( $this->parser->getOutput()->getProperty( 'smw-semanticdata-status' ) ||
			$this->parser->getOutput()->getProperty( 'displaytitle' ) ||
			$title->isProtected( 'edit' ) ||
			$this->parser->getOutput()->getCategoryLinks() ||
			$this->parser->getDefaultSort() ) {
			return true;
		}

		return false;
	}

	private function performUpdate( &$text ) {

		$applicationFactory = ApplicationFactory::getInstance();

		$parserData = $applicationFactory->newParserData(
			$this->parser->getTitle(),
			$this->parser->getOutput()
		);

		$this->updateAnnotationsOnAfterParse(
			$applicationFactory->singleton( 'PropertyAnnotatorFactory' ),
			$parserData->getSemanticData()
		);

		$parserData->pushSemanticDataToParserOutput();

		$this->checkOnPurgeRequest( $parserData );
	}

	private function updateAnnotationsOnAfterParse( $propertyAnnotatorFactory, $semanticData ) {

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
	private function checkOnPurgeRequest( $parserData ) {

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

			// Avoid a Parser::lock for when a PurgeRequest remains intact
			// during an update process while being executed from the cmdLine
			if ( $this->isCommandLineMode ) {
				return true;
			}

			$parserData->setOrigin( 'ParserAfterTidy' );

			// Set an explicit timestamp to create a new hash for the property
			// table change row differ and force a data comparison (this doesn't
			// change the _MDAT annotation)
			$parserData->getSemanticData()->setOption(
				SemanticData::OPT_LAST_MODIFIED,
				wfTimestamp( TS_UNIX )
			);

			$parserData->setOption(
				$parserData::OPT_FORCED_UPDATE,
				true
			);

			$parserData->updateStore( true );

			$parserData->addLimitReport(
				'pagepurge-storeupdatetime',
				number_format( ( microtime( true ) - $start ), 3 )
			);
		}
	}

}

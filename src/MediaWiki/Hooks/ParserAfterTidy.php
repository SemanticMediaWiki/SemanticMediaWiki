<?php

namespace SMW\MediaWiki\Hooks;

use Parser;
use SMW\ApplicationFactory;
use SMW\MediaWiki\MediaWiki;
use SMW\ParserData;
use SMW\SemanticData;
use Onoi\Cache\Cache;
use SMW\NamespaceExaminer;

/**
 * Hook: ParserAfterTidy to add some final processing to the
 * fully-rendered page output
 *
 * @see https://www.mediawiki.org/wiki/Manual:Hooks/ParserAfterTidy
 *
 * @license GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class ParserAfterTidy extends HookHandler {

	const CACHE_NAMESPACE = 'smw:parseraftertidy';

	/**
	 * @var Parser
	 */
	private $parser;

	/**
	 * @var NamespaceExaminer
	 */
	private $namespaceExaminer;

	/**
	 * @var Cache
	 */
	private $cache;

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
	 * @param NamespaceExaminer $NamespaceExaminer
	 * @param Cache $cache
	 */
	public function __construct( Parser &$parser, NamespaceExaminer $namespaceExaminer, Cache $cache ) {
		$this->parser = $parser;
		$this->namespaceExaminer = $namespaceExaminer;
		$this->cache = $cache;
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

		// Avoid an update for the SCHEMA NS to ensure errors remain present without
		// the need the rerun the schema validator again.
		if ( $title->getNamespace() === SMW_NS_SCHEMA ) {
			return false;
		}

		// ParserOptions::getInterfaceMessage is being used to identify whether a
		// parse was initiated by `Message::parse`
		if ( $title->isSpecialPage() || $this->parser->getOptions()->getInterfaceMessage() ) {
			return false;
		}

		$parserOutput = $this->parser->getOutput();

		if ( $parserOutput->getProperty( 'displaytitle' ) ||
			$parserOutput->getImages() !== [] ||
			$parserOutput->getExtensionData( 'translate-translation-page' ) ||
			$parserOutput->getCategoryLinks() ) {
			return true;
		}

		if ( ParserData::hasSemanticData( $parserOutput ) ||
			$title->isProtected( 'edit' ) ||
			$this->parser->getDefaultSort() ) {
			return true;
		}

		$key = smwfCacheKey( self::CACHE_NAMESPACE, $title->getPrefixedDBKey() );

		// Allow to continue the processing even without a `[[...::...]]` text
		// so that a change (such as an approved file, page version) is run
		// through the annotation and update process as part of a programtic
		// purge request.
		// @see SemanticApprovedRevs#2
		if( $this->cache->fetch( $key ) !== false ) {
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

		$semanticData = $parserData->getSemanticData();

		$this->addPropertyAnnotations(
			$applicationFactory->singleton( 'PropertyAnnotatorFactory' ),
			$semanticData
		);

		$parserData->copyToParserOutput();
		$subject = $semanticData->getSubject();

		// Only carry out a purge where the InTextAnnotationParser have set
		// an appropriate context reference otherwise it is assumed that the hook
		// call is part of another non SMW related parse
		if ( $subject->getContextReference() !== null || $subject->getNamespace() === NS_FILE ) {
			$this->checkPurgeRequest( $parserData );
		}
	}

	private function addPropertyAnnotations( $propertyAnnotatorFactory, $semanticData ) {

		$parserOutput = $this->parser->getOutput();

		$propertyAnnotator = $propertyAnnotatorFactory->newNullPropertyAnnotator(
			$semanticData
		);

		$propertyAnnotator = $propertyAnnotatorFactory->newCategoryPropertyAnnotator(
			$propertyAnnotator,
			$parserOutput->getCategoryLinks()
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
			$parserOutput
		);

		$propertyAnnotator = $propertyAnnotatorFactory->newDisplayTitlePropertyAnnotator(
			$propertyAnnotator,
			$parserOutput->getProperty( 'displaytitle' ),
			$this->parser->getDefaultSort()
		);

		$propertyAnnotator = $propertyAnnotatorFactory->newSortKeyPropertyAnnotator(
			$propertyAnnotator,
			$this->parser->getDefaultSort()
		);

		// #2300
		$propertyAnnotator = $propertyAnnotatorFactory->newTranslationPropertyAnnotator(
			$propertyAnnotator,
			$parserOutput->getExtensionData( 'translate-translation-page' )
		);

		// #3640
		$propertyAnnotator = $propertyAnnotatorFactory->newAttachmentLinkPropertyAnnotator(
			$propertyAnnotator,
			$parserOutput->getImages()
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
	private function checkPurgeRequest( $parserData ) {

		$start = microtime( true );
		$title = $this->parser->getTitle();

		$key = smwfCacheKey( ArticlePurge::CACHE_NAMESPACE, $title->getArticleID() );

		if( $this->cache->contains( $key ) && $this->cache->fetch( $key ) ) {
			$this->cache->delete( $key );
			$this->cache->delete( smwfCacheKey( self::CACHE_NAMESPACE, $title->getPrefixedDBKey() ) );

			// Avoid a Parser::lock for when a PurgeRequest remains intact
			// during an update process while being executed from the cmdLine
			if ( $this->isCommandLineMode ) {
				return true;
			}

			$semanticData = $parserData->getSemanticData();

			// Set an explicit timestamp to create a new hash for the property
			// table change row differ and force a data comparison (this doesn't
			// change the _MDAT annotation)
			$semanticData->setOption(
				SemanticData::OPT_LAST_MODIFIED,
				wfTimestamp( TS_UNIX )
			);

			// #3849
			if ( $this->getOption( 'smwgCheckForRemnantEntities' ) === 'purge' ) {
				$semanticData->setOption( SemanticData::OPT_CHECK_REMNANT_ENTITIES, true );
			}

			$parserData->setOption(
				$parserData::OPT_FORCED_UPDATE,
				true
			);

			$parserData->setOrigin( 'ParserAfterTidy' );
			$parserData->updateStore( true );

			$parserData->addLimitReport(
				'pagepurge-storeupdatetime',
				number_format( ( microtime( true ) - $start ), 3 )
			);
		}
	}

}

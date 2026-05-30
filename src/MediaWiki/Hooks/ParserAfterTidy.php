<?php

namespace SMW\MediaWiki\Hooks;

use MediaWiki\Hook\ParserAfterTidyHook;
use MediaWiki\HookContainer\HookContainer;
use MediaWiki\Parser\Parser;
use MediaWiki\Parser\ParserOutputLinkTypes;
use MediaWiki\Permissions\RestrictionStore;
use Psr\Log\LoggerInterface;
use SMW\DataModel\SemanticData;
use SMW\NamespaceExaminer;
use SMW\ParserData;
use SMW\Services\ServicesFactory as ApplicationFactory;
use SMW\Settings;
use SMW\Site;
use WeakMap;
use Wikimedia\ObjectCache\BagOStuff;

/**
 * Hook: ParserAfterTidy to add some final processing to the
 * fully-rendered page output
 *
 * @see https://www.mediawiki.org/wiki/Manual:Hooks/ParserAfterTidy
 *
 * @license GPL-2.0-or-later
 * @since 1.9
 *
 * @author mwjames
 */
class ParserAfterTidy implements ParserAfterTidyHook {

	const CACHE_NAMESPACE = 'smw:parseraftertidy';

	/**
	 * In-flight `Parser::parse()` calls, keyed by Parser instance and mapped
	 * to the title prefixed-DB key being parsed. Used to distinguish the
	 * outermost ParserAfterTidy fire from inner fires triggered by extensions
	 * that clone the parser and recurse on the same title (see #5923).
	 * Populated by `onParserClearState()` and drained in `process()`.
	 *
	 * Keyed by Parser instance so that short-lived clones are reclaimed by GC
	 * automatically if `Parser::parse()` throws between `clearState` and
	 * `ParserAfterTidy` (no `finally`-equivalent at the parser level). For a
	 * long-lived parser whose entry survives an exception, the next successful
	 * `process()` call for that parser re-runs the normal flow and clears the
	 * entry, so the leak does not compound across requests.
	 */
	private static ?WeakMap $inFlightParses = null;

	/**
	 * @since 7.0.0
	 */
	public function __construct(
		private readonly NamespaceExaminer $namespaceExaminer,
		private readonly BagOStuff $cache,
		private readonly ApplicationFactory $servicesFactory,
		private readonly HookContainer $hookContainer,
		private readonly Settings $settings,
		private readonly LoggerInterface $logger,
		private readonly RestrictionStore $restrictionStore,
	) {
	}

	/**
	 * Hook handler for `ParserClearState`. Called at the start of every
	 * `Parser::parse()` invocation (when `clearState` is true). Records the
	 * parser as in-flight so a subsequent inner parse on the same title can
	 * detect that it is nested and skip the update (see #5923).
	 *
	 * @since 7.0.0
	 */
	public static function onParserClearState( Parser $parser ): void {
		if ( $parser->getOptions()->getInterfaceMessage() ) {
			return;
		}
		// `Parser::clearState()` can be invoked outside of an actual
		// `Parser::parse()` (test helpers, manual state resets, etc.); in those
		// cases `isLocked()` is `false` and the parser is not really competing
		// for the title, so we must not record it as in-flight or we mark
		// every later, unrelated parse of the same title as "nested".
		if ( !$parser->isLocked() ) {
			return;
		}
		if ( self::$inFlightParses === null ) {
			self::$inFlightParses = new WeakMap();
		}
		// Re-setting for the same Parser is idempotent and self-healing if a
		// previous parse on this instance leaked an entry by throwing.
		self::$inFlightParses[$parser] = $parser->getTitle()->getPrefixedDBKey();
	}

	/**
	 * Reset the in-flight tracker. Intended for tests.
	 *
	 * @since 7.0.0
	 */
	public static function resetInFlightParses(): void {
		self::$inFlightParses = null;
	}

	/**
	 * Count active in-flight parses for the given title (excluding the
	 * supplied parser, if any).
	 */
	private static function countActiveParsesForTitle( string $titleKey, ?Parser $excluding ): int {
		if ( self::$inFlightParses === null || $titleKey === '' ) {
			return 0;
		}
		$count = 0;
		foreach ( self::$inFlightParses as $parser => $parsedTitleKey ) {
			if ( $parser === $excluding ) {
				continue;
			}
			if ( $parsedTitleKey !== $titleKey ) {
				continue;
			}
			// A leftover entry whose Parser is no longer locked is stale: the
			// parse it represented has long ended (or never started). It must
			// not be counted as a competing in-flight parse, otherwise the
			// current outermost fire would be wrongly classified as nested.
			if ( !$parser->isLocked() ) {
				continue;
			}
			$count++;
		}
		return $count;
	}

	/**
	 * @since 7.0.0
	 */
	public function onParserAfterTidy( $parser, &$text ) {
		if ( !Site::isReady() ) {
			$this->doAbort();
			return true;
		}

		// `(string)` defends against unit-test mocks where `getTitle()` returns
		// a Title mock whose `getPrefixedDBKey()` is not stubbed and resolves
		// to `null` rather than the production `string`.
		$key = (string)$parser->getTitle()->getPrefixedDBKey();

		// #5923: When an extension clones the parser and re-enters `Parser::parse()`
		// on the same title (e.g. DPL `<dpl>`, TabberNeue `<tabber>`), the inner
		// parse fires its own `ParserAfterTidy`. Without this guard, the inner
		// fire (which only sees a partial `ParserOutput`) would consume the
		// `ArticlePurge` cache key in `checkPurgeRequest()` and persist incomplete
		// data, while the outermost fire (with complete data) would skip because
		// the key has been deleted. Skip processing for inner fires and let the
		// outermost run with the full state. Interface-message parses are
		// excluded from the tracker by `onParserClearState`, so they pass
		// through here untouched.
		$isNestedParse = $key !== ''
			&& self::countActiveParsesForTitle( $key, $parser ) > 0;

		try {
			if ( !$isNestedParse && $this->canPerformUpdate( $parser ) ) {
				$this->performUpdate( $parser, $text );
			}
		} finally {
			if ( self::$inFlightParses !== null ) {
				unset( self::$inFlightParses[$parser] );
			}
		}

		return true;
	}

	private function canPerformUpdate( Parser $parser ): bool {
		// #2432 avoid access to the DBLoadBalancer while being in readOnly mode
		// when for example Title::isProtected is accessed
		if ( !Site::isReady() ) {
			return $this->doAbort();
		}

		$title = $parser->getTitle();

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
		if ( $title->isSpecialPage() || $parser->getOptions()->getInterfaceMessage() ) {
			return false;
		}

		$parserOutput = $parser->getOutput();

		// T301915
		$displayTitle = $parserOutput->getPageProperty( 'displaytitle' ) ?? false;
		$parserDefaultSort = $parserOutput->getPageProperty( 'defaultsort' );

		$parserCategories = [];
		foreach ( $parserOutput->getCategoryNames() as $name ) {
			$parserCategories[$name] = $parserOutput->getCategorySortKey( $name );
		}

		if ( $displayTitle ||
			$parserOutput->getLinkList( ParserOutputLinkTypes::MEDIA ) !== [] ||
			$parserOutput->getExtensionData( 'translate-translation-page' ) ||
			$parserCategories ) {
			return true;
		}

		if ( ParserData::hasSemanticData( $parserOutput ) ||
			$this->restrictionStore->isProtected( $title, 'edit' ) ||
			$parserDefaultSort ) {
			return true;
		}

		$key = smwfCacheKey( self::CACHE_NAMESPACE, $title->getPrefixedDBKey() );

		// Allow to continue the processing even without a `[[...::...]]` text
		// so that a change (such as an approved file, page version) is run
		// through the annotation and update process as part of a programtic
		// purge request.
		// @see SemanticApprovedRevs#2
		if ( $this->cache->get( $key ) !== false ) {
			return true;
		}

		return false;
	}

	private function performUpdate( Parser $parser, string &$text ): void {
		$parserData = $this->servicesFactory->newParserData(
			$parser->getTitle(),
			$parser->getOutput()
		);

		$semanticData = $parserData->getSemanticData();

		$this->addPropertyAnnotations(
			$parser,
			$this->servicesFactory->singleton( 'PropertyAnnotatorFactory' ),
			$semanticData
		);

		$parserData->copyToParserOutput();
		$subject = $semanticData->getSubject();

		// Only carry out a purge where the InTextAnnotationParser have set
		// an appropriate context reference otherwise it is assumed that the hook
		// call is part of another non SMW related parse
		if ( $subject->getContextReference() !== null || $subject->getNamespace() === NS_FILE ) {
			$this->checkPurgeRequest( $parser, $parserData );
		}
	}

	private function addPropertyAnnotations( Parser $parser, $propertyAnnotatorFactory, $semanticData ): void {
		$parserOutput = $parser->getOutput();

		$propertyAnnotator = $propertyAnnotatorFactory->newNullPropertyAnnotator(
			$semanticData
		);

		$parserCategoryKeys = $parserOutput->getCategoryNames();

		$propertyAnnotator = $propertyAnnotatorFactory->newCategoryPropertyAnnotator(
			$propertyAnnotator,
			$parserCategoryKeys
		);

		$propertyAnnotator = $propertyAnnotatorFactory->newMandatoryTypePropertyAnnotator(
			$propertyAnnotator
		);

		$propertyAnnotator = $propertyAnnotatorFactory->newEditProtectedPropertyAnnotator(
			$propertyAnnotator,
			$parser->getTitle()
		);

		// Special case! belongs to the EditProtectedPropertyAnnotator instance
		$propertyAnnotator->addTopIndicatorTo(
			$parserOutput
		);

		// T301915
		$displayTitle = $parserOutput->getPageProperty( 'displaytitle' ) ?? false;
		$parserDefaultSort = $parserOutput->getPageProperty( 'defaultsort' ) ?? '';

		$propertyAnnotator = $propertyAnnotatorFactory->newDisplayTitlePropertyAnnotator(
			$propertyAnnotator,
			$displayTitle,
			$parserDefaultSort
		);

		$propertyAnnotator = $propertyAnnotatorFactory->newSortKeyPropertyAnnotator(
			$propertyAnnotator,
			$parserDefaultSort
		);

		// #2300
		$propertyAnnotator = $propertyAnnotatorFactory->newTranslationPropertyAnnotator(
			$propertyAnnotator,
			$parserOutput->getExtensionData( 'translate-translation-page' )
		);

		// #3640
		$propertyAnnotator = $propertyAnnotatorFactory->newAttachmentLinkPropertyAnnotator(
			$propertyAnnotator,
			$parserOutput->getLinkList( ParserOutputLinkTypes::MEDIA )
		);

		$propertyAnnotator->addAnnotation();

		$this->hookContainer->run(
			'SMW::Parser::ParserAfterTidyPropertyAnnotationComplete',
			[ $propertyAnnotator, $parserOutput ]
		);
	}

	/**
	 * @note Article purge: In case an article was manually purged/moved
	 * the store is updated as well; for all other cases LinksUpdateComplete
	 * will handle the store update
	 *
	 * @note The purge action is isolated from any other request therefore using
	 * a static variable or any other messaging that is not persistent will not
	 * work hence the reliance on the cache as temporary persistence marker
	 */
	private function checkPurgeRequest( Parser $parser, $parserData ): ?bool {
		$start = microtime( true );
		$title = $parser->getTitle();

		$key = smwfCacheKey( ArticlePurge::CACHE_NAMESPACE, $title->getArticleID() );

		if ( $this->cache->get( $key ) ) {
			$this->cache->delete( $key );
			$this->cache->delete( smwfCacheKey( self::CACHE_NAMESPACE, $title->getPrefixedDBKey() ) );

			// Avoid a Parser::lock for when a PurgeRequest remains intact
			// during an update process while being executed from the cmdLine
			if ( Site::isCommandLineMode() ) {
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
			if ( $this->settings->get( 'smwgCheckForRemnantEntities' ) === 'purge' ) {
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

		return null;
	}

	private function doAbort(): bool {
		$this->logger->info(
			"ParserAfterTidy was invoked but the site isn't ready yet, aborting the processing."
		);

		return false;
	}

}

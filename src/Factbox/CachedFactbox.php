<?php

namespace SMW\Factbox;

use MediaWiki\Output\OutputPage;
use MediaWiki\Parser\ParserOutput;
use MediaWiki\Request\WebRequest;
use MediaWiki\Title\Title;
use Psr\Log\LoggerAwareTrait;
use SMW\EntityCache;
use SMW\Parser\InTextAnnotationParser;
use SMW\Services\ServicesFactory as ApplicationFactory;
use SMW\Utils\HmacSerializer;

/**
 * Factbox output caching
 *
 * Use a EntityCache to avoid unaltered content being re-parsed every time the
 * OutputPage hook is executed.
 *
 * @license GPL-2.0-or-later
 * @since 1.9
 *
 * @author mwjames
 */
class CachedFactbox {

	use LoggerAwareTrait;

	private bool $isCached = false;

	private int $featureSet = 0;

	private int $showFactboxEdit = 0;

	private int $showFactbox = 0;

	private bool $isEnabled = true;

	private int $cacheTTL = 0;

	/**
	 * @since 1.9
	 */
	public function __construct(
		private EntityCache $entityCache,
		private FactboxText $factboxText,
	) {
	}

	/**
	 * @since 1.9
	 */
	public function isCached(): bool {
		return $this->isCached;
	}

	/**
	 * @since 3.0
	 */
	public function setFeatureSet( int $featureSet ): void {
		$this->featureSet = $featureSet;
	}

	/**
	 * @since 3.1
	 */
	public function setShowFactboxEdit( int $showFactboxEdit ): void {
		$this->showFactboxEdit = $showFactboxEdit;
	}

	/**
	 * @since 3.1
	 */
	public function setShowFactbox( int $showFactbox ): void {
		$this->showFactbox = $showFactbox;
	}

	/**
	 * @since 2.5
	 */
	public function setCacheTTL( int $cacheTTL ): void {
		$this->cacheTTL = $cacheTTL;
	}

	/**
	 * @since 2.5
	 */
	public function isEnabled( bool $isEnabled ): void {
		$this->isEnabled = $isEnabled;
	}

	/**
	 * Prepare and update the OutputPage property
	 *
	 * Factbox content is either retrieved from a CacheStore or re-parsed from
	 * the Factbox object
	 *
	 * Altered content is tracked using the revision Id, getLatestRevID() only
	 * changes after a content modification has occurred.
	 *
	 * @since 1.9
	 *
	 * @param OutputPage &$outputPage
	 * @param ParserOutput $parserOutput
	 */
	public function prepare( OutputPage &$outputPage, ParserOutput $parserOutput ): void {
		$this->factboxText->clear();
		$time = -microtime( true );

		$context = $outputPage->getContext();
		$request = $context->getRequest();
		$isPreview = $request->getCheck( 'wpPreview' );

		$checkMagicWords = new CheckMagicWords(
			[
				'preview' => $isPreview,
				'showFactboxEdit' => $this->showFactboxEdit,
				'showFactbox' => $this->showFactbox
			]
		);

		if ( $checkMagicWords->getMagicWords( $parserOutput ) === SMW_FACTBOX_HIDDEN ) {
			return;
		}

		$outputPage->addModuleStyles( Factbox::getModuleStyles() );
		$outputPage->addModules( Factbox::getModules() );
		$title = $outputPage->getTitle();

		$rev_id = $outputPage->getRevisionId();
		$lang = $context->getLanguage()->getCode();

		if ( !$this->canUseCache( $rev_id, $request ) ) {
			$this->isCached = false;
			$this->factboxText->setText( $this->rebuild( $title, $parserOutput, $checkMagicWords ) );
			return;
		}

		$this->isCached = true;

		$cachedText = $this->entityCache->getWithSetCallback(
			$title,
			$this->makeCacheKey( $title, $rev_id, $lang ),
			$this->cacheTTL,
			function () use ( $title, $parserOutput, $checkMagicWords ) {
				$this->isCached = false;
				$text = $this->rebuild( $title, $parserOutput, $checkMagicWords );

				// Whether a factbox is shown depends on settings the cache key leaves
				// out, so an invisible one is not cached
				return $text === null ? false : HmacSerializer::compress( $text );
			}
		);

		$text = $this->uncompress( $cachedText );

		// Cached content that no longer verifies, as after a change of $wgSecretKey
		if ( $this->isCached && $text === null ) {
			$this->entityCache->invalidate( $title );
			$this->isCached = false;
			$text = $this->rebuild( $title, $parserOutput, $checkMagicWords );
		}

		$this->factboxText->setText( $text );

		$this->logger->info(
			( $this->isCached ? 'Factbox Using cached factbox' : 'Factbox Rebuild factbox' ) .
				' rev_id: {rev_id} {lang} procTime: {procTime}',
			[
				'rev_id' => $rev_id,
				'lang' => $lang,
				'procTime' => microtime( true ) + $time
			]
		);
	}

	/**
	 * Returns parsed Factbox content from either the OutputPage property
	 * or from the Cache
	 *
	 * @since 1.9
	 */
	public function retrieveContent( OutputPage $outputPage ): string {
		$title = $outputPage->getTitle();

		if ( $title instanceof Title && ( $title->isSpecialPage() || !$title->exists() ) ) {
			return '';
		}

		if ( $this->factboxText->hasText() ) {
			return $this->factboxText->getText() ?? '';
		}

		if ( !$title instanceof Title || !$this->isEnabled ) {
			return '';
		}

		$cachedText = $this->entityCache->fetch(
			$title,
			$this->makeCacheKey( $title, $outputPage->getRevisionId(), $outputPage->getContext()->getLanguage()->getCode() )
		);

		return $this->uncompress( $cachedText ) ?? '';
	}

	private function canUseCache( ?int $rev_id, WebRequest $request ): bool {
		return $this->isEnabled &&
			$rev_id &&
			!$request->getCheck( 'wpPreview' ) &&
			$request->getVal( 'action' ) !== 'edit';
	}

	private function makeCacheKey( Title $title, ?int $rev_id, string $lang ): string {
		return $this->entityCache->makeKey( 'factbox', $title->getArticleID(), $rev_id, $lang, $this->featureSet );
	}

	/**
	 * Processing and re-parsing of the Factbox content
	 */
	private function rebuild( Title $title, ParserOutput $parserOutput, CheckMagicWords $checkMagicWords ) {
		$text = null;
		$applicationFactory = ApplicationFactory::getInstance();

		$factbox = $applicationFactory->singleton( 'FactboxFactory' )->newFactbox(
			$title,
			$parserOutput
		);

		$factbox->setCheckMagicWords(
			$checkMagicWords
		);

		$factbox->doBuild();

		if ( !$factbox->isVisible() ) {
			return $text;
		}

		$contentParser = $applicationFactory->newContentParser( $title );

		$content = $factbox->getContent();
		if ( $content !== '' ) {
			$contentParser->parse( $content, false );
			$content = InTextAnnotationParser::removeAnnotation(
				$contentParser->getOutput()->getContentHolderText()
			);
		}

		$attachmentContent = $factbox->getAttachmentHTML();
		if ( $attachmentContent !== '' ) {
			$contentParser->parse( $attachmentContent, false );
			$attachmentContent = $contentParser->getOutput()->getContentHolderText();
		}

		return $factbox->tabs( $content, $attachmentContent );
	}

	private function uncompress( string|false $cachedText ): ?string {
		if ( $cachedText === false ) {
			return null;
		}

		$text = HmacSerializer::uncompress( $cachedText );

		return is_string( $text ) ? $text : null;
	}

}

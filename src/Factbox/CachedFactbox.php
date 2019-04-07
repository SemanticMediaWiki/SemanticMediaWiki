<?php

namespace SMW\Factbox;

use SMW\EntityCache;
use OutputPage;
use ParserOutput;
use SMW\ApplicationFactory;
use SMW\Parser\InTextAnnotationParser;
use Title;
use Psr\Log\LoggerAwareTrait;
use SMW\Utils\HmacSerializer;
use SMW\MediaWiki\RevisionGuard;

/**
 * Factbox output caching
 *
 * Use a EntityCache to avoid unaltered content being re-parsed every time the
 * OutputPage hook is executed.
 *
 * @license GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class CachedFactbox {

	use LoggerAwareTrait;

	/**
	 * @var EntityCache
	 */
	private $entityCache;

	/**
	 * @var boolean
	 */
	private $isCached = false;

	/**
	 * @var integer
	 */
	private $featureSet = 0;

	/**
	 * @var integer
	 */
	private $showFactboxEdit = 0;

	/**
	 * @var integer
	 */
	private $showFactbox = 0;

	/**
	 * @var boolean
	 */
	private $isEnabled = true;

	/**
	 * @var integer
	 */
	private $cacheTTL = 0;

	/**
	 * @var integer
	 */
	private $timestamp;

	/**
	 * @since 1.9
	 *
	 * @param EntityCache $entityCache
	 */
	public function __construct( EntityCache $entityCache ) {
		$this->entityCache = $entityCache;
	}

	/**
	 * @since 1.9
	 *
	 * @return boolean
	 */
	public function isCached() {
		return $this->isCached;
	}

	/**
	 * @since 3.0
	 *
	 * @param integer $featureSet
	 */
	public function setFeatureSet( $featureSet ) {
		$this->featureSet = $featureSet;
	}

	/**
	 * @since 3.1
	 *
	 * @param integer $showFactboxEdit
	 */
	public function setShowFactboxEdit( $showFactboxEdit ) {
		$this->showFactboxEdit = $showFactboxEdit;
	}

	/**
	 * @since 3.1
	 *
	 * @param integer $showFactbox
	 */
	public function setShowFactbox( $showFactbox ) {
		$this->showFactbox = $showFactbox;
	}

	/**
	 * @since 2.5
	 *
	 * @param integer $cacheTTL
	 */
	public function setCacheTTL( $cacheTTL ) {
		$this->cacheTTL = $cacheTTL;
	}

	/**
	 * @since 2.5
	 *
	 * @return boolean
	 */
	public function isEnabled( $isEnabled ) {
		$this->isEnabled = $isEnabled;
	}

	/**
	 * @since 2.2
	 *
	 * @return integer
	 */
	public function getTimestamp() {
		return $this->timestamp;
	}

	/**
	 * @since 2.2
	 *
	 * @return integer
	 */
	public static function makeCacheKey( $id ) {

		if ( $id instanceof Title ) {
			$id = $id->getArticleID();
		}

		return EntityCache::makeCacheKey( 'factbox', $id );
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
	public function prepare( OutputPage &$outputPage, ParserOutput $parserOutput ) {

		$outputPage->mSMWFactboxText = null;
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

		$outputPage->addModules( Factbox::getModules() );
		$title = $outputPage->getTitle();

		$rev_id = $this->findRevId( $title, $request );
		$lang = $context->getLanguage()->getCode();
		$content = '';

		$key = $this->makeCacheKey( $title );
		$subKey = $this->makeSubCacheKey( $rev_id, $lang, $this->featureSet );

		if ( ( $data = $this->entityCache->fetchSub( $key, $subKey ) ) !== false ) {
			$content = $this->findContentFromCache( $data );
		}

		if ( !$isPreview && $this->hasCachedContent( $subKey, $rev_id, $lang, $content, $request ) ) {

			$this->logger->info(
				[ 'Factbox', 'Using cached factbox', 'rev_id: {rev_id}','{lang}', 'procTime: {procTime}' ],
				[ 'rev_id' => $rev_id, 'lang' => $lang, 'procTime' => microtime( true ) + $time ]
			);

			return $outputPage->mSMWFactboxText = $content['text'];
		}

		$text = $this->rebuild(
			$title,
			$parserOutput,
			$checkMagicWords
		);

		$outputPage->mSMWFactboxText = $text;

		if ( $isPreview ) {
			return;
		}

		$this->addContentToCache(
			$key,
			$text,
			$rev_id,
			$lang,
			$this->featureSet
		);

		$this->logger->info(
			[ 'Factbox', 'Rebuild factbox', 'rev_id: {rev_id}', '{lang}', 'procTime: {procTime}' ],
			[ 'rev_id' => $rev_id, 'lang' => $lang, 'procTime' => microtime( true ) + $time ]
		);

		$this->entityCache->associate( $title, $key );
	}

	/**
	 * @since 2.2
	 *
	 * @param string $key
	 * @param string $text
	 * @param integer|null $revisionId
	 */
	public function addContentToCache( $key, $text, $rev_id = null, $lang = 'en', $feature_set = null ) {
		$this->saveToCache(
			$key,
			$this->makeSubCacheKey( $rev_id, $lang, $this->featureSet ),
			[
				'rev_id' => $rev_id,
				'lang'  => $lang,
				'feature_set' => $feature_set,
				'text'  => $text
			]
		);
	}

	/**
	 * Returns parsed Factbox content from either the OutputPage property
	 * or from the Cache
	 *
	 * @since 1.9
	 *
	 * @param OutputPage $outputPage
	 *
	 * @return string
	 */
	public function retrieveContent( OutputPage $outputPage ) {

		$text = '';
		$content = [];
		$title = $outputPage->getTitle();

		if ( $title instanceof Title && ( $title->isSpecialPage() || !$title->exists() ) ) {
			return $text;
		}

		if ( isset( $outputPage->mSMWFactboxText ) ) {
			$text = $outputPage->mSMWFactboxText;
		} elseif ( $title instanceof Title ) {

			$context = $outputPage->getContext();
			$lang = $context->getLanguage()->getCode();

			$rev_id = $this->findRevId(
				$title, $context->getRequest()
			);

			$sub = $this->makeSubCacheKey( $rev_id, $lang, $this->featureSet );

			$data = $this->entityCache->fetchSub(
				$this->makeCacheKey( $title ),
				$sub
			);

			$content = $this->findContentFromCache(
				$data
			);

			if ( isset( $content['text'] ) ) {
				return $content['text'];
			}
		}

		return $text;
	}

	/**
	 * Return a revisionId either from the WebRequest object (display an old
	 * revision or permalink etc.) or from the title object
	 */
	private function findRevId( Title $title, $request ) {

		if ( $request->getInt( 'diff' ) > 0 ) {
			return $request->getInt( 'diff' );
		}

		if ( $request->getInt( 'oldid' ) > 0 ) {
			return $request->getInt( 'oldid' );
		}

		return RevisionGuard::getLatestRevID( $title );
	}

	/**
	 * Processing and re-parsing of the Factbox content
	 */
	private function rebuild( Title $title, ParserOutput $parserOutput, $checkMagicWords ) {

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
		$content = '';

		if ( ( $content = $factbox->getContent() ) !== '' ) {
			$contentParser->parse( $content );
			$content = InTextAnnotationParser::removeAnnotation(
				$contentParser->getOutput()->getText()
			);
		}

		$attachmentContent = '';

		if ( ( $attachmentContent = $factbox->getAttachmentContent() ) !== '' ) {
			$contentParser->parse( $attachmentContent );
			$attachmentContent = $contentParser->getOutput()->getText();
		}

		return $factbox->tabs( $content, $attachmentContent );
	}

	private function hasCachedContent( $subKey, $rev_id, $lang, $content, $request ) {

		if ( $request->getVal( 'action' ) === 'edit' ) {
			return $this->isCached = false;
		}

		if ( $rev_id == 0 || !isset( $content['rev_id'] ) || $content['text'] === null ) {
			return $this->isCached = false;
		}

		if ( !isset( $content['lang'] ) || !isset( $content['feature_set'] ) ) {
			return $this->isCached = false;
		}

		if ( $subKey === $this->makeSubCacheKey( $content['rev_id'], $content['lang'], $content['feature_set'] ) ) {
			return $this->isCached = true;
		}

		return $this->isCached = false;
	}

	private function findContentFromCache( $data ) {

		if ( $data === false || !$this->isEnabled ) {
			return [];
		}

		$this->isCached = true;
		$this->timestamp = $data['time'];

		return HmacSerializer::uncompress( $data['content'] );
	}

	/**
	 * Cached content is serialized in an associative array following:
	 * { 'rev_id' => $revisionId, 'text' => (...) }
	 */
	private function saveToCache( $key, $subKey, array $content ) {

		$this->timestamp = wfTimestamp( TS_UNIX );
		$this->isCached = false;

		$data = [
			'time' => $this->timestamp,
			'content' => HmacSerializer::compress( $content )
		];

		// Storing as sub so that different language views for the same revision
		// can be cached together but when the entity gets flushed all keys and
		// content are evicted
		$this->entityCache->saveSub( $key, $subKey, $data, $this->cacheTTL );
	}

	private function makeSubCacheKey( ...$args ) {
		return md5( json_encode( $args ) );
	}

}

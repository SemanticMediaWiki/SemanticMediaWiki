<?php

namespace SMW\Factbox;

use ParserOutput;
use OutputPage;
use Onoi\Cache\Cache;
use SMW\ApplicationFactory;
use Title;

/**
 * Factbox output caching
 *
 * Enable ($smwgFactboxUseCache) to use a CacheStore to avoid unaltered
 * content being re-parsed every time the OutputPage hook is executed
 *
 * @license GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class CachedFactbox {

	/**
	 * @var Cache
	 */
	private $cache = null;

	/**
	 * @var CacheFactory
	 */
	private $cacheFactory = null;

	/**
	 * @var boolean
	 */
	private $isCached = false;

	/**
	 * @var integer
	 */
	private $timestamp;

	/**
	 * @since 1.9
	 *
	 * @param Cache|null $cache
	 * @param stdClass $cacheOptions
	 */
	public function __construct( Cache $cache = null, \stdClass $cacheOptions ) {
		$this->cache = $cache;
		$this->cacheOptions = $cacheOptions;
		$this->cacheFactory = ApplicationFactory::getInstance()->newCacheFactory();

		if ( $this->cache === null ) {
			$this->cache = $this->cacheFactory->newNullCache();
		}
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
	 * @since 2.2
	 *
	 * @return integer
	 */
	public function getTimestamp() {
		return $this->timestamp;
	}

	/**
	 * Prepare and update the OutputPage property
	 *
	 * Factbox content is either retrived from a CacheStore or re-parsed from
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
	public function prepareFactboxContent( OutputPage &$outputPage, ParserOutput $parserOutput ) {

		$content = '';
		$title = $outputPage->getTitle();
		$revisionId = $this->getRevisionId( $title, $outputPage->getContext() );

		$key = $this->cacheFactory->getFactboxCacheKey(
			$title->getArticleID()
		);

		if ( $this->cache->contains( $key ) ) {
			$content = $this->retrieveFromCache( $key );
		}

		if ( $this->cacheIsAvailableFor( $revisionId, $content, $outputPage->getContext() ) ) {
			$this->isCached = true;
			$outputPage->mSMWFactboxText = $content['text'];
			return;
		}

		$this->isCached = false;

		$text = $this->rebuild(
			$title,
			$parserOutput,
			$outputPage->getContext()
		);

		$this->addContentToCache(
			$key,
			$text,
			$revisionId
		);

		$outputPage->mSMWFactboxText = $text;
	}

	/**
	 * @since 2.2
	 *
	 * @param string $key
	 * @param string $text
	 * @param integer|null $revisionId
	 */
	public function addContentToCache( $key, $text, $revisionId = null ) {
		$this->saveToCache(
			$key,
			array(
				'revId' => $revisionId,
				'text'  => $text
			)
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
		$title = $outputPage->getTitle();

		if ( $title instanceof Title && ( $title->isSpecialPage() || !$title->exists() ) ) {
			return $text;
		}

		if ( isset( $outputPage->mSMWFactboxText ) ) {
			$text = $outputPage->mSMWFactboxText;
		} elseif ( $title instanceof Title ) {

			$key = $this->cacheFactory->getFactboxCacheKey(
				$title->getArticleID()
			);

			$content = $this->retrieveFromCache( $key );
			$text = isset( $content['text'] ) ? $content['text'] : '';
		}

		return $text;
	}

	/**
	 * Return a revisionId either from the WebRequest object (display an old
	 * revision or permalink etc.) or from the title object
	 */
	private function getRevisionId( Title $title, $requestContext ) {

		if ( $requestContext->getRequest()->getCheck( 'oldid' ) ) {
			return (int)$requestContext->getRequest()->getVal( 'oldid' );
		}

		return $title->getLatestRevID();
	}

	/**
	 * Processing and reparsing of the Factbox content
	 */
	private function rebuild( Title $title, ParserOutput $parserOutput, $requestContext ) {

		$text = null;
		$applicationFactory = ApplicationFactory::getInstance();

		$factbox = $applicationFactory->newFactboxFactory()->newFactbox(
			$applicationFactory->newParserData( $title, $parserOutput ),
			$requestContext
		);

		$factbox->useInPreview( $requestContext->getRequest()->getCheck( 'wpPreview' ) );

		if ( $factbox->doBuild()->isVisible() ) {

			$contentParser = $applicationFactory->newContentParser( $title );
			$contentParser->skipInTextAnnotationParser();
			$contentParser->parse( $factbox->getContent() );

			$text = $contentParser->getOutput()->getText();
		}

		return $text;
	}

	private function cacheIsAvailableFor( $revId, $content, $requestContext ) {

		if ( ApplicationFactory::getInstance()->getSettings()->get( 'smwgShowFactbox' ) === SMW_FACTBOX_HIDDEN ) {
			return false;
		}

		if ( $requestContext->getRequest()->getVal( 'action' ) === 'edit' ) {
			return false;
		}

		if ( $revId !== 0 && isset( $content['revId'] ) && ( $content['revId'] === $revId ) && $content['text'] !== null ) {
			return true;
		}

		return false;
	}

	private function retrieveFromCache( $key ) {

		if ( !$this->cache->contains( $key ) || !$this->cacheOptions->useCache ) {
			return array();
		}

		$data = $this->cache->fetch( $key );

		$this->isCached = true;
		$this->timestamp = $data['time'];

		return unserialize( $data['content'] );
	}

	/**
	 * Cached content is serialized in an associative array following:
	 * { 'revId' => $revisionId, 'text' => (...) }
	 */
	private function saveToCache( $key, array $content ) {

		$this->timestamp = wfTimestamp( TS_UNIX );
		$this->isCached = false;

		$data = array(
			'time' => $this->timestamp,
			'content' => serialize( $content )
		);

		$this->cache->save( $key, $data, $this->cacheOptions->ttl );
	}

}

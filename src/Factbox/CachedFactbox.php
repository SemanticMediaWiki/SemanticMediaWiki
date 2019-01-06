<?php

namespace SMW\Factbox;

use Onoi\Cache\Cache;
use OutputPage;
use ParserOutput;
use SMW\ApplicationFactory;
use SMW\Parser\InTextAnnotationParser;
use Title;
use Language;

/**
 * Factbox output caching
 *
 * @license GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class CachedFactbox {

	const CACHE_NAMESPACE = 'smw:fc';

	/**
	 * @var Cache
	 */
	private $cache;

	/**
	 * @var boolean
	 */
	private $isCached = false;

	/**
	 * @var boolean
	 */
	private $isEnabled = true;

	/**
	 * @var integer
	 */
	private $featureSet = 0;

	/**
	 * @var integer
	 */
	private $expiryInSeconds = 0;

	/**
	 * @var integer
	 */
	private $timestamp;

	/**
	 * @since 1.9
	 *
	 * @param Cache $cache
	 */
	public function __construct( Cache $cache ) {
		$this->cache = $cache;
	}

	/**
	 * @since 3.0
	 *
	 * @param Title|integer $id
	 *
	 * @return string
	 */
	public static function makeCacheKey( $id ) {

		if ( $id instanceof Title ) {
			$id = $id->getArticleID();
		}

		return smwfCacheKey( self::CACHE_NAMESPACE, $id );
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
	 * @since 2.5
	 *
	 * @return boolean
	 */
	public function setExpiryInSeconds( $expiryInSeconds ) {
		$this->expiryInSeconds = $expiryInSeconds;
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
	 * @param Language $language
	 * @param ParserOutput $parserOutput
	 */
	public function prepareFactboxContent( OutputPage &$outputPage, Language $language, ParserOutput $parserOutput ) {

		$content = '';
		$title = $outputPage->getTitle();

		$rev_id = $this->findRevId( $title, $outputPage->getContext() );
		$lang = $language->getCode();

		$key = self::makeCacheKey( $title );

		if ( $this->cache->contains( $key ) ) {
			$content = $this->retrieveFromCache( $key );
		}

		if ( $this->hasCachedContent( $rev_id, $lang, $content, $outputPage->getContext() ) ) {
			return $outputPage->mSMWFactboxText = $content['text'];
		}

		$text = $this->rebuild(
			$title,
			$parserOutput,
			$outputPage->getContext()
		);

		$this->addContentToCache(
			$key,
			$text,
			$rev_id,
			$lang,
			$this->featureSet
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
	public function addContentToCache( $key, $text, $revisionId = null, $lang = 'en', $fset = null ) {
		$this->saveToCache(
			$key,
			[
				'revId' => $revisionId,
				'lang'  => $lang,
				'fset'  => $fset,
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
		$title = $outputPage->getTitle();

		if ( $title instanceof Title && ( $title->isSpecialPage() || !$title->exists() ) ) {
			return $text;
		}

		if ( isset( $outputPage->mSMWFactboxText ) ) {
			$text = $outputPage->mSMWFactboxText;
		} elseif ( $title instanceof Title ) {

			$content = $this->retrieveFromCache(
				self::makeCacheKey( $title )
			);

			$text = isset( $content['text'] ) ? $content['text'] : '';
		}

		return $text;
	}

	/**
	 * Return a revisionId either from the WebRequest object (display an old
	 * revision or permalink etc.) or from the title object
	 */
	private function findRevId( Title $title, $requestContext ) {

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

		$factbox = $applicationFactory->singleton( 'FactboxFactory' )->newFactbox(
			$title,
			$parserOutput
		);

		$factbox->setPreviewFlag(
			$requestContext->getRequest()->getCheck( 'wpPreview' )
		);

		if ( $factbox->doBuild()->isVisible() ) {

			$contentParser = $applicationFactory->newContentParser( $title );
			$contentParser->parse( $factbox->getContent() );

			$text = InTextAnnotationParser::removeAnnotation(
				$contentParser->getOutput()->getText()
			);

			$text = $factbox->tabs( $text );
		}

		return $text;
	}

	private function hasCachedContent( $revId, $lang, $content, $requestContext ) {

		if ( $requestContext->getRequest()->getVal( 'action' ) === 'edit' ) {
			return $this->isCached = false;
		}

		if ( $revId !== 0 && isset( $content['revId'] ) && ( $content['revId'] === $revId ) && $content['text'] !== null ) {

			if (
				( isset( $content['lang'] ) && $content['lang'] === $lang ) &&
				( isset( $content['fset'] ) && $content['fset'] === $this->featureSet )  ) {
				return $this->isCached = true;
			}
		}

		return $this->isCached = false;
	}

	private function retrieveFromCache( $key ) {

		if ( !$this->cache->contains( $key ) || !$this->isEnabled ) {
			return [];
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

		$data = [
			'time' => $this->timestamp,
			'content' => serialize( $content )
		];

		$this->cache->save( $key, $data, $this->expiryInSeconds );
	}

}

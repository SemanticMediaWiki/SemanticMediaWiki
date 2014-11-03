<?php

namespace SMW;

use ParserOutput;
use OutputPage;
use Title;
use Html;

/**
 * Factbox output caching
 *
 * Enable ($smwgFactboxUseCache) to use a CacheStore to avoid unaltered
 * content being re-parsed every time the OutputPage hook is executed
 *
 * @ingroup SMW
 *
 * @license GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class FactboxCache {

	/**
	 * @var OutputPage
	 */
	protected $outputPage = null;

	/**
	 * @var boolean
	 */
	protected $isCached = false;

	/**
	 * @since 1.9
	 *
	 * @param OutputPage &$outputPage
	 *
	 * @return FactboxCache
	 */
	public function __construct( OutputPage &$outputPage ) {
		$this->outputPage = $outputPage;
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
	 * Cached content is stored in an associative array following:
	 * { 'revId' => $revisionId, 'text' => (...) }
	 *
	 * @since 1.9
	 *
	 * @param ParserOutput $parserOutput
	 */
	public function process( ParserOutput $parserOutput ) {

		Profiler::In( __METHOD__ );

		$title        = $this->outputPage->getTitle();
		$revId        = $this->getRevisionId( $title );
		$resultMapper = $this->newResultMapper( $title->getArticleID() );
		$content      = $resultMapper->fetchFromCache();

		if ( $this->cacheIsAvailableFor( $revId, $content ) ) {

			$this->isCached = true;
			$this->outputPage->mSMWFactboxText = $content['text'];

		} else {

			$this->isCached = false;

			$text = $this->rebuild(
				$title,
				$parserOutput,
				$this->outputPage->getContext()
			);

			$resultMapper->recache( array(
				'revId' => $revId,
				'text'  => $text
			) );

			$this->outputPage->mSMWFactboxText = $text;
		}

		Profiler::Out( __METHOD__ );
	}

	/**
	 * Returns parsed Factbox content from either the OutputPage property
	 * or from the CacheStore
	 *
	 * @since 1.9
	 *
	 * @return string
	 */
	public function retrieveContent() {

		$text = '';
		$title = $this->outputPage->getTitle();

		if ( $title instanceof Title && ( $title->isSpecialPage() || !$title->exists() ) ) {
			return $text;
		}

		if ( isset( $this->outputPage->mSMWFactboxText ) ) {
			$text = $this->outputPage->mSMWFactboxText;
		} else if ( $title instanceof Title ) {
			$content = $this->newResultMapper( $title->getArticleID() )->fetchFromCache();
			$text = isset( $content['text'] ) ? $content['text'] : '';
		}

		return $text;
	}

	/**
	 * Returns whether or not results have been cached
	 *
	 * @since 1.9
	 *
	 * @return boolean
	 */
	public function isCached() {
		return $this->isCached;
	}

	/**
	 * Returns a CacheIdGenerator object
	 *
	 * @since 1.9
	 *
	 * @return CacheIdGenerator
	 */
	public static function newCacheId( $pageId ) {
		return new CacheIdGenerator( $pageId, 'factbox' );
	}

	/**
	 * Returns a CacheableResultMapper object
	 *
	 * @since 1.9
	 *
	 * @param integer $pageId
	 *
	 * @return CacheableResultMapper
	 */
	public function newResultMapper( $pageId ) {

		$settings = ApplicationFactory::getInstance()->getSettings();

		return new CacheableResultMapper( new SimpleDictionary( array(
			'id'      => $pageId,
			'prefix'  => 'factbox',
			'type'    => $settings->get( 'smwgCacheType' ),
			'enabled' => $settings->get( 'smwgFactboxUseCache' ),
			'expiry'  => 0
		) ) );
	}

	/**
	 * Return a revisionId either from the WebRequest object (display an old
	 * revision or permalink etc.) or from the title object
	 *
	 * @since  1.9
	 *
	 * @param  Title $title
	 *
	 * @return integer
	 */
	protected function getRevisionId( Title $title ) {

		if ( $this->outputPage->getContext()->getRequest()->getCheck( 'oldid' ) ) {
			return (int)$this->outputPage->getContext()->getRequest()->getVal( 'oldid' );
		}

		return $title->getLatestRevID();
	}

	/**
	 * Processing and reparsing of the Factbox content
	 *
	 * @since 1.9
	 *
	 * @param  Factbox $factbox
	 *
	 * @return string|null
	 */
	protected function rebuild( Title $title, ParserOutput $parserOutput, $requestContext ) {

		$text = null;
		$applicationFactory = ApplicationFactory::getInstance();

		$factbox = $applicationFactory->newFactboxBuilder()->newFactbox(
			$applicationFactory->newParserData( $title, $parserOutput ),
			$requestContext
		);

		$factbox->useInPreview( $requestContext->getRequest()->getCheck( 'wpPreview' ) );

		if ( $factbox->doBuild()->isVisible() ) {

			$contentParser = $applicationFactory->newContentParser( $title );
			$contentParser->parse( $factbox->getContent() );

			$text = $contentParser->getOutput()->getText();
		}

		return $text;
	}

	protected function cacheIsAvailableFor( $revId, $content ) {

		if ( ApplicationFactory::getInstance()->getSettings()->get( 'smwgShowFactbox' ) === SMW_FACTBOX_HIDDEN ) {
			return false;
		}

		if ( $this->outputPage->getContext()->getRequest()->getVal( 'action' ) === 'edit' ) {
			return false;
		}

		if ( $revId !== 0 && isset( $content['revId'] ) && ( $content['revId'] === $revId ) && $content['text'] !== null ) {
			return true;
		}

		return false;
	}

}

<?php

namespace SMW;

use OutputPage;
use Title;

/**
 * Factbox output factory class
 *
 * @file
 *
 * @license GNU GPL v2+
 * @since   1.9
 *
 * @author mwjames
 */

/**
 * Factbox output factory class
 *
 * Enabling ($smwgFactboxUseCache) to use the CacheStore avoids non-changed
 * content being re-parsed every time the hook is executed where in reality
 * page content has not been altered.
 *
 * @ingroup SMW
 */
class FactboxPresenter extends DependencyInjector {

	/** @var Factbox */
	protected $outputPage = null;

	/** @var boolean */
	protected $isCached = false;

	/**
	 * @since 1.9
	 *
	 * @param OutputPage &$outputPage
	 *
	 * @return FactboxPresenter
	 */
	public function __construct( OutputPage &$outputPage ) {
		$this->outputPage = $outputPage;
	}

	/**
	 * Prepare and update the OutputPage object
	 *
	 * Factbox content is either retrived from CacheStore or re-parsed from
	 * the invoked Factbox object
	 *
	 * Altered content is tracked using the revision id (getTouched() is not a
	 * good measure for comparison where getLatestRevID() only changes after a
	 * content modification has occurred).
	 *
	 * Cached content is stored in an associative array following:
	 * { 'revId' => $revisionId, 'text' => (...) }
	 *
	 * @since 1.9
	 *
	 * @param ParserData $parserData
	 */
	public function process( ParserData $parserData ) {

		Profiler::In( __METHOD__ );

		$title        = $this->outputPage->getTitle();
		$resultMapper = $this->getResultMapper( $title->getArticleID() );
		$content      = $resultMapper->fetchFromCache();

		if ( isset( $content['revId'] ) && ( $content['revId'] === $title->getLatestRevID() ) ) {

			$this->isCached = true;
			$this->outputPage->mSMWFactboxText = $content['text'];

		} else {

			$this->isCached = false;
			$this->outputPage->mSMWFactboxText = $this->parse( $parserData );
			$resultMapper->recache( array(
				'revId' => $title->getLatestRevID(),
				'text'  => $this->outputPage->mSMWFactboxText
			) );
		}

		Profiler::Out( __METHOD__ );
	}

	/**
	 * Returns parsed Factbox content from either the OutputPage
	 * or the CacheStore
	 *
	 * @since 1.9
	 *
	 * @return string
	 */
	public function retrieveContent() {

		$text = '';

		if ( isset( $this->outputPage->mSMWFactboxText ) ) {
			$text = $this->outputPage->mSMWFactboxText;
		} else if ( $this->outputPage->getTitle() instanceof Title &&
			!$this->outputPage->getTitle()->isSpecialPage() ) {
			$content = $this->getResultMapper( $this->outputPage->getTitle()->getArticleID() )->fetchFromCache();
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
	public static function newCacheIdGenerator( $pageId ) {
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
	public function getResultMapper( $pageId ) {

		/**
		 * @var Settings $settings
		 */
		$settings = $this->getDependencyBuilder()->newObject( 'Settings' );

		return new CacheableResultMapper( new SimpleDictionary( array(
			'id'      => $pageId,
			'prefix'  => 'factbox',
			'type'    => $settings->get( 'smwgCacheType' ),
			'enabled' => $settings->get( 'smwgFactboxUseCache' ),
			'expiry'  => 0
		) ) );
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
	protected function parse( ParserData $parserData ) {

		$text = null;

		/**
		 * @var Factbox $factbox
		 */
		$factbox = $this->getDependencyBuilder()->newObject( 'Factbox', array(
			'ParserData'     => $parserData,
			'RequestContext' => $this->outputPage->getContext()
		) );

		if ( $factbox->doBuild()->isVisible() ) {

			/**
			 * @var ContentParser $contentParser
			 */
			$contentParser = $this->getDependencyBuilder()->newObject( 'ContentParser', array(
				'Title' => $this->outputPage->getTitle()
			) );

			$contentParser->setText( $factbox->getContent() )->parse(); // old style
		//	$contentParser->parse( $factbox->getContent() );
			$text = $contentParser->getOutput()->getText();

		}

		return $text;
	}

}

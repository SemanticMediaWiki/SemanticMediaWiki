<?php

namespace SMW\MediaWiki\Jobs;

use SMW\MediaWiki\Job;
use SMW\ApplicationFactory;
use Title;
use WikiPage;

/**
 * @license GNU GPL v2+
 * @since 3.1
 *
 * @author mwjames
 */
class ParserCachePurgeJob extends Job {

	/**
	 * @since 3.1
	 *
	 * @param Title $title
	 * @param array $params job parameters
	 */
	public function __construct( Title $title, $params = [] ) {
		parent::__construct( 'smw.parserCachePurgeJob', $title, $params );
		$this->removeDuplicates = true;
	}

	/**
	 * @since 3.1
	 *
	 * @param WikiPage|null $page
	 */
	public function updateParserCache( WikiPage $page = null ) {

		$applicationFactory = ApplicationFactory::getInstance();
		$logger = $applicationFactory->getMediaWikiLogger();

		if ( $this->hasParameter( 'action' ) ) {
			$causeAction = $this->getParameter( 'action' );
		} else {
			$causeAction = 'unknown';
		}

		if ( $this->hasParameter( 'user' ) ) {
			$causeAgent = $this->getParameter( 'user' );
		} else {
			$causeAgent = $GLOBALS['wgUser']->getName();
		}

		if ( $page === null ) {
			$page = $this->newWikiPage( $this->getTitle() );
		}

		$title = $page->getTitle();

		// MW 1.32+
		// @see ApiPurge
		if ( method_exists( $page, 'updateParserCache' ) ) {
			$page->updateParserCache(
				[
					'causeAction' => $causeAction,
					'causeAgent' => $causeAgent
				]
			);
		} else {
			$this->runLegacyUpdateParserCache( $page );
		}

		$logger->info(
			[ 'ParserCache', 'Forced update for: {title}', 'causeAction: {causeAction}' ],
			[ 'causeAction' => $causeAction, 'title' => $title->getPrefixedText(), 'role' => 'production' ]
		);
	}

	/**
	 * @see Job::run
	 *
	 * @since 3.1
	 */
	public function run() {

		$page = $this->newWikiPage( $this->getTitle() );
		$page->doPurge();
		$this->updateParserCache( $page );

		return true;
	}

	protected function newWikiPage( $title ) {
		return WikiPage::factory( $title );
	}

	/**
	 * Only for MW 1.31
	 */
	private function runLegacyUpdateParserCache( $page ) {

		$applicationFactory = ApplicationFactory::getInstance();
		$enableParserCache = true;

		$popts = $page->makeParserOptions( 'canonical' );
		$content = $page->getContent( \Revision::RAW );

		if ( $content ) {
			$p_result = $content->getParserOutput(
				$page->getTitle(),
				$page->getLatest(),
				$popts,
				$enableParserCache
			);

			if ( $enableParserCache ) {
				$parserCache = $applicationFactory->singleton( 'ParserCache' );
				$parserCache->save( $p_result, $page, $popts );
			}
		}
	}

}

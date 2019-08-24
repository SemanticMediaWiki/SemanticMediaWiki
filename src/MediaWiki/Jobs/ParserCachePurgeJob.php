<?php

namespace SMW\MediaWiki\Jobs;

use SMW\MediaWiki\Job;
use SMW\ApplicationFactory;
use Title;

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
	 * @see Job::run
	 *
	 * @since 3.1
	 */
	public function run() {

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

		$page = $this->newWikiPage( $this->getTitle() );
		$page->doPurge();

		// MW 1.32+
		if ( method_exists( $page, 'updateParserCache' ) ) {
			$page->updateParserCache(
				[
					'causeAction' => $causeAction,
					'causeAgent' => $causeAgent
				]
			);
		}

		return true;
	}

	protected function newWikiPage( $title ) {
		return \WikiPage::factory( $title );
	}

}

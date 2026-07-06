<?php

namespace SMW\MediaWiki\Jobs;

use MediaWiki\Context\RequestContext;
use MediaWiki\Logger\LoggerFactory;
use MediaWiki\Title\Title;
use Psr\Log\LoggerInterface;
use SMW\MediaWiki\Job;
use SMW\Services\ServicesFactory as ApplicationFactory;
use WikiPage;

/**
 * Partial DI: ParserCachePurgeJob still resolves its PSR-3 logger and
 * page-creator lazily (via `LoggerFactory::getInstance( 'smw' )` and
 * `ApplicationFactory`) because `PageCreator` is not yet registered on the
 * global container.
 *
 * @license GPL-2.0-or-later
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
	public function updateParserCache( ?WikiPage $page = null ): void {
		if ( $this->hasParameter( 'action' ) ) {
			$causeAction = $this->getParameter( 'action' );
		} else {
			$causeAction = 'unknown';
		}

		if ( $this->hasParameter( 'user' ) ) {
			$causeAgent = $this->getParameter( 'user' );
		} else {
			$causeAgent = RequestContext::getMain()->getUser()->getName();
		}

		if ( $page === null ) {
			$page = $this->newWikiPage( $this->getTitle() );
		}

		$title = $page->getTitle();

		$page->updateParserCache(
			[
				'causeAction' => $causeAction,
				'causeAgent' => $causeAgent
			]
		);

		$this->getLogger()->info(
			'ParserCache Forced update for: {title} causeAction: {causeAction}',
			[
				'causeAction' => $causeAction,
				'title' => $title->getPrefixedText(),
				'role' => 'production'
			]
		);
	}

	/**
	 * @see Job::run
	 *
	 * @since 3.1
	 */
	public function run(): bool {
		$page = $this->newWikiPage( $this->getTitle() );
		$page->doPurge();
		$this->updateParserCache( $page );

		return true;
	}

	protected function newWikiPage( Title $title ): WikiPage {
		return ApplicationFactory::getInstance()->newPageCreator()->createPage( $title );
	}

	private function getLogger(): LoggerInterface {
		if ( $this->logger === null ) {
			$this->logger = LoggerFactory::getInstance( 'smw' );
		}

		return $this->logger;
	}
}

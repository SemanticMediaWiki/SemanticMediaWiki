<?php

namespace SMW\Events;

use Onoi\EventDispatcher\EventListener;
use Onoi\EventDispatcher\DispatchContext;
use SMW\Query\Result\CachedQueryResultPrefetcher;
use SMW\DIWikiPage;
use Psr\Log\LoggerAwareTrait;

/**
 * @license GNU GPL v2+
 * @since 3.1
 *
 * @author mwjames
 */
class InvalidateResultCacheEventListener implements EventListener {

	use LoggerAwareTrait;

	/**
	 * @var CachedQueryResultPrefetcher
	 */
	private $cachedQueryResultPrefetcher;

	/**
	 * @since 3.1
	 */
	public function __construct( CachedQueryResultPrefetcher $cachedQueryResultPrefetcher ) {
		$this->cachedQueryResultPrefetcher = $cachedQueryResultPrefetcher;
	}

	/**
	 * @since 3.1
	 *
	 * {@inheritDoc}
	 */
	public function execute( DispatchContext $dispatchContext = null ) {

		if ( $dispatchContext === null ) {
			return;
		}

		if ( $dispatchContext->has( 'title' ) ) {
			$subject = DIWikiPage::newFromTitle( $dispatchContext->get( 'title' ) );
		} else{
			$subject = $dispatchContext->get( 'subject' );
		}

		if ( $dispatchContext->has( 'context' ) ) {
			$context = $dispatchContext->get( 'context' );
		} else {
			$context = 'n/a';
		}

		if ( $dispatchContext->has( 'dependency_list' ) ) {
			$items = $dispatchContext->get( 'dependency_list' );
		} else {
			$items = [ $subject ];
		}

		$this->cachedQueryResultPrefetcher->invalidate(
			$items,
			$context
		);

		$this->logger->info(
			[ 'Event', 'InvalidateResultCache', "{caused_by}", "{subject}" ],
			[ 'role' => 'user', 'caused_by' => $context, 'subject' => $subject ]
		);
	}

	/**
	 * @since 3.1
	 *
	 * {@inheritDoc}
	 */
	public function isPropagationStopped() {
		return true;
	}

}

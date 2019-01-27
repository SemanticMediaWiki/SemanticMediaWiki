<?php

namespace SMW\Events;

use Onoi\EventDispatcher\EventListener;
use Onoi\EventDispatcher\DispatchContext;
use SMW\Query\Result\CachedQueryResultPrefetcher;
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

		$context = $dispatchContext->get( 'context' );
		$subject = $dispatchContext->get( 'subject' );

		$this->cachedQueryResultPrefetcher->invalidate(
			$dispatchContext->get( 'dependency_list' ),
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

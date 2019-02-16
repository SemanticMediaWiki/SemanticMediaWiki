<?php

namespace SMW\Events;

use Onoi\EventDispatcher\EventListener;
use Onoi\EventDispatcher\DispatchContext;
use SMW\EntityCache;
use Psr\Log\LoggerAwareTrait;

/**
 * @license GNU GPL v2+
 * @since 3.1
 *
 * @author mwjames
 */
class InvalidateEntityCacheEventListener implements EventListener {

	use LoggerAwareTrait;

	/**
	 * @var EntityCache
	 */
	private $entityCache;

	/**
	 * @since 3.1
	 */
	public function __construct( EntityCache $entityCache ) {
		$this->entityCache = $entityCache;
	}

	/**
	 * @since 3.1
	 *
	 * {@inheritDoc}
	 */
	public function execute( DispatchContext $dispatchContext = null ) {

		$title = $dispatchContext->get( 'title' );
		$context = $dispatchContext->get( 'context' );

		$this->entityCache->invalidate( $title );
		$subject = $title->getPrefixedDBKey();

		$this->logger->info(
			[ 'Event', 'InvalidateEntityCache', "{caused_by}", "{subject}" ],
			[ 'role' => 'user', 'caused_by' => $context, 'subject' => $subject ]
		);
	}

	/**
	 * @since 3.1
	 *
	 * {@inheritDoc}
	 */
	public function isPropagationStopped() {
		return false;
	}

}

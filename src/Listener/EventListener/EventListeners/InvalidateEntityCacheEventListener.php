<?php

namespace SMW\Listener\EventListener\EventListeners;

use Onoi\EventDispatcher\DispatchContext;
use Onoi\EventDispatcher\EventListener;
use Psr\Log\LoggerAwareTrait;
use SMW\EntityCache;

/**
 * @license GPL-2.0-or-later
 * @since 3.1
 *
 * @author mwjames
 */
class InvalidateEntityCacheEventListener implements EventListener {

	use LoggerAwareTrait;

	const EVENT_ID = 'InvalidateEntityCache';

	/**
	 * @since 3.1
	 */
	public function __construct( private EntityCache $entityCache ) {
	}

	/**
	 * @since 3.1
	 *
	 * {@inheritDoc}
	 */
	public function execute( ?DispatchContext $dispatchContext = null ): void {
		if ( $dispatchContext->has( 'subject' ) ) {
			$subject = $dispatchContext->get( 'subject' );
			$id = $subject->getHash();
		} else {
			$subject = $dispatchContext->get( 'title' );
			$id = $subject->getPrefixedDBKey();
		}

		$context = $dispatchContext->get( 'context' );
		$this->entityCache->invalidate( $subject );

		$this->logger->info(
			[ 'Event', 'InvalidateEntityCache', "{caused_by}", "{id}" ],
			[ 'role' => 'user', 'caused_by' => $context, 'id' => $id ]
		);
	}

	/**
	 * @since 3.1
	 *
	 * {@inheritDoc}
	 */
	public function isPropagationStopped(): bool {
		return false;
	}

}

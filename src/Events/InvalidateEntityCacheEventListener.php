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
	public function isPropagationStopped() {
		return false;
	}

}

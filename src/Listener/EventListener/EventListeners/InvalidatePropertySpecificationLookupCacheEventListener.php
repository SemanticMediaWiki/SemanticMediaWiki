<?php

namespace SMW\Listener\EventListener\EventListeners;

use Onoi\EventDispatcher\EventListener;
use Onoi\EventDispatcher\DispatchContext;
use SMW\PropertySpecificationLookup;
use Psr\Log\LoggerAwareTrait;

/**
 * @license GNU GPL v2+
 * @since 3.2
 *
 * @author mwjames
 */
class InvalidatePropertySpecificationLookupCacheEventListener implements EventListener {

	use LoggerAwareTrait;

	const EVENT_ID = 'InvalidatePropertySpecificationLookupCache';

	/**
	 * @var PropertySpecificationLookup
	 */
	private $propertySpecificationLookup;

	/**
	 * @since 3.2
	 *
	 * @param PropertySpecificationLookup $propertySpecificationLookup
	 */
	public function __construct( PropertySpecificationLookup $propertySpecificationLookup ) {
		$this->propertySpecificationLookup = $propertySpecificationLookup;
	}

	/**
	 * @since 3.2
	 *
	 * {@inheritDoc}
	 */
	public function execute( DispatchContext $dispatchContext = null ) {

		$subject = $dispatchContext->get( 'subject' );
		$context = $dispatchContext->get( 'context' );

		$this->propertySpecificationLookup->invalidateCache(
			$subject
		);

		$this->logger->info(
			[ 'Event', 'InvalidatePropertySpecificationLookupCache', "{triggered_by}", "{id}" ],
			[ 'role' => 'user', 'triggered_by' => $context, 'id' => $subject->getHash() ]
		);
	}

	/**
	 * @since 3.2
	 *
	 * {@inheritDoc}
	 */
	public function isPropagationStopped() {
		return false;
	}

}

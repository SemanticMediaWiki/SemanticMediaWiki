<?php

namespace SMW\Listener\EventListener;

use MediaWiki\MediaWikiServices;
use Onoi\EventDispatcher\EventListenerCollection;
use SMW\Export\Exporter;
use SMW\Listener\EventListener\EventListeners\InvalidateEntityCacheEventListener;
use SMW\Listener\EventListener\EventListeners\InvalidatePropertySpecificationLookupCacheEventListener;
use SMW\Listener\EventListener\EventListeners\InvalidateResultCacheEventListener;
use SMW\Query\QueryComparator;
use SMW\Services\ServicesFactory as ApplicationFactory;

/**
 * @license GPL-2.0-or-later
 * @since 2.2
 *
 * @author mwjames
 */
class EventListenerRegistry implements EventListenerCollection {

	/**
	 * @since 2.2
	 */
	public function __construct( private readonly EventListenerCollection $eventListenerCollection ) {
	}

	/**
	 * @see  EventListenerCollection::getCollection
	 *
	 * @since 2.2
	 */
	public function getCollection() {
		$applicationFactory = ApplicationFactory::getInstance();
		$logger = $applicationFactory->getMediaWikiLogger();

		$invalidateResultCacheEventListener = $applicationFactory->create(
			'InvalidateResultCacheEventListener'
		);

		$invalidateResultCacheEventListener->setLogger(
			$logger
		);

		$this->eventListenerCollection->registerListener(
			InvalidateResultCacheEventListener::EVENT_ID,
			$invalidateResultCacheEventListener
		);

		$invalidateEntityCacheEventListener = $applicationFactory->create(
			'InvalidateEntityCacheEventListener'
		);

		$invalidateEntityCacheEventListener->setLogger(
			$logger
		);

		$this->eventListenerCollection->registerListener(
			InvalidateEntityCacheEventListener::EVENT_ID,
			$invalidateEntityCacheEventListener
		);

		$invalidatePropertySpecificationLookupCacheEventListener = $applicationFactory->create(
			'InvalidatePropertySpecificationLookupCacheEventListener'
		);

		$invalidatePropertySpecificationLookupCacheEventListener->setLogger(
			$logger
		);

		$this->eventListenerCollection->registerListener(
			InvalidatePropertySpecificationLookupCacheEventListener::EVENT_ID,
			$invalidatePropertySpecificationLookupCacheEventListener
		);

		$this->addListenersToCollection();

		MediaWikiServices::getInstance()
			->getHookContainer()
			->run( 'SMW::Event::RegisterEventListeners', [ $this->eventListenerCollection ] );

		return $this->eventListenerCollection->getCollection();
	}

	private function addListenersToCollection(): EventListenerCollection {
		$this->eventListenerCollection->registerCallback(
			'exporter.reset', static function (): void {
				Exporter::getInstance()->clear();
			}
		);

		$this->eventListenerCollection->registerCallback(
			'query.comparator.reset', static function (): void {
				QueryComparator::getInstance()->clear();
			}
		);

		return $this->eventListenerCollection;
	}

}

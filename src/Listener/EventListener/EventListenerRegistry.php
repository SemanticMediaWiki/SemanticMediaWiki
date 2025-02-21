<?php

namespace SMW\Listener\EventListener;

use MediaWiki\MediaWikiServices;
use Onoi\EventDispatcher\EventListenerCollection;
use SMW\Listener\EventListener\EventListeners\InvalidateEntityCacheEventListener;
use SMW\Listener\EventListener\EventListeners\InvalidatePropertySpecificationLookupCacheEventListener;
use SMW\Listener\EventListener\EventListeners\InvalidateResultCacheEventListener;
use SMW\Query\QueryComparator;
use SMW\Services\ServicesFactory as ApplicationFactory;
use SMWExporter as Exporter;

/**
 * @license GPL-2.0-or-later
 * @since 2.2
 *
 * @author mwjames
 */
class EventListenerRegistry implements EventListenerCollection {

	/**
	 * @var EventListenerCollection
	 */
	private $eventListenerCollection = null;

	/**
	 * @var LoggerInterface
	 */
	private $logger;

	/**
	 * @since 2.2
	 *
	 * @param EventListenerCollection $eventListenerCollection
	 */
	public function __construct( EventListenerCollection $eventListenerCollection ) {
		$this->eventListenerCollection = $eventListenerCollection;
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

	private function addListenersToCollection() {
		$this->logger = ApplicationFactory::getInstance()->getMediaWikiLogger();

		$this->eventListenerCollection->registerCallback(
			'exporter.reset', static function () {
				Exporter::getInstance()->clear();
			}
		);

		$this->eventListenerCollection->registerCallback(
			'query.comparator.reset', static function () {
				QueryComparator::getInstance()->clear();
			}
		);

		return $this->eventListenerCollection;
	}

}

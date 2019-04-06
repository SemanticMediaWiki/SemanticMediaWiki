<?php

namespace SMW;

use Onoi\EventDispatcher\EventListenerCollection;
use SMW\Query\QueryComparator;
use SMWExporter as Exporter;

/**
 * @license GNU GPL v2+
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
		$invalidateResultCacheEventListener = $applicationFactory->create( 'InvalidateResultCacheEventListener' );

		$invalidateResultCacheEventListener->setLogger(
			$applicationFactory->getMediaWikiLogger()
		);

		$this->eventListenerCollection->registerListener( 'InvalidateResultCache', $invalidateResultCacheEventListener );

		$invalidateEntityCacheEventListener = $applicationFactory->create( 'InvalidateEntityCacheEventListener' );

		$invalidateEntityCacheEventListener->setLogger(
			$applicationFactory->getMediaWikiLogger()
		);

		$this->eventListenerCollection->registerListener( 'InvalidateEntityCache', $invalidateEntityCacheEventListener );

		$this->addListenersToCollection();

		\Hooks::run( 'SMW::Event::RegisterEventListeners', [ $this->eventListenerCollection ] );

		return $this->eventListenerCollection->getCollection();
	}

	private function addListenersToCollection() {

		$this->logger = ApplicationFactory::getInstance()->getMediaWikiLogger();

		$this->eventListenerCollection->registerCallback(
			'exporter.reset', function() {
				Exporter::getInstance()->clear();
			}
		);

		$this->eventListenerCollection->registerCallback(
			'query.comparator.reset', function() {
				QueryComparator::getInstance()->clear();
			}
		);

		return $this->eventListenerCollection;
	}

}

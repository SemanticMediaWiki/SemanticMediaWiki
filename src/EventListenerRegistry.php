<?php

namespace SMW;

use Onoi\EventDispatcher\EventListenerCollection;

use SMWExporter as Exporter;
use SMW\Query\QueryComparator;

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
		return $this->addListenersToCollection()->getCollection();
	}

	private function addListenersToCollection() {

		$this->eventListenerCollection->registerCallback(
			'factbox.cache.delete', function( $dispatchContext ) {

				$title = $dispatchContext->get( 'title' );
				$cache = ApplicationFactory::getInstance()->getCache();

				$cache->delete(
					ApplicationFactory::getInstance()->newCacheFactory()->getFactboxCacheKey( $title->getArticleID() )
				);
			}
		);

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

		$this->registerStateChangeEvents();

		return $this->eventListenerCollection;
	}

	private function registerStateChangeEvents() {

		$this->eventListenerCollection->registerCallback(
			'property.spec.change', function( $dispatchContext ) {

				$applicationFactory = ApplicationFactory::getInstance();
				$subject = $dispatchContext->get( 'subject' );

				$updateDispatcherJob = $applicationFactory->newJobFactory()->newUpdateDispatcherJob(
					$subject->getTitle()
				);

				$updateDispatcherJob->run();

				Exporter::getInstance()->resetCacheFor( $subject );
				$applicationFactory->getPropertySpecificationLookup()->resetCacheFor( $subject );

				$dispatchContext->set( 'propagationstop', true );
			}
		);

		$this->eventListenerCollection->registerCallback(
			'on.before.semanticdata.update.complete', function( $dispatchContext ) {

				$subject = $dispatchContext->get( 'subject' );
				$hash = $subject->getHash();

				$inMemoryPoolCache = InMemoryPoolCache::getInstance();
				$inMemoryPoolCache->getPoolCacheFor( 'store.redirectTarget.lookup' )->delete( $hash );

				$applicationFactory = ApplicationFactory::getInstance();
				$applicationFactory->getCachedPropertyValuesPrefetcher()->resetCacheFor( $subject );

				$dispatchContext->set( 'propagationstop', true );
			}
		);

		$this->eventListenerCollection->registerCallback(
			'on.after.semanticdata.update.complete', function( $dispatchContext ) {

				$subject = $dispatchContext->get( 'subject' );
				$pageUpdater = ApplicationFactory::getInstance()->newMwCollaboratorFactory()->newPageUpdater();

				if ( $GLOBALS['smwgAutoRefreshSubject'] && $pageUpdater->canUpdate() ) {
					$pageUpdater->addPage( $subject->getTitle() );
					$pageUpdater->doPurgeParserCache();
					$pageUpdater->doPurgeHtmlCache();
				}

				$dispatchContext->set( 'propagationstop', true );
			}
		);
	}

}

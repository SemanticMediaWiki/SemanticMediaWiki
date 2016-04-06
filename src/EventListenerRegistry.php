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
				$applicationFactory = ApplicationFactory::getInstance();

				$applicationFactory->getCache()->delete(
					$applicationFactory->newCacheFactory()->getFactboxCacheKey( $title->getArticleID() )
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

		/**
		 * Emitted during NewRevisionFromEditComplete, UpdateJob, ArticleDelete
		 */
		$this->eventListenerCollection->registerCallback(
			'cached.propertyvalues.prefetcher.reset', function( $dispatchContext ) {

				$subject = DIWikiPage::newFromTitle( $dispatchContext->get( 'title' ) );
				wfDebugLog( 'smw', 'Clear CachedPropertyValuesPrefetcher for ' . $subject->getHash() );

				$applicationFactory = ApplicationFactory::getInstance();

				$applicationFactory->getCachedPropertyValuesPrefetcher()->resetCacheFor(
					$subject
				);

				$dispatchContext->set( 'propagationstop', true );
			}
		);

		$this->registerStateChangeEvents();

		return $this->eventListenerCollection;
	}

	private function registerStateChangeEvents() {

		/**
		 * Emitted during PropertySpecificationChangeNotifier::notifyDispatcher
		 */
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

		/**
		 * Emitted during Store::updateData
		 */
		$this->eventListenerCollection->registerCallback(
			'on.before.semanticdata.update.complete', function( $dispatchContext ) {

				$subject = $dispatchContext->get( 'subject' );
				$hash = $subject->getHash();

				$applicationFactory = ApplicationFactory::getInstance();

				$poolCache = $applicationFactory->getInMemoryPoolCache()->getPoolCacheFor(
					'store.redirectTarget.lookup'
				);

				$poolCache->delete( $hash );

				$dispatchContext->set( 'propagationstop', true );
			}
		);

		/**
		 * Emitted during Store::updateData
		 */
		$this->eventListenerCollection->registerCallback(
			'on.after.semanticdata.update.complete', function( $dispatchContext ) {

				$applicationFactory = ApplicationFactory::getInstance();
				$subject = $dispatchContext->get( 'subject' );

				$pageUpdater = $applicationFactory->newMwCollaboratorFactory()->newPageUpdater();

				if ( $GLOBALS['smwgAutoRefreshSubject'] && $pageUpdater->canUpdate() ) {
					$pageUpdater->addPage( $subject->getTitle() );

					$deferredCallableUpdate = $applicationFactory->newDeferredCallableUpdate( function() use( $pageUpdater ) {
						$pageUpdater->doPurgeParserCache();
						$pageUpdater->doPurgeHtmlCache();
					} );

					$deferredCallableUpdate->pushToDeferredUpdateList();
				}

				$dispatchContext->set( 'propagationstop', true );
			}
		);
	}

}

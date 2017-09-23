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

		/**
		 * Emitted during UpdateJob, ArticlePurge
		 */
		$this->eventListenerCollection->registerCallback(
			'factbox.cache.delete', function( $dispatchContext ) {

				if ( $dispatchContext->has( 'subject' ) ) {
					$title = $dispatchContext->get( 'subject' )->getTitle();
				} else {
					$title = $dispatchContext->get( 'title' );
				}

				$applicationFactory = ApplicationFactory::getInstance();

				$applicationFactory->getCache()->delete(
					$applicationFactory->newCacheFactory()->getFactboxCacheKey( $title )
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
		 * Emitted during UpdateJob
		 */
		$this->eventListenerCollection->registerCallback(
			'cached.propertyvalues.prefetcher.reset', function( $dispatchContext ) {

				if ( $dispatchContext->has( 'title' ) ) {
					$subject = DIWikiPage::newFromTitle( $dispatchContext->get( 'title' ) );
				} else{
					$subject = $dispatchContext->get( 'subject' );
				}

				$applicationFactory = ApplicationFactory::getInstance();
				$applicationFactory->getMediaWikiLogger()->info( 'Event: cached.propertyvalues.prefetcher.reset :: ' . $subject->getHash() );

				$applicationFactory->singleton( 'CachedPropertyValuesPrefetcher' )->resetCacheBy(
					$subject
				);

				$dispatchContext->set( 'propagationstop', true );
			}
		);

		/**
		 * Emitted during NewRevisionFromEditComplete, ArticleDelete, TitleMoveComplete,
		 * PropertyTableIdReferenceDisposer, ArticlePurge
		 */
		$this->eventListenerCollection->registerCallback(
			'cached.prefetcher.reset', function( $dispatchContext ) {

				if ( $dispatchContext->has( 'title' ) ) {
					$subject = DIWikiPage::newFromTitle( $dispatchContext->get( 'title' ) );
				} else{
					$subject = $dispatchContext->get( 'subject' );
				}

				$context = $dispatchContext->has( 'context' ) ? $dispatchContext->get( 'context' ) : '';

				$applicationFactory = ApplicationFactory::getInstance();
				$applicationFactory->getMediaWikiLogger()->info( 'Event: cached.prefetcher.reset :: ' . $subject->getHash() );

				$applicationFactory->singleton( 'CachedPropertyValuesPrefetcher' )->resetCacheBy(
					$subject
				);

				$applicationFactory->singleton( 'CachedQueryResultPrefetcher' )->resetCacheBy(
					$subject,
					$context
				);

				if ( $dispatchContext->has( 'ask' ) ) {
					$applicationFactory->singleton( 'CachedQueryResultPrefetcher' )->resetCacheBy(
						$dispatchContext->get( 'ask' ),
						$context
					);
				}

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
			'property.specification.change', function( $dispatchContext ) {

				$applicationFactory = ApplicationFactory::getInstance();
				$subject = $dispatchContext->get( 'subject' );

				$updateDispatcherJob = $applicationFactory->newJobFactory()->newByType(
					'SMW\UpdateDispatcherJob',
					$subject->getTitle()
				);

				$updateDispatcherJob->run();

				Exporter::getInstance()->resetCacheBy( $subject );
				$applicationFactory->getPropertySpecificationLookup()->resetCacheBy( $subject );

				$dispatchContext->set( 'propagationstop', true );
			}
		);

		/**
		 * Emitted during ArticleDelete
		 */
		$this->eventListenerCollection->registerCallback(
			'cached.update.marker.delete', function( $dispatchContext ) {

				$hash = '';

				if ( $dispatchContext->has( 'subject' ) ) {
					$hash = $dispatchContext->get( 'subject' )->getHash();
				}

				ApplicationFactory::getInstance()->getCache()->delete(
					smwfCacheKey( ParserData::CACHE_NAMESPACE, $hash )
				);
			}
		);
	}

}

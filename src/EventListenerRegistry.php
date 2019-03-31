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

				$logContext = [
					'role' => 'developer',
					'event' => 'cached.prefetcher.reset',
					'origin' => $subject
				];

				$this->logger->info( '[Event] {event}: {origin}', $logContext );

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
		 * Emitted during ArticleDelete
		 */
		$this->eventListenerCollection->registerCallback(
			'cached.update.marker.delete', function( $dispatchContext ) {

				$cache = ApplicationFactory::getInstance()->getCache();

				if ( $dispatchContext->has( 'subject' ) ) {
					$cache->delete(
						smwfCacheKey(
							ParserData::CACHE_NAMESPACE,
							$dispatchContext->get( 'subject' )->getHash()
						)
					);
				}
			}
		);
	}

}

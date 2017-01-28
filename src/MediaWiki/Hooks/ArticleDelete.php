<?php

namespace SMW\MediaWiki\Hooks;

use SMW\ApplicationFactory;
use SMW\DIWikiPage;
use SMW\EventHandler;
use Psr\Log\LoggerInterface;
use Psr\Log\LoggerAwareInterface;

/**
 * @see https://www.mediawiki.org/wiki/Manual:Hooks/ArticleDelete
 *
 * @license GNU GPL v2+
 * @since 2.0
 *
 * @author mwjames
 */
class ArticleDelete implements LoggerAwareInterface {

	/**
	 * @var LoggerInterface
	 */
	private $logger;

	/**
	 * @see LoggerAwareInterface::setLogger
	 *
	 * @since 2.5
	 *
	 * @param LoggerInterface $logger
	 */
	public function setLogger( LoggerInterface $logger ) {
		$this->logger = $logger;
	}

	/**
	 * @since 2.0
	 *
	 * @param Wikipage $wikiPage
	 *
	 * @return true
	 */
	public function process( $wikiPage ) {

		$applicationFactory = ApplicationFactory::getInstance();
		$eventHandler = EventHandler::getInstance();

		$title = $wikiPage->getTitle();
		$store = $applicationFactory->getStore();

		$semanticDataSerializer = $applicationFactory->newSerializerFactory()->newSemanticDataSerializer();
		$jobFactory = $applicationFactory->newJobFactory();

		$deferredCallableUpdate = $applicationFactory->newDeferredCallableUpdate( function() use( $store, $title, $semanticDataSerializer, $jobFactory, $eventHandler ) {

			$subject = DIWikiPage::newFromTitle( $title );
			$this->log( 'DeferredCallableUpdate on delete for ' . $subject->getHash() );

			$parameters['semanticData'] = $semanticDataSerializer->serialize(
				$store->getSemanticData( $subject )
			);

			$jobFactory->newUpdateDispatcherJob( $title, $parameters )->insert();

			// Do we want this?
			/*
			$properties = $store->getInProperties( $subject );
			$jobList = array();

			foreach ( $properties as $property ) {
				$propertySubjects = $store->getPropertySubjects( $property, $subject );
				foreach ( $propertySubjects as $sub ) {
					$jobList[$sub->getHash()] = true;
				}
			}

			$jobFactory->newUpdateDispatcherJob( $title, array( 'job-list' => $jobList ) )->insert();
			*/
			$store->deleteSubject( $title );

			$dispatchContext = $eventHandler->newDispatchContext();
			$dispatchContext->set( 'title', $title );
			$dispatchContext->set( 'context', 'ArticleDelete' );

			$eventHandler->getEventDispatcher()->dispatch(
				'cached.prefetcher.reset',
				$dispatchContext
			);
		} );

		$deferredCallableUpdate->setOrigin( __METHOD__ );
		$deferredCallableUpdate->pushUpdate();

		return true;
	}

	private function log( $message, $context = array() ) {

		if ( $this->logger === null ) {
			return;
		}

		$this->logger->info( $message, $context );
	}

}

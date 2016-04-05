<?php

namespace SMW\MediaWiki\Hooks;

use SMW\ApplicationFactory;
use SMW\DIWikiPage;
use SMW\EventHandler;

/**
 * @see https://www.mediawiki.org/wiki/Manual:Hooks/ArticleDelete
 *
 * @ingroup FunctionHook
 *
 * @license GNU GPL v2+
 * @since 2.0
 *
 * @author mwjames
 */
class ArticleDelete {

	/**
	 * @var Wikipage
	 */
	private $wikiPage = null;

	/**
	 * @since  2.0
	 *
	 * @param Wikipage $wikiPage
	 */
	public function __construct( &$wikiPage, &$user, &$reason, &$error ) {
		$this->wikiPage = $wikiPage;
	}

	/**
	 * @since 2.0
	 *
	 * @return true
	 */
	public function process() {

		$applicationFactory = ApplicationFactory::getInstance();
		$eventHandler = EventHandler::getInstance();

		$title = $this->wikiPage->getTitle();
		$store = $applicationFactory->getStore();

		$semanticDataSerializer = $applicationFactory->newSerializerFactory()->newSemanticDataSerializer();
		$jobFactory = $applicationFactory->newJobFactory();

		$deferredCallableUpdate = $applicationFactory->newDeferredCallableUpdate( function() use( $store, $title, $semanticDataSerializer, $jobFactory, $eventHandler ) {

			$subject = DIWikiPage::newFromTitle( $title );
			wfDebugLog( 'smw', 'DeferredCallableUpdate on delete for ' . $subject->getHash() );

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

			$eventHandler->getEventDispatcher()->dispatch(
				'cached.propertyvalues.prefetcher.reset',
				$dispatchContext
			);
		} );

		$deferredCallableUpdate->pushToDeferredUpdateList();

		return true;
	}

}

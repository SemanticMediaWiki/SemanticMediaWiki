<?php

namespace SMW\MediaWiki\Hooks;

use SMW\ApplicationFactory;
use SMW\DIWikiPage;
use SMW\EventHandler;
use SMW\MediaWiki\Jobs\UpdateDispatcherJob;
use SMW\SemanticData;
use SMW\Store;
use Title;
use Wikipage;

/**
 * @see https://www.mediawiki.org/wiki/Manual:Hooks/ArticleDelete
 *
 * @license GNU GPL v2+
 * @since 2.0
 *
 * @author mwjames
 */
class ArticleDelete extends HookHandler {

	/**
	 * @var
	 */
	private $store;

	/**
	 * @since 3.0
	 *
	 * @param Store $store
	 */
	public function __construct( Store $store ) {
		$this->store = $store;
	}

	/**
	 * @since 2.0
	 *
	 * @param Wikipage $wikiPage
	 *
	 * @return true
	 */
	public function process( Wikipage $wikiPage ) {

		$deferredCallableUpdate = ApplicationFactory::getInstance()->newDeferredCallableUpdate( function() use( $wikiPage ) {
			$this->doDelete( $wikiPage->getTitle() );
		} );

		$deferredCallableUpdate->setOrigin( __METHOD__ );
		$deferredCallableUpdate->pushUpdate();

		return true;
	}

	/**
	 * @since 3.0
	 *
	 * @param Title $title
	 */
	public function doDelete( Title $title ) {

		$applicationFactory = ApplicationFactory::getInstance();
		$subject = DIWikiPage::newFromTitle( $title );

		$semanticDataSerializer = $applicationFactory->newSerializerFactory()->newSemanticDataSerializer();
		$jobFactory = $applicationFactory->newJobFactory();

		// Instead of Store::getSemanticData, construct the SemanticData by
		// attaching only the incoming properties indicating which entities
		// carry an actual reference to this subject
		$semanticData = new SemanticData(
			$subject
		);

		$properties = $this->store->getInProperties( $subject );

		foreach ( $properties as $property ) {
			// Avoid doing $propertySubjects = $store->getPropertySubjects( $property, $subject );
			// as it may produce a too large pool of entities and ultimately
			// block the delete transaction
			// Use the subject as dataItem with the UpdateDispatcherJob because
			// Store::getAllPropertySubjects is only scanning the property
			$semanticData->addPropertyObjectValue( $property, $subject );
		}

		$parameters['semanticData'] = $semanticDataSerializer->serialize(
			$semanticData
		);

		$parameters['origin'] = 'ArticleDelete';

		// Fetch the ID before the delete process marks it as outdated to help
		// run a dispatch process on secondary tables
		$parameters['_id'] = $this->store->getObjectIds()->getId(
			$subject
		);

		// Restricted to the available SemanticData
		$parameters[UpdateDispatcherJob::RESTRICTED_DISPATCH_POOL] = true;

		$updateDispatcherJob = $jobFactory->newUpdateDispatcherJob( $title, $parameters );
		$updateDispatcherJob->insert();

		$parserCachePurgeJob = $jobFactory->newParserCachePurgeJob(
			$title,
			[
				'idlist' => [
					$parameters['_id']
				],
				'origin' => 'ArticleDelete',

				// Insert will only be done for when the links store is active
				// otherwise the job wouldn't have any work to do
				'is.enabled' => $this->getOption( 'smwgEnabledQueryDependencyLinksStore' )
			]
		);

		$parserCachePurgeJob->insert();

		$this->store->deleteSubject( $title );

		$eventHandler = EventHandler::getInstance();
		$dispatchContext = $eventHandler->newDispatchContext();

		$dispatchContext->set( 'title', $title );
		$dispatchContext->set( 'subject', $subject );
		$dispatchContext->set( 'context', 'ArticleDelete' );

		$eventHandler->getEventDispatcher()->dispatch(
			'cached.prefetcher.reset',
			$dispatchContext
		);

		// Removes any related update marker
		$eventHandler->getEventDispatcher()->dispatch(
			'cached.update.marker.delete',
			$dispatchContext
		);
	}

}

<?php

namespace SMW\MediaWiki\Hooks;

use MediaWiki\Page\Hook\ArticleDeleteHook;
use MediaWiki\Status\Status;
use MediaWiki\Title\Title;
use MediaWiki\User\User;
use SMW\DataItems\WikiPage as DIWikiPage;
use SMW\DataModel\SemanticData;
use SMW\EventDispatcher\EventDispatcher;
use SMW\MediaWiki\JobFactory;
use SMW\MediaWiki\Jobs\UpdateDispatcherJob;
use SMW\Services\ServicesFactory as ApplicationFactory;
use WikiPage;

/**
 * @see https://www.mediawiki.org/wiki/Manual:Hooks/ArticleDelete
 *
 * @license GPL-2.0-or-later
 * @since 2.0
 *
 * @author mwjames
 */
class ArticleDelete implements ArticleDeleteHook {

	private string $origin = 'ArticleDelete';

	/**
	 * @since 7.0.0
	 */
	public function __construct(
		private readonly JobFactory $jobFactory,
		private readonly EventDispatcher $eventDispatcher,
	) {
	}

	/**
	 * @since 3.2
	 *
	 * @param string $origin
	 */
	public function setOrigin( string $origin ): void {
		$this->origin = $origin;
	}

	/**
	 * @since 7.0.0
	 */
	public function onArticleDelete( WikiPage $wikiPage, User $user, &$reason, &$error, Status &$status, $suppress ) {
		$this->scheduleDeleteFor( $wikiPage->getTitle() );

		return true;
	}

	/**
	 * Schedule the SMW-side cleanup for a deleted (or about-to-be-deleted) page.
	 *
	 * @since 7.0.0
	 */
	public function scheduleDeleteFor( Title $title ): void {
		$deferredCallableUpdate = ApplicationFactory::getInstance()->newDeferredCallableUpdate( function () use( $title ): void {
			$this->doDelete( $title );
		} );

		$deferredCallableUpdate->setOrigin( __METHOD__ );
		$deferredCallableUpdate->pushUpdate();
	}

	/**
	 * @since 3.0
	 *
	 * @param Title $title
	 */
	public function doDelete( Title $title ): void {
		// Resolve Store lazily inside the deferred update rather than capturing
		// it in the constructor. The handler is built once when extension.json
		// HookHandlers is wired (or when MwHooksHandler eager-builds in tests);
		// JSON-script tests that change property table configurations between
		// cases would otherwise see a stale Store with outdated table
		// definitions, producing queries like `SELECT s_id ... WHERE o_id=...`
		// against tables that no longer have an `o_id` column.
		$applicationFactory = ApplicationFactory::getInstance();
		$store = $applicationFactory->getStore();
		$subject = DIWikiPage::newFromTitle( $title );

		$semanticDataSerializer = $applicationFactory->newSerializerFactory()->newSemanticDataSerializer();

		// Instead of Store::getSemanticData, construct the SemanticData by
		// attaching only the incoming properties indicating which entities
		// carry an actual reference to this subject
		$semanticData = new SemanticData(
			$subject
		);

		$properties = $store->getInProperties( $subject );

		foreach ( $properties as $property ) {
			// Avoid doing $propertySubjects = $store->getPropertySubjects( $property, $subject );
			// as it may produce a too large pool of entities and ultimately
			// block the delete transaction
			// Use the subject as dataItem with the UpdateDispatcherJob because
			// Store::getAllPropertySubjects is only scanning the property
			$semanticData->addPropertyObjectValue( $property, $subject );
		}

		$parameters = [];
		$parameters['semanticData'] = $semanticDataSerializer->serialize(
			$semanticData
		);

		$parameters['origin'] = $this->origin;

		// Fetch the ID before the delete process marks it as outdated to help
		// run a dispatch process on secondary tables
		$parameters['_id'] = $store->getObjectIds()->getId(
			$subject
		);

		// Restricted to the available SemanticData
		$parameters[UpdateDispatcherJob::RESTRICTED_DISPATCH_POOL] = true;

		$updateDispatcherJob = $this->jobFactory->newUpdateDispatcherJob( $title, $parameters );
		$updateDispatcherJob->insert();

		$store->deleteSubject( $title );

		$context = [
			'context' => $this->origin,
			'title' => $title,
			'subject' => $subject
		];

		$this->eventDispatcher->dispatch( 'InvalidateResultCache', $context );
		$this->eventDispatcher->dispatch( 'InvalidateEntityCache', $context );
	}

}

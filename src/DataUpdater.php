<?php

namespace SMW;

use Title;
use User;
use WikiPage;

/**
 * This function takes care of storing the collected semantic data and
 * clearing out any outdated entries for the processed page. It assumes
 * that parsing has happened and that all relevant information are
 * contained and provided for.
 *
 * Optionally, this function also takes care of triggering indirect updates
 * that might be needed for an overall database consistency. If the saved page
 * describes a property or data type, the method checks whether the property
 * type, the data type, the allowed values, or the conversion factors have
 * changed.
 *
 * @license GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class DataUpdater {

	/**
	 * @var Store
	 */
	private $store;

	/**
	 * @var SemanticData
	 */
	private $semanticData;

	/**
	 * @var TransactionalCallableUpdate
	 */
	private $transactionalCallableUpdate;

	/**
	 * @var boolean|null
	 */
	private $canCreateUpdateJob = null;

	/**
	 * @var boolean
	 */
	private $processSemantics = false;

	/**
	 * @var boolean
	 */
	private $isCommandLineMode = false;

	/**
	 * @var boolean|string
	 */
	private $isChangeProp = false;

	/**
	 * @var boolean
	 */
	private $isDeferrableUpdate = false;

	/**
	 * @var string
	 */
	private $origin = '';

	/**
	 * @since  1.9
	 *
	 * @param Store $store
	 * @param SemanticData $semanticData
	 */
	public function __construct( Store $store, SemanticData $semanticData ) {
		$this->store = $store;
		$this->semanticData = $semanticData;
		$this->transactionalCallableUpdate = ApplicationFactory::getInstance()->newDeferredTransactionalCallableUpdate();
	}

	/**
	 * @see https://www.mediawiki.org/wiki/Manual:$wgCommandLineMode
	 * Indicates whether MW is running in command-line mode.
	 *
	 * @since 3.0
	 *
	 * @param boolean $isCommandLineMode
	 */
	public function isCommandLineMode( $isCommandLineMode ) {
		$this->isCommandLineMode = $isCommandLineMode;
	}

	/**
	 * @since 3.0
	 *
	 * @param boolean $isChangeProp
	 */
	public function isChangeProp( $isChangeProp ) {
		$this->isChangeProp = (bool)$isChangeProp;
	}

	/**
	 * @since 3.0
	 *
	 * @param boolean $isChangeProp
	 */
	public function isDeferrableUpdate( $isDeferrableUpdate ) {
		$this->isDeferrableUpdate = (bool)$isDeferrableUpdate;
	}

	/**
	 * @since 2.5
	 *
	 * @param string $origin
	 */
	public function setOrigin( $origin ) {
		$this->origin = $origin;
	}

	/**
	 * @since 1.9
	 *
	 * @return DIWikiPage
	 */
	public function getSubject() {
		return $this->semanticData->getSubject();
	}

	/**
	 * @since 1.9
	 *
	 * @param boolean $canCreateUpdateJob
	 */
	public function canCreateUpdateJob( $canCreateUpdateJob ) {
		$this->canCreateUpdateJob = (bool)$canCreateUpdateJob;
	}

	/**
	 * @since 1.9
	 *
	 * @return boolean
	 */
	public function doUpdate() {

		if ( !$this->canPerformUpdate() ) {
			return false;
		}

		DeferredCallableUpdate::releasePendingUpdates();

		if ( $this->isDeferrableUpdate === false || $this->isCommandLineMode ) {
			return $this->performUpdate();
		}

		$this->transactionalCallableUpdate->setCallback( function() {
			$this->performUpdate();
		} );

		$this->transactionalCallableUpdate->setOrigin(
			[
				__METHOD__,
				$this->origin,
				$this->getSubject()->getHash()
			]
		);

		$this->transactionalCallableUpdate->isDeferrableUpdate(
			$this->isDeferrableUpdate
		);

		$this->transactionalCallableUpdate->commitWithTransactionTicket();
		$this->transactionalCallableUpdate->pushUpdate();

		return true;
	}

	private function canPerformUpdate() {

		$title = $this->getSubject()->getTitle();

		// Protect against null and namespace -1 see Bug 50153
		if ( $title === null || $title->isSpecialPage() ) {
			return false;
		}

		return true;
	}

	/**
	 * @note Make sure to have a valid revision (null means delete etc.) and
	 * check if semantic data should be processed and displayed for a page in
	 * the given namespace
	 */
	private function performUpdate() {

		$applicationFactory = ApplicationFactory::getInstance();

		if ( $this->canCreateUpdateJob === null ) {
			$this->canCreateUpdateJob( $applicationFactory->getSettings()->get( 'smwgEnableUpdateJobs' ) );
		}

		$title = $this->getSubject()->getTitle();
		$wikiPage = $applicationFactory->newPageCreator()->createPage( $title );

		$revision = $wikiPage->getRevision();
		$user = $revision !== null ? User::newFromId( $revision->getUser() ) : null;

		$this->addAnnotations( $title, $wikiPage, $revision, $user );

		// In case of a restricted update, only the protection update is required
		// hence the process bails-out early to avoid unnecessary DB connections
		// or updates
		if ( $this->checkUpdateEditProtection( $wikiPage, $user ) === true ) {
			return true;
		}

		$this->checkChangePropagation();
		$this->updateData();

		if ( $this->semanticData->getOption( Enum::PURGE_ASSOC_PARSERCACHE ) === true ) {
			$jobQueue = $applicationFactory->getJobQueue();
			$jobQueue->runFromQueue( [ 'SMW\ParserCachePurgeJob' => 2 ] );
		}

		return true;
	}

	private function addAnnotations( Title $title, WikiPage $wikiPage, $revision, $user ) {

		$applicationFactory = ApplicationFactory::getInstance();

		if ( $revision !== null ) {
			$this->processSemantics = $applicationFactory->getNamespaceExaminer()->isSemanticEnabled( $title->getNamespace() );
		}

		if ( !$this->processSemantics ) {
			return $this->semanticData = new SemanticData( $this->getSubject() );
		}

		$pageInfoProvider = $applicationFactory->newMwCollaboratorFactory()->newPageInfoProvider(
			$wikiPage,
			$revision,
			$user
		);

		$this->semanticData->setExtensionData( 'revision_id', $revision->getId() );

		$propertyAnnotatorFactory = $applicationFactory->singleton( 'PropertyAnnotatorFactory' );

		$propertyAnnotator = $propertyAnnotatorFactory->newNullPropertyAnnotator(
			$this->semanticData
		);

		$propertyAnnotator = $propertyAnnotatorFactory->newPredefinedPropertyAnnotator(
			$propertyAnnotator,
			$pageInfoProvider
		);

		// Standard text hooks are not run through a JSON content object therefore
		// we attach possible annotations at this point
		if ( $title->getNamespace() === SMW_NS_SCHEMA ) {

			$schemaFactory = $applicationFactory->singleton( 'SchemaFactory' );

			try {
				$schema = $schemaFactory->newSchema(
					$title->getDBKey(),
					$pageInfoProvider->getNativeData()
				);
			} catch ( \Exception $e ) {
				$schema = null;
			}

			$propertyAnnotator = $propertyAnnotatorFactory->newSchemaPropertyAnnotator(
				$propertyAnnotator,
				$schema
			);
		}

		$propertyAnnotator->addAnnotation();

		\Hooks::run(
			'SMW::DataUpdater::ContentProcessor',
			[
				$this->semanticData,
				$wikiPage->getContent()
			]
		);
	}

	private function checkUpdateEditProtection( $wikiPage, $user ) {

		$applicationFactory = ApplicationFactory::getInstance();

		$editProtectionUpdater = $applicationFactory->create( 'EditProtectionUpdater',
			$wikiPage,
			$user
		);

		$editProtectionUpdater->doUpdateFrom( $this->semanticData );

		return $editProtectionUpdater->isRestrictedUpdate();
	}

	/**
	 * @note Comparison must happen *before* the storage update;
	 * even finding uses of a property fails after its type changed.
	 */
	private function checkChangePropagation() {

		// canCreateUpdateJob: if it is not enabled there's not much to do here
		// isChangeProp: means the update is part of the ChangePropagationDispatchJob
		// therefore skip
		if ( !$this->canCreateUpdateJob || $this->isChangeProp  ) {
			return;
		}

		$namespace = $this->semanticData->getSubject()->getNamespace();

		if ( $namespace !== SMW_NS_PROPERTY && $namespace !== NS_CATEGORY ) {
			return;
		}

		$applicationFactory = ApplicationFactory::getInstance();

		$propertyChangePropagationNotifier = new PropertyChangePropagationNotifier(
			$this->store,
			$applicationFactory->newSerializerFactory()
		);

		$propertyChangePropagationNotifier->setPropertyList(
			$applicationFactory->getSettings()->get( 'smwgChangePropagationWatchlist' )
		);

		$propertyChangePropagationNotifier->isCommandLineMode(
			$this->isCommandLineMode
		);

		$propertyChangePropagationNotifier->checkAndNotify(
			$this->semanticData
		);
	}

	private function updateData() {

		$this->store->setOption(
			Store::OPT_CREATE_UPDATE_JOB,
			$this->canCreateUpdateJob
		);

		$semanticData = $this->checkOnRequiredRedirectUpdate(
			$this->semanticData
		);

		$subject = $semanticData->getSubject();

		if ( $this->processSemantics ) {
			$this->store->updateData( $semanticData );
		} elseif ( $this->store->getObjectIds()->exists( $subject ) ) {
			// Only clear the data where it is know that "exists" is true otherwise
			// an empty entity is created and later being removed by the
			// "PropertyTableOutdatedReferenceDisposer" since it is an entity that is
			// empty == has no reference
			$this->store->clearData( $subject );
		}

		return true;
	}

	private function checkOnRequiredRedirectUpdate( SemanticData $semanticData ) {

		// Check only during online-mode so that when a user operates Special:MovePage
		// or #redirect the same process is applied
		if ( !$this->canCreateUpdateJob ) {
			return $semanticData;
		}

		$redirects = $semanticData->getPropertyValues(
			new DIProperty( '_REDI' )
		);

		if ( $redirects !== [] && !$semanticData->getSubject()->equals( end( $redirects ) ) ) {
			return $this->doUpdateUnknownRedirectTarget( $semanticData, end( $redirects ) );
		}

		return $semanticData;
	}

	private function doUpdateUnknownRedirectTarget( SemanticData $semanticData, DIWikiPage $target ) {

		// Only keep the reference to safeguard that even in case of a text keeping
		// its annotations there are removed from the Store. A redirect is not
		// expected to contain any other annotation other than that of the redirect
		// target
		$subject = $semanticData->getSubject();
		$semanticData = new SemanticData( $subject );

		$semanticData->addPropertyObjectValue(
			new DIProperty( '_REDI' ),
			$target
		);

		// Force a manual changeTitle before the general update otherwise
		// #redirect can cause an inconsistent data container as observed in #895
		$source = $subject->getTitle();
		$target = $target->getTitle();

		$this->store->changeTitle(
			$source,
			$target,
			$source->getArticleID(),
			$target->getArticleID()
		);

		$dispatchContext = EventHandler::getInstance()->newDispatchContext();
		$dispatchContext->set( 'title', $subject->getTitle() );

		EventHandler::getInstance()->getEventDispatcher()->dispatch(
			'factbox.cache.delete',
			$dispatchContext
		);

		return $semanticData;
	}

}

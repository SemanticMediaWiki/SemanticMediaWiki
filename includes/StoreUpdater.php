<?php

namespace SMW;

use WikiPage;
use Title;
use User;

/**
 * Initiates an update of the Store
 *
 * @license GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class StoreUpdater {

	/**
	 * @var Store
	 */
	private $store;

	/**
	 * @var SemanticData
	 */
	private $semanticData;

	/**
	 * @var boolean|null
	 */
	private $updateJobsEnabledState = null;

	/**
	 * @var boolean|null
	 */
	private $processSemantics = null;

	/**
	 * @var ApplicationFactory
	 */
	private $applicationFactory = null;

	/**
	 * @since  1.9
	 *
	 * @param Store $store
	 * @param SemanticData $semanticData
	 */
	public function __construct( Store $store, SemanticData $semanticData ) {
		$this->store = $store;
		$this->semanticData = $semanticData;
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
	 * @param boolean $status
	 */
	public function setUpdateJobsEnabledState( $status ) {
		$this->updateJobsEnabledState = (bool)$status;
		return $this;
	}

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
	 * changed. If so, it triggers UpdateDispatcherJob for the relevant articles,
	 * which then asynchronously undergoes an update.
	 *
	 * @since 1.9
	 *
	 * @return boolean
	 */
	public function doUpdate() {
		return $this->canPerformUpdate() ? $this->performUpdate() : false;
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

		$this->applicationFactory = ApplicationFactory::getInstance();

		if ( $this->updateJobsEnabledState === null ) {
			$this->setUpdateJobsEnabledState( $this->applicationFactory->getSettings()->get( 'smwgEnableUpdateJobs' ) );
		}

		$title = $this->getSubject()->getTitle();
		$wikiPage = $this->applicationFactory->newPageCreator()->createPage( $title );
		$revision = $wikiPage->getRevision();

		$this->updateSemanticData( $title, $wikiPage, $revision );
		$this->doRealUpdate( $this->inspectPropertySpecification() );

		return true;
	}

	private function updateSemanticData( Title $title, WikiPage $wikiPage, $revision ) {

		$this->processSemantics = $revision !== null && $this->isSemanticEnabledNamespace( $title );

		if ( !$this->processSemantics ) {
			return $this->semanticData = new SemanticData( $this->getSubject() );
		}

		$pageInfoProvider = $this->applicationFactory->newMwCollaboratorFactory()->newPageInfoProvider(
			$wikiPage,
			$revision,
			User::newFromId( $revision->getUser() )
		);

		$propertyAnnotator = $this->applicationFactory->newPropertyAnnotatorFactory()->newPredefinedPropertyAnnotator(
			$this->semanticData,
			$pageInfoProvider
		);

		$propertyAnnotator->addAnnotation();
	}

	/**
	 * @note Comparison must happen *before* the storage update;
	 * even finding uses of a property fails after its type changed.
	 */
	private function inspectPropertySpecification() {

		if ( !$this->updateJobsEnabledState ) {
			return;
		}

		$propertySpecificationChangeNotifier = new PropertySpecificationChangeNotifier(
			$this->store,
			$this->semanticData
		);

		$propertySpecificationChangeNotifier->setPropertiesToCompare(
			$this->applicationFactory->getSettings()->get( 'smwgDeclarationProperties' )
		);

		$propertySpecificationChangeNotifier->compareForListedSpecification();
	}

	private function doRealUpdate() {

		$this->store->setUpdateJobsEnabledState( $this->updateJobsEnabledState );

		$semanticData = $this->checkForRequiredRedirectUpdate(
			$this->semanticData
		);

		if ( $this->processSemantics ) {
			$this->store->updateData( $semanticData );
		} else {
			$this->store->clearData( $semanticData->getSubject() );
		}

		return true;
	}

	private function isSemanticEnabledNamespace( Title $title ) {
		return $this->applicationFactory->getNamespaceExaminer()->isSemanticEnabled( $title->getNamespace() );
	}

	private function checkForRequiredRedirectUpdate( SemanticData $semanticData ) {

		// Check only during online-mode so that when a user operates Special:MovePage
		// or #redirect the same process is applied
		if ( !$this->updateJobsEnabledState ) {
			return $semanticData;
		}

		$redirects = $semanticData->getPropertyValues(
			new DIProperty( '_REDI' )
		);

		if ( $redirects !== array() && !$semanticData->getSubject()->equals( end( $redirects ) ) ) {
			return $this->handleYetUnknownRedirectTarget( $semanticData, end( $redirects ) );
		}

		return $semanticData;
	}

	private function handleYetUnknownRedirectTarget( SemanticData $semanticData, DIWikiPage $target ) {

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
		$this->store->changeTitle(
			$subject->getTitle(),
			$target->getTitle(),
			$subject->getTitle()->getArticleID(),
			$target->getTitle()->getArticleID()
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

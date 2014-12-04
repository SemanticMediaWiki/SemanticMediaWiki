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
		Profiler::In();

		$this->applicationFactory = ApplicationFactory::getInstance();

		if ( $this->updateJobsEnabledState === null ) {
			$this->setUpdateJobsEnabledState( $this->applicationFactory->getSettings()->get( 'smwgEnableUpdateJobs' ) );
		}

		$title = $this->getSubject()->getTitle();
		$wikiPage = $this->applicationFactory->newPageCreator()->createPage( $title );
		$revision = $wikiPage->getRevision();

		$this->updateSemanticData( $title, $wikiPage, $revision );

		Profiler::Out();
		return $this->doRealUpdate( $this->inspectPropertyType() );
	}

	private function updateSemanticData( Title $title, WikiPage $wikiPage, $revision ) {

		$this->processSemantics = $revision !== null && $this->isSemanticEnabledNamespace( $title );

		if ( !$this->processSemantics ) {
			return $this->semanticData = new SemanticData( $this->getSubject() );
		}

		$pageInfoProvider = $this->applicationFactory
			->newMwCollaboratorFactory()
			->newPageInfoProvider( $wikiPage, $revision, User::newFromId( $revision->getUser() ) );

		$propertyAnnotator = $this->applicationFactory
			->newPropertyAnnotatorFactory()
			->newPredefinedPropertyAnnotator( $this->semanticData, $pageInfoProvider );

		$propertyAnnotator->addAnnotation();
	}

	/**
	 * @note Comparison must happen *before* the storage update;
	 * even finding uses of a property fails after its type changed.
	 */
	private function inspectPropertyType() {

		if ( $this->updateJobsEnabledState ) {
			$propertyTypeDiffFinder = new PropertyTypeDiffFinder( $this->store, $this->semanticData );
			$propertyTypeDiffFinder->findDiff();
		}
	}

	private function doRealUpdate() {

		Profiler::In();

		$this->store->setUpdateJobsEnabledState( $this->updateJobsEnabledState );

		if ( $this->processSemantics ) {
			$this->store->updateData( $this->semanticData );
		} else {
			$this->store->clearData( $this->semanticData->getSubject() );
		}

		Profiler::Out();
		return true;
	}

	private function isSemanticEnabledNamespace( $title ) {
		return $this->applicationFactory->getNamespaceExaminer()->isSemanticEnabled( $title->getNamespace() );
	}

}

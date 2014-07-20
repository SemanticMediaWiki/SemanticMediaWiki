<?php

namespace SMW;

use WikiPage;
use Title;
use User;

/**
 * Initiates an update of the Store
 *
 * @ingroup SMW
 *
 * @license GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class StoreUpdater {

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
	 * @since  1.9
	 *
	 * @param SemanticData $semanticData
	 */
	public function __construct( SemanticData $semanticData ) {
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

		$this->application = Application::getInstance();

		if ( $this->updateJobsEnabledState === null ) {
			$this->setUpdateJobsEnabledState( $this->application->getSettings()->get( 'smwgEnableUpdateJobs' ) );
		}

		$title = $this->getSubject()->getTitle();
		$wikiPage = $this->application->newPageCreator()->createPage( $title );
		$revision = $wikiPage->getRevision();

		$this->updateSemanticData( $title, $wikiPage, $revision );

		Profiler::Out();
		return $this->doRealUpdate( $this->inspectPropertyType() );
	}

	private function updateSemanticData( Title $title, WikiPage $wikiPage, $revision ) {

		$this->processSemantics = $revision !== null && $this->isEnabledNamespace( $title );

		if ( !$this->processSemantics ) {
			return $this->semanticData = new SemanticData( $this->getSubject() );
		}

		$pageInfoProvider = $this->application
			->newPropertyAnnotatorFactory()
			->newPageInfoProvider( $wikiPage, $revision, User::newFromId( $revision->getUser() ) );

		$propertyAnnotator = $this->application
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
			$propertyTypeDiffFinder = new PropertyTypeDiffFinder( $this->application->getStore(), $this->semanticData );
			$propertyTypeDiffFinder->findDiff();
		}
	}

	private function doRealUpdate() {

		Profiler::In();

		if ( $this->processSemantics ) {
			$this->application->getStore()->updateData( $this->semanticData );
		} else {
			$this->application->getStore()->clearData( $this->semanticData->getSubject() );
		}

		Profiler::Out();
		return true;
	}

	private function isEnabledNamespace( $title ) {
		return NamespaceExaminer::newFromArray( $this->application->getSettings()->get( 'smwgNamespacesWithSemanticLinks' ) )->isSemanticEnabled( $title->getNamespace() );
	}

}

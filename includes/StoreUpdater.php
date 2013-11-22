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
 * @licence GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class StoreUpdater implements ContextAware {

	/** @var SemanticData */
	protected $semanticData;

	/** @var ContextResource */
	protected $context;

	/** @var $updateJobs */
	protected $updateJobs = null;

	public function __construct( SemanticData $semanticData, ContextResource $context ) {
		$this->semanticData = $semanticData;
		$this->context      = $context;

		$this->setUpdateJobs( $this->context->getSettings()->get( 'smwgEnableUpdateJobs' ) );
	}

	/**
	 * @see ContextAware::withContext
	 *
	 * @since 1.9
	 *
	 * @return ContextResource
	 */
	public function withContext() {
		return $this->context;
	}

	/**
	 * Returns the subject
	 *
	 * @since 1.9
	 *
	 * @return DIWikiPage
	 */
	public function getSubject() {
		return $this->semanticData->getSubject();
	}

	/**
	 * Sets the update status
	 *
	 * @since 1.9
	 */
	public function setUpdateJobs( $status ) {
		$this->updateJobs = (bool)$status;
		return $this;
	}

	/**
	 * Updates the store with invoked semantic data
	 *
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
	public function runUpdater() {

		$title = $this->getSubject()->getTitle();

		// Protect against null and namespace -1 see Bug 50153
		if ( $title === null || $title->isSpecialPage() ) {
			return false;
		}

		return $this->performUpdate( WikiPage::factory( $title ) );
	}

	/**
	 * @note Make sure to have a valid revision (null means delete etc.) and
	 * check if semantic data should be processed and displayed for a page in
	 * the given namespace
	 *
	 * @since 1.9
	 *
	 * @param WikiPage $wikiPage
	 *
	 * @return boolean
	 */
	protected function performUpdate( WikiPage $wikiPage ) {

		Profiler::In();

		$revision = $wikiPage->getRevision();

		$processSemantics = $revision !== null && $this->isValid( $wikiPage );

		if ( $processSemantics ) {

			$user = User::newFromId( $revision->getUser() );

			$propertyAnnotator = $this->withContext()->getDependencyBuilder()->newObject( 'PredefinedPropertyAnnotator', array(
				'SemanticData' => $this->semanticData,
				'WikiPage' => $wikiPage,
				'Revision' => $revision,
				'User'     => $user
			) );

			$propertyAnnotator->addAnnotation();

		} else {
			// data found, but do all operations as if it was empty
			$this->semanticData = new SemanticData( $this->getSubject() );
		}

		Profiler::Out();
		return $this->updateStore( $this->inspectPropertyType( $processSemantics ) );
	}

	/**
	 * @note Comparison must happen *before* the storage update;
	 * even finding uses of a property fails after its type changed.
	 *
	 * @since 1.9
	 *
	 * @return boolean
	 */
	protected function inspectPropertyType( $processSemantics ) {

		if ( $this->updateJobs ) {

			$propertyComparator = $this->withContext()->getDependencyBuilder()->newObject( 'PropertyTypeComparator', array(
				'SemanticData' => $this->semanticData
			) );

			$propertyComparator->runComparator();
		}

		return $processSemantics;
	}

	/**
	 * @since 1.9
	 *
	 * @return boolean
	 */
	protected function updateStore( $processSemantics ) {

		Profiler::In();

		// Actually store semantic data, or at least clear it if needed
		if ( $processSemantics ) {
			$this->withContext()->getStore()->updateData( $this->semanticData );
		} else {
			$this->withContext()->getStore()->clearData( $this->semanticData->getSubject() );
		}

		Profiler::Out();
		return true;
	}

	/**
	 * Returns whether the current WikiPage is valid
	 *
	 * @since 1.9
	 *
	 * @param WikiPage $wikiPage
	 *
	 * @return boolean
	 */
	protected function isValid( WikiPage $wikiPage ) {
		return $this->withContext()->getDependencyBuilder()->newObject( 'NamespaceExaminer' )->isSemanticEnabled( $wikiPage->getTitle()->getNamespace() );
	}

}

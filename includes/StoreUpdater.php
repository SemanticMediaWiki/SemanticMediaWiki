<?php

namespace SMW;

use WikiPage;
use Title;
use User;

/**
 * Initiates an update of the Store
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

		$this->setUpdateStatus( $this->context->getSettings()->get( 'smwgEnableUpdateJobs' ) );
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
	public function setUpdateStatus( $status ) {
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
	public function doUpdate() {
		Profiler::In( __METHOD__, true );

		$title = $this->getSubject()->getTitle();

		if ( $title === null ) {
			return false;
		}

		// Protect against namespace -1 see Bug 50153
		if ( $title->isSpecialPage() ) {
			return false;
		}

		$wikiPage = WikiPage::factory( $title );
		$revision = $wikiPage->getRevision();

		// Make sure to have a valid revision (null means delete etc.)
		// Check if semantic data should be processed and displayed for a page in
		// the given namespace
		$processSemantics = $revision !== null && $this->isValid( $title );

		if ( $processSemantics ) {

			$user = User::newFromId( $revision->getUser() );

			$propertyAnnotator = $this->withContext()->getDependencyBuilder()->newObject( 'BasePropertyAnnotator', array(
				'SemanticData' => $this->semanticData
			) );

			$propertyAnnotator->addSpecialProperties( $wikiPage, $revision, $user );

		} else {
			// data found, but do all operations as if it was empty
			$this->semanticData = new SemanticData( $this->getSubject() );
		}

		// Comparison must happen *before* the storage update;
		// even finding uses of a property fails after its type changed.
		if ( $this->updateJobs ) {

			$changeNotifier = $this->withContext()->getDependencyBuilder()->newObject( 'PropertyChangeNotifier', array(
				'SemanticData' => $this->semanticData
			) );

			$changeNotifier->detectChanges();
		}

		// Actually store semantic data, or at least clear it if needed
		if ( $processSemantics ) {
			$this->withContext()->getStore()->updateData( $this->semanticData );
		} else {
			$this->withContext()->getStore()->clearData( $this->semanticData->getSubject() );
		}

		Profiler::Out( __METHOD__, true );
		return true;
	}

	/**
	 * Returns whether the current Title is valid
	 *
	 * @since 1.9
	 *
	 * @param Title $title
	 *
	 * @return boolean
	 */
	protected function isValid( Title $title ) {
		return $this->withContext()->getDependencyBuilder()->newObject( 'NamespaceExaminer' )->isSemanticEnabled( $title->getNamespace() );
	}

}

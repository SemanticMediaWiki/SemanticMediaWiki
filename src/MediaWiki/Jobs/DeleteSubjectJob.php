<?php

namespace SMW\MediaWiki\Jobs;

use SMW\ApplicationFactory;
use SMW\DIWikiPage;
use Title;

/**
 * Handle subject removal directly or as deferred job
 *
 * @ingroup SMW
 *
 * @licence GNU GPL v2+
 * @since 1.9.1
 *
 * @author mwjames
 */
class DeleteSubjectJob extends JobBase {

	/**
	 * @since  1.9.1
	 *
	 * @param Title $title
	 * @param array $params job parameters
	 */
	public function __construct( Title $title, $params = array() ) {
		parent::__construct( 'SMW\DeleteSubjectJob', $title, $params );
		$this->removeDuplicates = true;
	}

	/**
	 * deleteSubject() will eliminate any associative reference to a subject
	 * therefore when run as `DeferredJob` and before the actual removal SemanticData
	 * are serialized and attached to the dispatch job, this allows to decouple
	 * deletion from an update process (prioritization between subject deletion
	 * and data refresh process)
	 *
	 * @since  1.9.1
	 *
	 * @return boolean
	 */
	public function execute() {

		if ( ApplicationFactory::getInstance()->getSettings()->get( 'smwgEnableUpdateJobs' ) &&
			$this->hasParameter( 'asDeferredJob' ) &&
			$this->getParameter( 'asDeferredJob' ) ) {
			$this->insertAsDeferredJobWithSemanticData()->pushToJobQueue();
			return $this->deleteSubject();
		}

		return $this->run();
	}

	/**
	 * @see Job::run
	 *
	 * @since  1.9.1
	 */
	public function run() {

		if ( $this->hasParameter( 'withAssociates' ) && $this->getParameter( 'withAssociates' ) ) {
			$this->initUpdateDispatcherJob();
		}

		return $this->deleteSubject();
	}

	protected function initUpdateDispatcherJob() {
		ApplicationFactory::getInstance()
			->newJobFactory()
			->newUpdateDispatcherJob( $this->getTitle(), $this->params )
			->run();
	}

	protected function deleteSubject() {
		ApplicationFactory::getInstance()->getStore()->deleteSubject( $this->getTitle() );
		return true;
	}

	protected function insertAsDeferredJobWithSemanticData() {

		$this->params['semanticData'] = $this->fetchSerializedSemanticData();

		$this->jobs[] = new self( $this->getTitle(), $this->params );
		return $this;
	}

	protected function fetchSerializedSemanticData() {

		$semanticDataSerializer = ApplicationFactory::getInstance()->newSerializerFactory()->newSemanticDataSerializer();

		return $semanticDataSerializer->serialize(
			ApplicationFactory::getInstance()->getStore()->getSemanticData( DIWikiPage::newFromTitle( $this->getTitle() ) )
		);
	}

}

<?php

namespace SMW;

use Title;
use Job;

/**
 * Handle subject removal directly or as deferred job
 *
 * @ingroup SMW
 *
 * @licence GNU GPL v2+
 * @since 1.9.0.1
 *
 * @author mwjames
 */
class DeleteSubjectJob extends JobBase {

	/** $var Job */
	protected $jobs = array();

	/** $var boolean */
	protected $enabledJob = true;

	/**
	 * @since  1.9.0.1
	 *
	 * @param Title $title
	 * @param array $params job parameters
	 */
	public function __construct( Title $title, $params = array() ) {
		parent::__construct( 'SMW\DeleteSubjectJob', $title, $params );
		$this->removeDuplicates = true;
	}

	/**
	 * Disables ability to insert jobs into the JobQueue
	 *
	 * @since  1.9.0.1
	 */
	public function disable() {
		$this->enabledJob = false;
		return $this;
	}

	/**
	 * deleteSubject() will eliminate any associative reference to a subject
	 * in the Store therefore before the actual removal SemanticData of that
	 * subject are serialized and attached to the dispatch job. This allows to
	 * decouple update from the delete process (prioritization between subject
	 * deletion and data refresh process)
	 *
	 * @since  1.9.0.1
	 *
	 * @return boolean
	 */
	public function execute() {

		if ( $this->withContext()->getSettings()->get( 'smwgEnableUpdateJobs' ) &&
			$this->hasParameter( 'asDeferredJob' ) &&
			$this->getParameter( 'asDeferredJob' ) ) {
			$this->instertAsDeferredJobWithSemanticData()->push();
		}

		return $this->run();
	}

	/**
	 * @see Job::run
	 *
	 * @since  1.9.0.1
	 */
	public function run() {

		if ( $this->hasParameter( 'withAssociates' ) && $this->getParameter( 'withAssociates' ) ) {
			$this->findAssociatesAndRunAsDispatchJob();
		}

		return $this->deleteSubject();
	}

	protected function push() {
		$this->enabledJob ? Job::batchInsert( $this->jobs ) : null;
		return true;
	}

	protected function findAssociatesAndRunAsDispatchJob() {
		$dispatcher = new UpdateDispatcherJob( $this->getTitle(), $this->params );
		$dispatcher->invokeContext( $this->withContext() );
		$dispatcher->run();
	}

	protected function deleteSubject() {
		$this->withContext()->getStore()->deleteSubject( $this->getTitle() );
		return true;
	}

	protected function instertAsDeferredJobWithSemanticData() {

		$this->addSerializedData();

		$this->jobs[] = new self( $this->getTitle(), $this->params );
		return $this;
	}

	protected function addSerializedData() {
		$this->params['semanticData'] = SerializerFactory::serialize(
			$this->withContext()->getStore()->getSemanticData( DIWikiPage::newFromTitle( $this->getTitle() ) )
		);
	}

}

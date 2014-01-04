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
 * @since 1.9
 *
 * @author mwjames
 */
class DeleteSubjectJob extends JobBase {

	/** $var Job */
	protected $jobs = array();

	/** $var boolean */
	protected $enabledJob = true;

	/**
	 * @since  1.9
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
	 * @since 1.9
	 */
	public function disable() {
		$this->enabledJob = false;
		return $this;
	}

	/**
	 * A deferred job mode is being introduced to avoid a performance penalty which
	 * can occur during processing if a large group of associate assignments are
	 * connected to the deleted subject
	 *
	 * @since  1.9
	 *
	 * @return boolean
	 */
	public function execute() {

		if ( $this->withContext()->getSettings()->get( 'smwgEnableUpdateJobs' ) &&
			$this->hasParameter( 'asDeferredJob' ) &&
			$this->getParameter( 'asDeferredJob' ) ) {
			return $this->instertAsDeferredJob()->push();
		}

		return $this->run();
	}

	/**
	 * @see Job::run
	 *
	 * @note UpdateDispatcherJob has to be executed before any store data is
	 * being modified (before deleteSubject) to ensure synchronize execution
	 *
	 * @since 1.9
	 */
	public function run() {

		if ( $this->hasParameter( 'withRefresh' ) && $this->getParameter( 'withRefresh' ) ) {
			$this->findAssociatesAndRefresh();
		}

		return $this->deleteSubject();
	}

	/**
	 * @since 1.9
	 */
	public function push() {
		$this->enabledJob ? Job::batchInsert( $this->jobs ) : null;
		return true;
	}

	/**
	 * @since 1.9
	 */
	protected function findAssociatesAndRefresh() {
		$dispatcher = new UpdateDispatcherJob( $this->getTitle() );
		$dispatcher->invokeContext( $this->withContext() );
		$dispatcher->run();
	}

	/**
	 * @since 1.9
	 */
	protected function deleteSubject() {
		$this->withContext()->getStore()->deleteSubject( $this->getTitle() );
		return true;
	}

	/**
	 * @since 1.9
	 */
	protected function instertAsDeferredJob() {
		$this->jobs[] = new self( $this->getTitle(), $this->params );
		return $this;
	}

}

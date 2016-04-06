<?php

namespace SMW\Tests\Utils\Runners;

use Job;
use JobQueueGroup;
use SMW\DBConnectionProvider;
use SMW\Tests\TestEnvironment;
use SMW\Tests\Utils\MwDBConnectionProvider;

/**
 * Partly copied from the MW 1.19 RunJobs maintenance script
 *
 * @group SMW
 * @group SMWExtension
 *
 * @license GNU GPL v2+
 * @since 1.9.2
 */
class JobQueueRunner {

	protected $type = null;
	protected $status = array();
	protected $dbConnectionProvider = null;

	/**
	 * @var TestEnvironment
	 */
	private $testEnvironment;

	/**
	 * @since 1.9.2
	 *
	 * @param string|null $type
	 * @param DBConnectionProvider|null $dbConnectionProvider
	 */
	public function __construct( $type = null, DBConnectionProvider $dbConnectionProvider = null ) {
		$this->type = $type;
		$this->dbConnectionProvider = $dbConnectionProvider;

		if ( $this->dbConnectionProvider === null ) {
			$this->dbConnectionProvider = new MwDBConnectionProvider();
		}

		$this->testEnvironment = new TestEnvironment();
	}

	/**
	 * @since 2.1
	 *
	 * @param string $type
	 *
	 * @return JobQueueRunner
	 */
	public function setType( $type ) {
		$this->type = $type;
		return $this;
	}

	/**
	 * @since 2.1
	 *
	 * @param DBConnectionProvider $dbConnectionProvider
	 *
	 * @return JobQueueRunner
	 */
	public function setDBConnectionProvider( DBConnectionProvider $dbConnectionProvider ) {
		$this->dbConnectionProvider = $dbConnectionProvider;
		return $this;
	}

	/**
	 * @since 1.9.2
	 */
	public function run() {

		$conds = '';

		if ( $this->type !== null ) {
			$conds = "job_cmd = " . $this->dbConnectionProvider->getConnection()->addQuotes( $this->type );
		}

		while ( $this->dbConnectionProvider->getConnection()->selectField( 'job', 'job_id', $conds, __METHOD__ ) ) {

			$job = $this->type === null ? $this->pop() : $this->pop_type( $this->type );

			if ( !$job ) {
				break;
			}

			wfWaitForSlaves();

			$this->status[] = array(
				'type'   => $job->command,
				'status' => $job->run()
			);
		}

		$this->testEnvironment->executePendingDeferredUpdates();
	}

	/**
	 * @since  2.0
	 */
	public function deleteAllJobs() {

		$conditions = '*';

		if ( $this->type !== null ) {
			$conditions = "job_cmd = " . $this->dbConnectionProvider->getConnection()->addQuotes( $this->type );
		}

		$this->dbConnectionProvider->getConnection()->delete(
			'job',
			$conditions,
			__METHOD__
		);
	}

	/**
	 * @since 1.9.2
	 *
	 * @return array
	 */
	public function getStatus() {
		return $this->status;
	}

	/**
	 * @see https://gerrit.wikimedia.org/r/#/c/162009/
	 */
	private function pop() {
		$offset = 0;

		if ( class_exists( 'JobQueueGroup' ) ) {
			return JobQueueGroup::singleton()->pop();
		}

		return Job::pop( $offset );
	}

	/**
	 * @see https://gerrit.wikimedia.org/r/#/c/162009/
	 */
	public function pop_type( $type ) {

		if ( class_exists( 'JobQueueGroup' ) ) {
			return JobQueueGroup::singleton()->get( $type )->pop();
		}

		return Job::pop_type( $type );
	}

}

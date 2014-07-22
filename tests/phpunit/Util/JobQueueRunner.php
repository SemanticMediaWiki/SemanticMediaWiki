<?php

namespace SMW\Tests\Util;

use SMW\DBConnectionProvider;

use Job;

/**
 * Partly copied from the MW 1.19 RunJobs maintenance script
 *
 * @ingroup Test
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
			$offset = 0;

			$job = $this->type === null ? Job::pop( $offset ) : Job::pop_type( $this->type );

			if ( !$job ) {
				break;
			}

			wfWaitForSlaves();

			$this->status[] = array(
				'type'   => $job->command,
				'status' => $job->run()
			);
		}
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

}

<?php

namespace SMW\Tests\Util;

use Job;

/**
 * Partly copied from the MW 1.19 RunJobs maintenance script
 *
 * @ingroup Test
 *
 * @group SMW
 * @group SMWExtension
 *
 * @licence GNU GPL v2+
 * @since 1.9.2
 */
class JobQueueRunner {

	protected $type = null;
	protected $status = array();
	protected $dbConnection = null;

	/**
	 * @since 1.9.2
	 *
	 * @return string $type
	 */
	public function __construct( $type = null ) {
		$this->type = $type;
	}

	/**
	 * @since 1.9.2
	 */
	public function execute() {

		$conds = '';

		if ( $this->type !== null ) {
			$conds = "job_cmd = " . $this->getDatabase()->addQuotes( $this->type );
		}

		while ( $this->getDatabase()->selectField( 'job', 'job_id', $conds, __METHOD__ ) ) {
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
	 * @since 1.9.2
	 *
	 * @return array
	 */
	public function getStatus() {
		return $this->status;
	}

	protected function getDatabase() {

		if ( $this->dbConnection === null ) {
			$this->dbConnection = wfGetDB( DB_MASTER );
		}

		return $this->dbConnection;
	}

}
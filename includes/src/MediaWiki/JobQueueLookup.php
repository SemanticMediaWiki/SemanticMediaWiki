<?php

namespace SMW\MediaWiki;

/**
 * @ingroup SMW
 *
 * @license GNU GPL v2+
 * @since 2.0
 *
 * @author mwjames
 */
class JobQueueLookup {

	/**
	 * @var Database
	 */
	protected $dbConnection = null;

	/**
	 * @since 2.0
	 *
	 * @param Database $database
	 */
	public function __construct( Database $dbConnection ) {
		$this->dbConnection = $dbConnection;
	}

	/**
	 * @since 2.0
	 *
	 * @return array
	 */
	public function getStatistics() {
		return array(
			'UPDATEJOB'  => $this->estimateJobCountFor( 'SMW\UpdateJob' ),
			'REFRESHJOB' => $this->estimateJobCountFor( 'SMW\RefreshJob' ),
			'DELETEJOB'  => $this->estimateJobCountFor( 'SMW\DeleteSubjectJob' )
		);
	}

	private function estimateJobCountFor( $jobName ) {

		$count = 0;

		$count = $this->dbConnection->estimateRowCount(
			'job',
			'*',
			array( 'job_cmd' => $jobName )
		);

		return (int)$count;
	}

}

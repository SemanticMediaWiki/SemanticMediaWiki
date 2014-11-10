<?php

namespace SMW\MediaWiki;

/**
 * @ingroup SMW
 *
 * @license GNU GPL v2+
 * @since 2.1
 *
 * @author mwjames
 */
class JobQueueLookup {

	/**
	 * @var Database
	 */
	protected $connection = null;

	/**
	 * @since 2.1
	 *
	 * @param Database $database
	 */
	public function __construct( Database $connection ) {
		$this->connection = $connection;
	}

	/**
	 * @since 2.1
	 *
	 * @param string $jobName
	 *
	 * @return integer
	 */
	public function estimateJobCountFor( $jobName ) {

		$count = $this->connection->estimateRowCount(
			'job',
			'*',
			array( 'job_cmd' => $jobName )
		);

		return (int)$count;
	}

}

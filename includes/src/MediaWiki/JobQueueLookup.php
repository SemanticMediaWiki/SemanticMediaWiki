<?php

namespace SMW\MediaWiki;

/**
 * @license GNU GPL v2+
 * @since 2.1
 *
 * @author mwjames
 */
class JobQueueLookup {

	/**
	 * @var Database
	 */
	private $connection = null;

	/**
	 * @var string
	 */
	private $tablename = '';

	/**
	 * @since 2.1
	 *
	 * @param Database $connection
	 */
	public function __construct( Database $connection ) {
		$this->connection = $connection;
		$this->tablename = 'job';
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
			$this->tablename,
			'*',
			array( 'job_cmd' => $jobName )
		);

		return (int)$count;
	}

	/**
	 * @since 2.1
	 *
	 * @param string $jobName
	 *
	 * @return boolean
	 */
	public function selectJobRowFor( $jobName ) {

		$row = $this->connection->selectRow(
			$this->tablename,
			'*',
			array( 'job_cmd' => $jobName ),
			__METHOD__
		);

		return $row;
	}

}

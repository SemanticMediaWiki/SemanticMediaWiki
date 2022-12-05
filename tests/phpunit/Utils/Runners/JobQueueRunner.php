<?php

namespace SMW\Tests\Utils\Runners;

use Job;
use JobQueueGroup;
use MediaWiki\MediaWikiServices;
use SMW\Connection\ConnectionProvider;
use SMW\Tests\TestEnvironment;
use SMW\Tests\Utils\Connection\TestDatabaseConnectionProvider;

/**
 * Partially copied from the MW 1.19 RunJobs maintenance script
 *
 * @group SMW
 * @group SMWExtension
 *
 * @license GNU GPL v2+
 * @since 1.9.2
 */
class JobQueueRunner {

	protected $type = null;
	protected $status = [];
	protected $connectionProvider = null;
	private $lbFactory;

	/**
	 * @var TestEnvironment
	 */
	private $testEnvironment;

	/**
	 * @since 1.9.2
	 *
	 * @param string|null $type
	 * @param ConnectionProvider|null $connectionProvider
	 */
	public function __construct( $type = null, ConnectionProvider $connectionProvider = null ) {
		$this->type = $type;
		$this->connectionProvider = $connectionProvider;
		$this->lbFactory = MediaWikiServices::getInstance()->getDBLoadBalancerFactory();

		if ( $this->connectionProvider === null ) {
			$this->connectionProvider = new TestDatabaseConnectionProvider();
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
	 * @param IConnectionProvider $connectionProvider
	 *
	 * @return JobQueueRunner
	 */
	public function setConnectionProvider( ConnectionProvider $connectionProvider ) {
		$this->connectionProvider = $connectionProvider;
		return $this;
	}

	/**
	 * @since 1.9.2
	 */
	public function run() {

		$conds = '';
		$connection = $this->connectionProvider->getConnection();

		if ( $this->type !== null ) {
			$conds = "job_cmd = " . $connection->addQuotes( $this->type );
		}

		while ( $connection->selectField( 'job', 'job_id', $conds, __METHOD__ ) ) {

			$job = $this->type === null ? $this->pop() : $this->pop_type( $this->type );

			if ( !$job ) {
				break;
			}

			$this->lbFactory->waitForReplication();

			$this->status[] = [
				'type'   => $job->command,
				'status' => $job->run()
			];
		}

		$this->testEnvironment->executePendingDeferredUpdates();
	}

	/**
	 * @since  2.0
	 */
	public function deleteAllJobs() {

		$conditions = '*';
		$connection = $this->connectionProvider->getConnection();

		if ( $this->type !== null ) {
			$conditions = "job_cmd = " . $connection->addQuotes( $this->type );
		}

		$connection->delete(
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

		if ( method_exists( MediaWikiServices::class, 'getJobQueueGroup' ) ) {
			// MW 1.37+
			return MediaWikiServices::getInstance()->getJobQueueGroup()->pop();
		} else {
			return JobQueueGroup::singleton()->pop();
		}
	}

	/**
	 * @see https://gerrit.wikimedia.org/r/#/c/162009/
	 */
	public function pop_type( $type ) {

		if ( method_exists( MediaWikiServices::class, 'getJobQueueGroup' ) ) {
			// MW 1.37+
			return MediaWikiServices::getInstance()->getJobQueueGroup()->get( $type )->pop();
		} else {
			return JobQueueGroup::singleton()->get( $type )->pop();
		}
	}

}

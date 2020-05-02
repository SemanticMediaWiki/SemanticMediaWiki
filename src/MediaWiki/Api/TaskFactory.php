<?php

namespace SMW\MediaWiki\Api;

use SMW\ApplicationFactory;
use SMW\MediaWiki\Api\Tasks\Task;
use SMW\MediaWiki\Api\Tasks\UpdateTask;
use SMW\MediaWiki\Api\Tasks\CheckQueryTask;
use SMW\MediaWiki\Api\Tasks\DuplicateLookupTask;
use SMW\MediaWiki\Api\Tasks\InsertJobTask;
use SMW\MediaWiki\Api\Tasks\JobListTask;
use SMW\MediaWiki\Api\Tasks\TableStatisticsTask;
use SMW\MediaWiki\Api\Tasks\EntityExaminerTask;
use SMW\Indicator\EntityExaminerIndicatorsFactory;
use RuntimeException;
use User;

/**
 * @license GNU GPL v2+
 * @since 3.1
 *
 * @author mwjames
 */
class TaskFactory {

	/**
	 * @var []
	 */
	private static $services;

	/**
	 * @since 3.1
	 *
	 * @return []
	 */
	public function getAllowedTypes() {

		if ( self::$services === null ) {
			\Hooks::run( 'SMW::Api::AddTasks', [ &self::$services ] );
		}

		$types = [
			// Run update using the updateJob
			'update',

			// Run a query check
			'check-query',

			// Run deferred integrity examiners
			'run-entity-examiner',

			// Duplicate lookup support
			'duplicate-lookup',

			// Fetch some table statistics
			'table-statistics',

			// Insert/run a job
			'insert-job',

			// Run jobs from a list directly without the job scheduler
			'run-joblist'
		];

		if ( is_array( self::$services ) ) {
			$types = array_merge( $types, array_keys( self::$services ) );
		}

		return $types;
	}

	/**
	 * @since 3.1
	 *
	 * @param string $type
	 *
	 * @throws RuntimeException
	 */
	public function newByType( $type, User $user = null ) {

		$applicationFactory = ApplicationFactory::getInstance();
		$service = null;

		switch ( $type ) {
			case 'update':
				return new UpdateTask( $applicationFactory->newJobFactory() );
				break;
			case 'check-query':
				return new CheckQueryTask( $applicationFactory->getStore() );
				break;
			case 'run-entity-examiner':
				return $this->newEntityExaminerTask( $user );
				break;
			case 'duplicate-lookup':
				return $this->newDuplicateLookupTask();
				break;
			case 'table-statistics':
				return $this->newTableStatisticsTask();
				break;
			case 'insert-job':
				return new InsertJobTask( $applicationFactory->newJobFactory() );
				break;
			case 'run-joblist':
				return new JobListTask( $applicationFactory->getJobQueue() );
				break;
		}

		if ( is_array( self::$services ) && isset( self::$services[$type] ) && is_callable( self::$services[$type] ) ) {
			$service = call_user_func( self::$services[$type] );
		}

		if ( $service instanceof Task ) {
			return $service;
		}

		throw new RuntimeException( "$type is an unknown task type!" );
	}

	/**
	 * @since 3.1
	 *
	 * @return DuplicateLookupTask
	 */
	public function newDuplicateLookupTask() {

		$applicationFactory = ApplicationFactory::getInstance();

		$duplicateLookupTask = new DuplicateLookupTask(
			$applicationFactory->getStore(),
			$applicationFactory->getCache()
		);

		$duplicateLookupTask->setCacheUsage(
			$applicationFactory->getSettings()->get( 'smwgCacheUsage' )
		);

		return $duplicateLookupTask;
	}

	/**
	 * @since 3.1
	 *
	 * @return TableStatisticsTask
	 */
	public function newTableStatisticsTask() {

		$applicationFactory = ApplicationFactory::getInstance();

		$tableStatisticsTask = new TableStatisticsTask(
			$applicationFactory->getStore(),
			$applicationFactory->getCache()
		);

		$tableStatisticsTask->setCacheUsage(
			$applicationFactory->getSettings()->get( 'smwgCacheUsage' )
		);

		return $tableStatisticsTask;
	}

	/**
	 * @since 3.2
	 *
	 * @return EntityExaminerTask
	 */
	public function newEntityExaminerTask( User $user = null ) : EntityExaminerTask {

		$applicationFactory = ApplicationFactory::getInstance();

		$entityExaminerTask = new EntityExaminerTask(
			$applicationFactory->getStore(),
			new EntityExaminerIndicatorsFactory()
		);

		$entityExaminerTask->setPermissionExaminer(
			$applicationFactory->newPermissionExaminer( $user )
		);

		return $entityExaminerTask;
	}

}

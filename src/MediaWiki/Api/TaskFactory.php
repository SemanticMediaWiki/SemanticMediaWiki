<?php

namespace SMW\MediaWiki\Api;

use MediaWiki\HookContainer\HookContainer;
use MediaWiki\User\User;
use Onoi\Cache\Cache;
use RuntimeException;
use SMW\Indicator\EntityExaminerIndicatorsFactory;
use SMW\MediaWiki\Api\Tasks\CheckQueryTask;
use SMW\MediaWiki\Api\Tasks\DuplicateLookupTask;
use SMW\MediaWiki\Api\Tasks\EntityExaminerTask;
use SMW\MediaWiki\Api\Tasks\InsertJobTask;
use SMW\MediaWiki\Api\Tasks\JobListTask;
use SMW\MediaWiki\Api\Tasks\TableStatisticsTask;
use SMW\MediaWiki\Api\Tasks\Task;
use SMW\MediaWiki\Api\Tasks\UpdateTask;
use SMW\MediaWiki\JobFactory;
use SMW\MediaWiki\JobQueue;
use SMW\Services\ServicesFactory as ApplicationFactory;
use SMW\Settings;
use SMW\Store;

/**
 * @license GPL-2.0-or-later
 * @since 3.1
 *
 * @author mwjames
 */
class TaskFactory {

	/**
	 * Lazily-computed contributions of the `SMW::Api::AddTasks` hook against
	 * this instance's HookContainer. Memoised because the hook is idempotent
	 * across calls.
	 *
	 * @var array<string,callable>|null
	 */
	private ?array $hookServices = null;

	/**
	 * @since 7.0.0
	 */
	public function __construct(
		private readonly Store $store,
		private readonly JobQueue $jobQueue,
		private readonly Cache $cache,
		private readonly Settings $settings,
		private readonly JobFactory $jobFactory,
		private readonly HookContainer $hookContainer
	) {
	}

	/**
	 * @since 3.1
	 */
	public function getAllowedTypes(): array {
		$services = $this->getHookServices();

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

		if ( $services !== [] ) {
			$types = array_merge( $types, array_keys( $services ) );
		}

		return $types;
	}

	/**
	 * @since 3.1
	 *
	 * @throws RuntimeException
	 */
	public function newByType( string $type, ?User $user = null ): Task {
		$service = null;

		switch ( $type ) {
			case 'update':
				return new UpdateTask( $this->jobFactory );
			case 'check-query':
				return new CheckQueryTask( $this->store );
			case 'run-entity-examiner':
				return $this->newEntityExaminerTask( $user );
			case 'duplicate-lookup':
				return $this->newDuplicateLookupTask();
			case 'table-statistics':
				return $this->newTableStatisticsTask();
			case 'insert-job':
				return new InsertJobTask( $this->jobFactory );
			case 'run-joblist':
				return new JobListTask( $this->jobQueue );
		}

		$services = $this->getHookServices();

		if ( isset( $services[$type] ) && is_callable( $services[$type] ) ) {
			$service = call_user_func( $services[$type] );
		}

		if ( $service instanceof Task ) {
			return $service;
		}

		throw new RuntimeException( "$type is an unknown task type!" );
	}

	/**
	 * @since 3.1
	 */
	public function newDuplicateLookupTask(): DuplicateLookupTask {
		$duplicateLookupTask = new DuplicateLookupTask(
			$this->store,
			$this->cache
		);

		$duplicateLookupTask->setCacheUsage(
			$this->settings->get( 'smwgCacheUsage' )
		);

		return $duplicateLookupTask;
	}

	/**
	 * @since 3.1
	 */
	public function newTableStatisticsTask(): TableStatisticsTask {
		$tableStatisticsTask = new TableStatisticsTask(
			$this->store,
			$this->cache
		);

		$tableStatisticsTask->setCacheUsage(
			$this->settings->get( 'smwgCacheUsage' )
		);

		return $tableStatisticsTask;
	}

	/**
	 * @since 3.2
	 */
	public function newEntityExaminerTask( ?User $user = null ): EntityExaminerTask {
		$entityExaminerTask = new EntityExaminerTask(
			$this->store,
			new EntityExaminerIndicatorsFactory()
		);

		$entityExaminerTask->setPermissionExaminer(
			ApplicationFactory::getInstance()->newPermissionExaminer( $user )
		);

		return $entityExaminerTask;
	}

	/**
	 * Runs `SMW::Api::AddTasks` against the injected HookContainer on first
	 * use and memoises the resulting service array on this instance. The
	 * hook is expected to be idempotent across calls.
	 *
	 * @return array<string,callable>
	 */
	private function getHookServices(): array {
		if ( $this->hookServices === null ) {
			$services = null;

			$this->hookContainer->run( 'SMW::Api::AddTasks', [ &$services ] );

			// @phan-suppress-next-line PhanImpossibleCondition
			$this->hookServices = is_array( $services ) ? $services : [];
		}

		return $this->hookServices;
	}

}

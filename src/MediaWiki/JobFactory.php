<?php

namespace SMW\MediaWiki;

use MediaWiki\JobQueue\JobFactory as MwJobFactory;
use MediaWiki\MediaWikiServices;
use MediaWiki\Title\Title;
use RuntimeException;
use SMW\Elastic\Jobs\FileIngestJob;
use SMW\Elastic\Jobs\IndexerRecoveryJob;
use SMW\MediaWiki\Jobs\ChangePropagationClassUpdateJob;
use SMW\MediaWiki\Jobs\ChangePropagationDispatchJob;
use SMW\MediaWiki\Jobs\ChangePropagationUpdateJob;
use SMW\MediaWiki\Jobs\EntityIdDisposerJob;
use SMW\MediaWiki\Jobs\FulltextSearchTableRebuildJob;
use SMW\MediaWiki\Jobs\FulltextSearchTableUpdateJob;
use SMW\MediaWiki\Jobs\NullJob;
use SMW\MediaWiki\Jobs\ParserCachePurgeJob;
use SMW\MediaWiki\Jobs\PropertyStatisticsRebuildJob;
use SMW\MediaWiki\Jobs\RefreshJob;
use SMW\MediaWiki\Jobs\UpdateDispatcherJob;
use SMW\MediaWiki\Jobs\UpdateJob;

/**
 * Typed wrapper around MediaWiki's JobFactory for the SMW job classes.
 *
 * Each `newXxxJob()` method calls `MwJobFactory::newJob()` which resolves the
 * JobClasses ObjectFactory spec, constructing the job with any declared
 * service dependencies. The Title is passed via `namespace`/`title` keys in
 * `$params` so the call matches MW's modern two-argument signature (the
 * legacy three-argument back-compat form trips
 * `PhanTypeMismatchArgumentProbablyReal`). Each return suppresses
 * `PhanTypeMismatchReturnSuperType`: `JobFactory::newJob()` declares
 * `: Job`, but the JobClasses spec guarantees the matching subclass at
 * runtime, so the covariance is safe.
 *
 * @license GPL-2.0-or-later
 * @since 2.0
 *
 * @author mwjames
 */
class JobFactory {

	private MwJobFactory $mwJobFactory;

	/**
	 * @since 7.0.0
	 */
	public function __construct( ?MwJobFactory $mwJobFactory = null ) {
		$this->mwJobFactory = $mwJobFactory ?? MediaWikiServices::getInstance()->getJobFactory();
	}

	/**
	 * @since 2.5
	 *
	 * @param array $jobs
	 */
	public function batchInsert( array $jobs ): void {
		Job::batchInsert( $jobs );
	}

	/**
	 * @since 2.5
	 *
	 * @param string $type
	 * @param Title|null $title
	 * @param array $parameters
	 *
	 * @throws RuntimeException
	 */
	public function newByType( $type, ?Title $title = null, array $parameters = [] ): Job {
		if ( $title === null ) {
			return new NullJob( null );
		}

		// Map the typed names exposed by SMW (and a couple of legacy aliases
		// kept for back-compat) to the JobClasses commands.
		$command = match ( $type ) {
			'smw.refresh' => 'smw.refresh',
			'smw.update' => 'smw.update',
			'smw.updateDispatcher' => 'smw.updateDispatcher',
			'smw.parserCachePurge', 'smw.parserCachePurgeJob' => 'smw.parserCachePurgeJob',
			'smw.entityIdDisposer' => 'smw.entityIdDisposer',
			'smw.propertyStatisticsRebuild' => 'smw.propertyStatisticsRebuild',
			'smw.fulltextSearchTableUpdate' => 'smw.fulltextSearchTableUpdate',
			'smw.fulltextSearchTableRebuild' => 'smw.fulltextSearchTableRebuild',
			'smw.changePropagationDispatch' => 'smw.changePropagationDispatch',
			'smw.changePropagationUpdate' => 'smw.changePropagationUpdate',
			'smw.changePropagationClassUpdate' => 'smw.changePropagationClassUpdate',
			'smw.elasticFileIngest' => 'smw.elasticFileIngest',
			'smw.elasticIndexerRecovery' => 'smw.elasticIndexerRecovery',
			default => throw new RuntimeException( "Unable to match $type to a valid Job type" ),
		};

		// @phan-suppress-next-line PhanTypeMismatchReturnSuperType
		return $this->mwJobFactory->newJob(
			$command,
			[ 'namespace' => $title->getNamespace(), 'title' => $title->getDBkey() ] + $parameters
		);
	}

	/**
	 * @since 2.5
	 */
	public function newRefreshJob( Title $title, array $parameters = [] ): RefreshJob {
		// @phan-suppress-next-line PhanTypeMismatchReturnSuperType
		return $this->mwJobFactory->newJob(
			'smw.refresh',
			[ 'namespace' => $title->getNamespace(), 'title' => $title->getDBkey() ] + $parameters
		);
	}

	/**
	 * @since 2.0
	 */
	public function newUpdateJob( Title $title, array $parameters = [] ): UpdateJob {
		// @phan-suppress-next-line PhanTypeMismatchReturnSuperType
		return $this->mwJobFactory->newJob(
			'smw.update',
			[ 'namespace' => $title->getNamespace(), 'title' => $title->getDBkey() ] + $parameters
		);
	}

	/**
	 * @since 2.0
	 */
	public function newUpdateDispatcherJob( Title $title, array $parameters = [] ): UpdateDispatcherJob {
		// @phan-suppress-next-line PhanTypeMismatchReturnSuperType
		return $this->mwJobFactory->newJob(
			'smw.updateDispatcher',
			[ 'namespace' => $title->getNamespace(), 'title' => $title->getDBkey() ] + $parameters
		);
	}

	/**
	 * @since 2.5
	 */
	public function newFulltextSearchTableUpdateJob( Title $title, array $parameters = [] ): FulltextSearchTableUpdateJob {
		// @phan-suppress-next-line PhanTypeMismatchReturnSuperType
		return $this->mwJobFactory->newJob(
			'smw.fulltextSearchTableUpdate',
			[ 'namespace' => $title->getNamespace(), 'title' => $title->getDBkey() ] + $parameters
		);
	}

	/**
	 * @since 2.5
	 */
	public function newEntityIdDisposerJob( Title $title, array $parameters = [] ): EntityIdDisposerJob {
		// @phan-suppress-next-line PhanTypeMismatchReturnSuperType
		return $this->mwJobFactory->newJob(
			'smw.entityIdDisposer',
			[ 'namespace' => $title->getNamespace(), 'title' => $title->getDBkey() ] + $parameters
		);
	}

	/**
	 * @since 2.5
	 */
	public function newPropertyStatisticsRebuildJob( Title $title, array $parameters = [] ): PropertyStatisticsRebuildJob {
		// @phan-suppress-next-line PhanTypeMismatchReturnSuperType
		return $this->mwJobFactory->newJob(
			'smw.propertyStatisticsRebuild',
			[ 'namespace' => $title->getNamespace(), 'title' => $title->getDBkey() ] + $parameters
		);
	}

	/**
	 * @since 2.5
	 */
	public function newFulltextSearchTableRebuildJob( Title $title, array $parameters = [] ): FulltextSearchTableRebuildJob {
		// @phan-suppress-next-line PhanTypeMismatchReturnSuperType
		return $this->mwJobFactory->newJob(
			'smw.fulltextSearchTableRebuild',
			[ 'namespace' => $title->getNamespace(), 'title' => $title->getDBkey() ] + $parameters
		);
	}

	/**
	 * @since 3.0
	 */
	public function newChangePropagationDispatchJob( Title $title, array $parameters = [] ): ChangePropagationDispatchJob {
		// @phan-suppress-next-line PhanTypeMismatchReturnSuperType
		return $this->mwJobFactory->newJob(
			'smw.changePropagationDispatch',
			[ 'namespace' => $title->getNamespace(), 'title' => $title->getDBkey() ] + $parameters
		);
	}

	/**
	 * @since 3.0
	 */
	public function newChangePropagationUpdateJob( Title $title, array $parameters = [] ): ChangePropagationUpdateJob {
		// @phan-suppress-next-line PhanTypeMismatchReturnSuperType
		return $this->mwJobFactory->newJob(
			'smw.changePropagationUpdate',
			[ 'namespace' => $title->getNamespace(), 'title' => $title->getDBkey() ] + $parameters
		);
	}

	/**
	 * @since 3.0
	 */
	public function newChangePropagationClassUpdateJob( Title $title, array $parameters = [] ): ChangePropagationClassUpdateJob {
		// @phan-suppress-next-line PhanTypeMismatchReturnSuperType
		return $this->mwJobFactory->newJob(
			'smw.changePropagationClassUpdate',
			[ 'namespace' => $title->getNamespace(), 'title' => $title->getDBkey() ] + $parameters
		);
	}

	/**
	 * @since 3.1
	 */
	public function newParserCachePurgeJob( Title $title, array $parameters = [] ): ParserCachePurgeJob {
		// @phan-suppress-next-line PhanTypeMismatchReturnSuperType
		return $this->mwJobFactory->newJob(
			'smw.parserCachePurgeJob',
			[ 'namespace' => $title->getNamespace(), 'title' => $title->getDBkey() ] + $parameters
		);
	}

	/**
	 * @since 7.0.0
	 */
	public function newFileIngestJob( Title $title, array $parameters = [] ): FileIngestJob {
		// @phan-suppress-next-line PhanTypeMismatchReturnSuperType
		return $this->mwJobFactory->newJob(
			'smw.elasticFileIngest',
			[ 'namespace' => $title->getNamespace(), 'title' => $title->getDBkey() ] + $parameters
		);
	}

	/**
	 * @since 7.0.0
	 */
	public function newIndexerRecoveryJob( Title $title, array $parameters = [] ): IndexerRecoveryJob {
		// @phan-suppress-next-line PhanTypeMismatchReturnSuperType
		return $this->mwJobFactory->newJob(
			'smw.elasticIndexerRecovery',
			[ 'namespace' => $title->getNamespace(), 'title' => $title->getDBkey() ] + $parameters
		);
	}

}

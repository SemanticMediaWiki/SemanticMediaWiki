<?php

namespace SMW\MediaWiki;

use InvalidArgumentException;
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
 * Each `newXxxJob()` method delegates to `MwJobFactory::newJob()` which in turn
 * resolves the JobClasses ObjectFactory spec (constructing the job with any
 * declared service dependencies). The wrapper exists so call-sites can keep
 * the typed factory methods that pre-date constructor injection.
 *
 * @license GPL-2.0-or-later
 * @since 2.0
 *
 * @author mwjames
 */
class JobFactory {

	private MwJobFactory $mwJobFactory;

	public function __construct( ?MwJobFactory $mwJobFactory = null ) {
		$this->mwJobFactory = $mwJobFactory ?? MediaWikiServices::getInstance()->getJobFactory();
	}

	/**
	 * @since 2.5
	 *
	 * @param array $jobs
	 */
	public static function batchInsert( array $jobs ): void {
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
			default => throw new RuntimeException( "Unable to match $type to a valid Job type" ),
		};

		/** @var Job $job */
		$job = $this->mwJobFactory->newJob( $command, $title, $parameters );

		return $job;
	}

	/**
	 * @since 2.5
	 */
	public function newRefreshJob( Title $title, array $parameters = [] ): RefreshJob {
		/** @var RefreshJob $job */
		$job = $this->mwJobFactory->newJob( 'smw.refresh', $title, $parameters );
		return $job;
	}

	/**
	 * @since 2.0
	 */
	public function newUpdateJob( Title $title, array $parameters = [] ): UpdateJob {
		/** @var UpdateJob $job */
		$job = $this->mwJobFactory->newJob( 'smw.update', $title, $parameters );
		return $job;
	}

	/**
	 * @since 2.0
	 */
	public function newUpdateDispatcherJob( Title $title, array $parameters = [] ): UpdateDispatcherJob {
		/** @var UpdateDispatcherJob $job */
		$job = $this->mwJobFactory->newJob( 'smw.updateDispatcher', $title, $parameters );
		return $job;
	}

	/**
	 * @since 2.5
	 */
	public function newFulltextSearchTableUpdateJob( Title $title, array $parameters = [] ): FulltextSearchTableUpdateJob {
		/** @var FulltextSearchTableUpdateJob $job */
		$job = $this->mwJobFactory->newJob( 'smw.fulltextSearchTableUpdate', $title, $parameters );
		return $job;
	}

	/**
	 * @since 2.5
	 */
	public function newEntityIdDisposerJob( Title $title, array $parameters = [] ): EntityIdDisposerJob {
		/** @var EntityIdDisposerJob $job */
		$job = $this->mwJobFactory->newJob( 'smw.entityIdDisposer', $title, $parameters );
		return $job;
	}

	/**
	 * @since 2.5
	 */
	public function newPropertyStatisticsRebuildJob( Title $title, array $parameters = [] ): PropertyStatisticsRebuildJob {
		/** @var PropertyStatisticsRebuildJob $job */
		$job = $this->mwJobFactory->newJob( 'smw.propertyStatisticsRebuild', $title, $parameters );
		return $job;
	}

	/**
	 * @since 2.5
	 */
	public function newFulltextSearchTableRebuildJob( Title $title, array $parameters = [] ): FulltextSearchTableRebuildJob {
		/** @var FulltextSearchTableRebuildJob $job */
		$job = $this->mwJobFactory->newJob( 'smw.fulltextSearchTableRebuild', $title, $parameters );
		return $job;
	}

	/**
	 * @since 3.0
	 */
	public function newChangePropagationDispatchJob( Title $title, array $parameters = [] ): ChangePropagationDispatchJob {
		/** @var ChangePropagationDispatchJob $job */
		$job = $this->mwJobFactory->newJob( 'smw.changePropagationDispatch', $title, $parameters );
		return $job;
	}

	/**
	 * @since 3.0
	 */
	public function newChangePropagationUpdateJob( Title $title, array $parameters = [] ): ChangePropagationUpdateJob {
		/** @var ChangePropagationUpdateJob $job */
		$job = $this->mwJobFactory->newJob( 'smw.changePropagationUpdate', $title, $parameters );
		return $job;
	}

	/**
	 * @since 3.0
	 */
	public function newChangePropagationClassUpdateJob( Title $title, array $parameters = [] ): ChangePropagationClassUpdateJob {
		/** @var ChangePropagationClassUpdateJob $job */
		$job = $this->mwJobFactory->newJob( 'smw.changePropagationClassUpdate', $title, $parameters );
		return $job;
	}

	/**
	 * @since 3.1
	 */
	public function newParserCachePurgeJob( Title $title, array $parameters = [] ): ParserCachePurgeJob {
		/** @var ParserCachePurgeJob $job */
		$job = $this->mwJobFactory->newJob( 'smw.parserCachePurgeJob', $title, $parameters );
		return $job;
	}

	/**
	 * @since 7.0.0
	 */
	public function newFileIngestJob( Title $title, array $parameters = [] ): FileIngestJob {
		/** @var FileIngestJob $job */
		$job = $this->mwJobFactory->newJob( 'smw.elasticFileIngest', $title, $parameters );
		return $job;
	}

	/**
	 * @since 7.0.0
	 */
	public function newIndexerRecoveryJob( Title $title, array $parameters = [] ): IndexerRecoveryJob {
		/** @var IndexerRecoveryJob $job */
		$job = $this->mwJobFactory->newJob( 'smw.elasticIndexerRecovery', $title, $parameters );
		return $job;
	}

}

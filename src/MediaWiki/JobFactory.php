<?php

namespace SMW\MediaWiki;

use SMW\MediaWiki\Jobs\NullJob;
use SMW\MediaWiki\Jobs\RefreshJob;
use SMW\MediaWiki\Jobs\UpdateJob;
use SMW\MediaWiki\Jobs\UpdateDispatcherJob;
use SMW\MediaWiki\Jobs\EntityIdDisposerJob;
use SMW\MediaWiki\Jobs\PropertyStatisticsRebuildJob;
use SMW\MediaWiki\Jobs\FulltextSearchTableUpdateJob;
use SMW\MediaWiki\Jobs\FulltextSearchTableRebuildJob;
use SMW\MediaWiki\Jobs\ChangePropagationDispatchJob;
use SMW\MediaWiki\Jobs\ChangePropagationUpdateJob;
use SMW\MediaWiki\Jobs\ChangePropagationClassUpdateJob;
use SMW\MediaWiki\Jobs\ParserCachePurgeJob;
use RuntimeException;
use Title;

/**
 * @license GNU GPL v2+
 * @since 2.0
 *
 * @author mwjames
 */
class JobFactory {

	/**
	 * @since 2.5
	 *
	 * @param array $jobs
	 */
	public static function batchInsert( array $jobs ) {
		Job::batchInsert( $jobs );
	}

	/**
	 * @since 2.5
	 *
	 * @param string $type
	 * @param Title|null $title
	 * @param array $parameters
	 *
	 * @return Job
	 * @throws RuntimeException
	 */
	public function newByType( $type, Title $title = null, array $parameters = [] ) {

		if ( $title === null ) {
			return new NullJob( null );
		}

		switch ( $type ) {
			case 'SMW\RefreshJob':
			case 'smw.refresh':
				return $this->newRefreshJob( $title, $parameters );
			case 'SMW\UpdateJob':
			case 'smw.update':
				return $this->newUpdateJob( $title, $parameters );
			case 'SMW\UpdateDispatcherJob':
			case 'smw.updateDispatcher':
				return $this->newUpdateDispatcherJob( $title, $parameters );
			case 'SMW\ParserCachePurgeJob':
			case 'smw.parserCachePurge':
				return $this->newParserCachePurgeJob( $title, $parameters );
			case 'SMW\EntityIdDisposerJob':
			case 'smw.entityIdDisposer':
				return $this->newEntityIdDisposerJob( $title, $parameters );
			case 'SMW\PropertyStatisticsRebuildJob':
			case 'smw.propertyStatisticsRebuild':
				return $this->newPropertyStatisticsRebuildJob( $title, $parameters );
			case 'SMW\FulltextSearchTableUpdateJob':
			case 'smw.fulltextSearchTableUpdate':
				return $this->newFulltextSearchTableUpdateJob( $title, $parameters );
			case 'SMW\FulltextSearchTableRebuildJob':
			case 'smw.fulltextSearchTableRebuild':
				return $this->newFulltextSearchTableRebuildJob( $title, $parameters );
			case 'SMW\ChangePropagationDispatchJob':
			case 'smw.changePropagationDispatch':
				return $this->newChangePropagationDispatchJob( $title, $parameters );
			case 'SMW\ChangePropagationUpdateJob':
			case 'smw.changePropagationUpdate':
				return $this->newChangePropagationUpdateJob( $title, $parameters );
			case 'SMW\ChangePropagationClassUpdateJob':
			case 'smw.changePropagationClassUpdate':
				return $this->newChangePropagationClassUpdateJob( $title, $parameters );
		}

		throw new RuntimeException( "Unable to match $type to a valid Job type" );
	}

	/**
	 * @since 2.5
	 *
	 * @param Title $title
	 * @param array $parameters
	 *
	 * @return RefreshJob
	 */
	public function newRefreshJob( Title $title, array $parameters = [] ) {
		return new RefreshJob( $title, $parameters );
	}

	/**
	 * @since 2.0
	 *
	 * @param Title $title
	 * @param array $parameters
	 *
	 * @return UpdateJob
	 */
	public function newUpdateJob( Title $title, array $parameters = [] ) {
		return new UpdateJob( $title, $parameters );
	}

	/**
	 * @since 2.0
	 *
	 * @param Title $title
	 * @param array $parameters
	 *
	 * @return UpdateDispatcherJob
	 */
	public function newUpdateDispatcherJob( Title $title, array $parameters = [] ) {
		return new UpdateDispatcherJob( $title, $parameters );
	}

	/**
	 * @since 2.5
	 *
	 * @param Title $title
	 * @param array $parameters
	 *
	 * @return FulltextSearchTableUpdateJob
	 */
	public function newFulltextSearchTableUpdateJob( Title $title, array $parameters = [] ) {
		return new FulltextSearchTableUpdateJob( $title, $parameters );
	}

	/**
	 * @since 2.5
	 *
	 * @param Title $title
	 * @param array $parameters
	 *
	 * @return EntityIdDisposerJob
	 */
	public function newEntityIdDisposerJob( Title $title, array $parameters = [] ) {
		return new EntityIdDisposerJob( $title, $parameters );
	}

	/**
	 * @since 2.5
	 *
	 * @param Title $title
	 * @param array $parameters
	 *
	 * @return PropertyStatisticsRebuildJob
	 */
	public function newPropertyStatisticsRebuildJob( Title $title, array $parameters = [] ) {
		return new PropertyStatisticsRebuildJob( $title, $parameters );
	}

	/**
	 * @since 2.5
	 *
	 * @param Title $title
	 * @param array $parameters
	 *
	 * @return FulltextSearchTableRebuildJob
	 */
	public function newFulltextSearchTableRebuildJob( Title $title, array $parameters = [] ) {
		return new FulltextSearchTableRebuildJob( $title, $parameters );
	}

	/**
	 * @since 3.0
	 *
	 * @param Title $title
	 * @param array $parameters
	 *
	 * @return ChangePropagationDispatchJob
	 */
	public function newChangePropagationDispatchJob( Title $title, array $parameters = [] ) {
		return new ChangePropagationDispatchJob( $title, $parameters );
	}

	/**
	 * @since 3.0
	 *
	 * @param Title $title
	 * @param array $parameters
	 *
	 * @return ChangePropagationUpdateJob
	 */
	public function newChangePropagationUpdateJob( Title $title, array $parameters = [] ) {
		return new ChangePropagationUpdateJob( $title, $parameters );
	}

	/**
	 * @since 3.0
	 *
	 * @param Title $title
	 * @param array $parameters
	 *
	 * @return ChangePropagationClassUpdateJob
	 */
	public function newChangePropagationClassUpdateJob( Title $title, array $parameters = [] ) {
		return new ChangePropagationClassUpdateJob( $title, $parameters );
	}

	/**
	 * @since 3.1
	 *
	 * @param Title $title
	 * @param array $parameters
	 *
	 * @return ParserCachePurgeJob
	 */
	public function newParserCachePurgeJob( Title $title, array $parameters = [] ) {
		return new ParserCachePurgeJob( $title, $parameters );
	}

}

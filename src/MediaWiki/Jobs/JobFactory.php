<?php

namespace SMW\MediaWiki\Jobs;

use Title;
use RuntimeException;

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
	public function batchInsert( array $jobs ) {
		JobBase::batchInsert( $jobs );
	}

	/**
	 * @since 2.5
	 *
	 * @param string $type
	 * @param Title $title
	 * @param array $parameters
	 *
	 * @return Job
	 * @throws RuntimeException
	 */
	public function newByType( $type, Title $title, array $parameters = array() ) {

		switch ( $type ) {
			case 'SMW\RefreshJob':
				return $this->newRefreshJob( $title, $parameters );
			case 'SMW\UpdateJob':
				return $this->newUpdateJob( $title, $parameters );
			case 'SMW\UpdateDispatcherJob':
				return $this->newUpdateDispatcherJob( $title, $parameters );
			case 'SMW\ParserCachePurgeJob':
				return $this->newParserCachePurgeJob( $title, $parameters );
			case 'SMW\SearchTableUpdateJob':
				return $this->newSearchTableUpdateJob( $title, $parameters );
			case 'SMW\EntityIdDisposerJob':
				return $this->newEntityIdDisposerJob( $title, $parameters );
			case 'SMW\SequentialCachePurgeJob':
				return $this->newSequentialCachePurgeJob( $title, $parameters );
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
	public function newRefreshJob( Title $title, array $parameters = array() ) {
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
	public function newUpdateJob( Title $title, array $parameters = array() ) {
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
	public function newUpdateDispatcherJob( Title $title, array $parameters = array() ) {
		return new UpdateDispatcherJob( $title, $parameters );
	}

	/**
	 * @since 2.0
	 *
	 * @param Title $title
	 * @param array $parameters
	 *
	 * @return ParserCachePurgeJob
	 */
	public function newParserCachePurgeJob( Title $title, array $parameters = array() ) {
		return new ParserCachePurgeJob( $title, $parameters );
	}

	/**
	 * @since 2.5
	 *
	 * @param Title $title
	 * @param array $parameters
	 *
	 * @return SearchTableUpdateJob
	 */
	public function newSearchTableUpdateJob( Title $title, array $parameters = array() ) {
		return new SearchTableUpdateJob( $title, $parameters );
	}

	/**
	 * @since 2.5
	 *
	 * @param Title $title
	 * @param array $parameters
	 *
	 * @return EntityIdDisposerJob
	 */
	public function newEntityIdDisposerJob( Title $title, array $parameters = array() ) {
		return new EntityIdDisposerJob( $title, $parameters );
	}

	/**
	 * @since 2.5
	 *
	 * @param Title $title
	 * @param array $parameters
	 *
	 * @return SequentialCachePurgeJob
	 */
	public function newSequentialCachePurgeJob( Title $title, array $parameters = array() ) {
		return new SequentialCachePurgeJob( $title, $parameters );
	}

}

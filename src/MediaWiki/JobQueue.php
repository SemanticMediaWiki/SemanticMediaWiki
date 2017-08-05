<?php

namespace SMW\MediaWiki;

use JobQueueGroup;
use Job;

/**
 * MediaWiki's JobQueue contains mostly final methods making it difficult to use
 * an instance during tests hence this class provides a reduced interface with
 * mockable methods.
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class JobQueue {

	/**
	 * @var JobQueueGroup
	 */
	private $jobQueueGroup;

	/**
	 * @var boolean
	 */
	private $disableCache = false;

	/**
	 * @since 3.0
	 *
	 * @param JobQueueGroup $jobQueueGroup
	 */
	public function __construct( JobQueueGroup $jobQueueGroup ) {
		$this->jobQueueGroup = $jobQueueGroup;
	}

	/**
	 * @since 3.0
	 *
	 * @param boolean $disableCache
	 */
	public function disableCache( $disableCache = true ) {
		$this->disableCache = (bool)$disableCache;
	}

	/**
	 * @since 3.0
	 *
	 * @param string $type
	 *
	 * @return Job|boolean
	 */
	public function pop( $type ) {
		return $this->jobQueueGroup->get( $type )->pop();
	}

	/**
	 * Acknowledge that a job was completed
	 *
	 * @since 3.0
	 *
	 * @param Job $job
	 */
	public function ack( Job $job ) {
		$this->jobQueueGroup->get( $job->getType() )->ack( $job );
	}

	/**
	 * @since 3.0
	 *
	 * @param string $type
	 */
	public function delete( $type ) {

		$jobQueue = $this->jobQueueGroup->get( $type );
		$jobQueue->delete();

		if ( $this->disableCache ) {
			$jobQueue->flushCaches();
		}
	}

	/**
	 * @since 3.0
	 *
	 * @param Job|Job[] $jobs
	 */
	public function push( $jobs ) {
		$this->jobQueueGroup->push( $jobs );
	}

	/**
	 * @since 3.0
	 *
	 * @param Job|Job[] $jobs
	 */
	public function lazyPush( $jobs ) {

		if ( !method_exists( $this->jobQueueGroup, 'lazyPush' ) ) {
			return $this->push( $jobs );
		}

		$this->jobQueueGroup->lazyPush( $jobs );
	}

	/**
	 * @since 3.0
	 *
	 * @return array
	 */
	public function getQueueSizes() {
		return $this->jobQueueGroup->getQueueSizes();
	}

	/**
	 * @since 3.0
	 *
	 * @param string $type
	 *
	 * @return integer
	 */
	public function getQueueSize( $type ) {

		$jobQueue = $this->jobQueueGroup->get( $type );

		if ( $this->disableCache ) {
			$jobQueue->flushCaches();
		}

		return $jobQueue->getSize();
	}

	/**
	 * @since 3.0
	 *
	 * @param string $type
	 *
	 * @return boolean
	 */
	public function hasPendingJob( $type ) {
		return $this->getQueueSize( $type ) > 0;
	}

}

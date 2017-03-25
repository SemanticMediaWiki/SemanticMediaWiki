<?php

namespace SMW\MediaWiki\Jobs;

use Job;
use JobQueueGroup;
use SMW\Store;
use Title;

/**
 * @ingroup SMW
 *
 * @license GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
abstract class JobBase extends Job {

	/**
	 * @var boolean
	 */
	protected $isEnabledJobQueue = true;

	/**
	 * @var Job
	 */
	protected $jobs = array();

	/**
	 * @var Store
	 */
	protected $store = null;

	/**
	 * @since 2.1
	 *
	 * @param Store $store
	 */
	public function setStore( Store $store ) {
		$this->store = $store;
	}

	/**
	 * Whether to insert jobs into the JobQueue is enabled or not
	 *
	 * @since 1.9
	 *
	 * @param boolean|true $enableJobQueue
	 *
	 * @return JobBase
	 */
	public function isEnabledJobQueue( $enableJobQueue = true ) {
		$this->isEnabledJobQueue = (bool)$enableJobQueue;
		return $this;
	}

	/**
	 * @note Job::batchInsert was deprecated in MW 1.21
	 * JobQueueGroup::singleton()->push( $job );
	 *
	 * @since 1.9
	 */
	public function pushToJobQueue() {
		$this->isEnabledJobQueue ? self::batchInsert( $this->jobs ) : null;
	}

	/**
	 * @note Job::getType was introduced with MW 1.21
	 *
	 * @return string
	 */
	public function getType() {
		return $this->command;
	}

	/**
	 * @since  2.0
	 *
	 * @return integer
	 */
	public function getJobCount() {
		return count( $this->jobs );
	}

	/**
	 * @note Job::getTitle() in MW 1.19 does not exist
	 *
	 * @since  1.9
	 *
	 * @return Title
	 */
	public function getTitle() {
		return $this->title;
	}

	/**
	 * @since  1.9
	 *
	 * @param mixed $key
	 *
	 * @return boolean
	 */

	public function hasParameter( $key ) {

		if ( !is_array( $this->params ) ) {
			return false;
		}

		return isset( $this->params[$key] ) || array_key_exists( $key, $this->params );
	}

	/**
	 * @since  1.9
	 *
	 * @param mixed $key
	 *
	 * @return boolean
	 */
	public function getParameter( $key ) {
		return $this->hasParameter( $key ) ? $this->params[$key] : false;
	}

	/**
	 * @see https://gerrit.wikimedia.org/r/#/c/162009
	 *
	 * @param self[] $jobs
	 *
	 * @return boolean
	 */
	public static function batchInsert( $jobs ) {

		if ( class_exists( 'JobQueueGroup' ) ) {
			JobQueueGroup::singleton()->push( $jobs );
			return true;
		}

		return parent::batchInsert( $jobs );
	}

	/**
	 * @see Job::insert
	 */
	public function insert() {
		if ( $this->isEnabledJobQueue ) {
			return self::batchInsert( array( $this ) );
		}
	}

	/**
	 * @see JobQueueGroup::lazyPush
	 *
	 * @note Registered jobs are pushed using JobQueueGroup::pushLazyJobs at the
	 * end of MediaWiki::restInPeace
	 *
	 * @since 3.0
	 */
	public function lazyPush() {

		// MW 1.26+
		if ( $this->isEnabledJobQueue && method_exists( 'JobQueueGroup', 'lazyPush' ) ) {
			return JobQueueGroup::singleton()->lazyPush( $this );
		}

		$this->insert();
	}

	/**
	 * @since 2.5
	 *
	 * @return array
	 */
	public static function getQueueSizes() {

		if ( class_exists( 'JobQueueGroup' ) ) {
			return JobQueueGroup::singleton()->getQueueSizes();
		}

		return array();
	}

}

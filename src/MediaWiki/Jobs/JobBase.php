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
	protected $enabledJobQueue = true;

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
	public function setJobQueueEnabledState( $enableJobQueue = true ) {
		$this->enabledJobQueue = (bool)$enableJobQueue;
		return $this;
	}

	/**
	 * @note Job::batchInsert was deprecated in MW 1.21
	 * JobQueueGroup::singleton()->push( $job );
	 *
	 * @since 1.9
	 */
	public function pushToJobQueue() {
		$this->enabledJobQueue ? self::batchInsert( $this->jobs ) : null;
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
	 * Whether the parameters contain an element for a given key
	 *
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
	 * Returns a parameter value for a given key
	 *
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
	 */
	public static function batchInsert( $jobs ) {

		if ( class_exists( 'JobQueueGroup' ) ) {
			JobQueueGroup::singleton()->push( $jobs );
			return true;
		}

		return parent::batchInsert( $jobs );
	}

}

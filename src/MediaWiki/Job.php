<?php

namespace SMW\MediaWiki;

use Job as MediaWikiJob;
use MediaWiki\Title\Title;
use Psr\Log\LoggerAwareTrait;
use SMW\Services\ServicesFactory as ApplicationFactory;
use SMW\Site;
use SMW\Store;

/**
 * @ingroup SMW
 *
 * @license GPL-2.0-or-later
 * @since 1.9
 *
 * @author mwjames
 */
abstract class Job extends MediaWikiJob {

	use LoggerAwareTrait;

	protected bool $isEnabledJobQueue = true;

	protected JobQueue $jobQueue;

	/** @var array<int|string, mixed> */
	protected array $jobs = [];

	protected ?Store $store = null;

	/**
	 * @since 2.1
	 */
	public function setStore( Store $store ): void {
		$this->store = $store;
	}

	/**
	 * Whether to insert jobs into the JobQueue is enabled or not
	 *
	 * @since 1.9
	 */
	public function isEnabledJobQueue( bool $enableJobQueue = true ): static {
		$this->isEnabledJobQueue = $enableJobQueue;
		return $this;
	}

	/**
	 * @note Job::batchInsert was deprecated in MW 1.21
	 * JobQueueGroup::singleton()->push( $job ) was deprecated in MW 1.37;
	 * MediaWikiServices::getInstance()->getJobQueueGroup()
	 *
	 * @since 1.9
	 */
	public function pushToJobQueue(): void {
		$this->isEnabledJobQueue ? self::batchInsert( $this->jobs ) : null;
	}

	/**
	 * @note Job::getType was introduced with MW 1.21
	 */
	public function getType(): string {
		return $this->command;
	}

	/**
	 * @since  2.0
	 */
	public function getJobCount(): int {
		return count( $this->jobs );
	}

	/**
	 * @since  1.9
	 */
	public function hasParameter( string $key ): bool {
		if ( !is_array( $this->params ) ) {
			return false;
		}

		return isset( $this->params[$key] ) || array_key_exists( $key, $this->params );
	}

	/**
	 * @since  1.9
	 *
	 * @return bool
	 */
	public function getParameter( string $key, $default = false ) {
		return $this->hasParameter( $key ) ? $this->params[$key] : $default;
	}

	/**
	 * @since  3.0
	 */
	public function setParameter( string $key, mixed $value ): void {
		$this->params[$key] = $value;
	}

	/**
	 * @see https://gerrit.wikimedia.org/r/#/c/162009
	 */
	public static function batchInsert( array $jobs ): void {
		ApplicationFactory::getInstance()->getJobQueue()->push( $jobs );
	}

	/**
	 * @see Job::insert
	 */
	public function insert(): void {
		if ( $this->isEnabledJobQueue ) {
			self::batchInsert( [ $this ] );
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
	public function lazyPush(): void {
		if ( $this->isEnabledJobQueue ) {
			$this->getJobQueue()->lazyPush( $this );
		}
	}

	/**
	 * @see Translate::TTMServerMessageUpdateJob
	 * @since 3.0
	 */
	public function setDelay( int $delay ): void {
		$isDelayedJobsEnabled = $this->getJobQueue()->isDelayedJobsEnabled(
			$this->getType()
		);

		if ( !$delay || !$isDelayedJobsEnabled ) {
			return;
		}

		$oldTime = $this->getReleaseTimestamp();
		$newTime = time() + $delay;

		if ( $oldTime !== null && $oldTime >= $newTime ) {
			return;
		}

		$this->params['jobReleaseTimestamp'] = $newTime;
	}

	/**
	 * @see Job::newRootJobParams
	 * @since 3.0
	 *
	 * @param string $key
	 * @param string|Title $title
	 */
	public static function newRootJobParams( $key = '', $title = '' ) {
		if ( $title instanceof Title ) {
			$title = $title->getPrefixedDBkey();
		}

		return parent::newRootJobParams( "job:{$key}:root:{$title}" );
	}

	/**
	 * @see Job::ignoreDuplicates
	 * @since 3.0
	 */
	public function ignoreDuplicates(): bool {
		if ( isset( $this->params['waitOnCommandLine'] ) ) {
			return $this->params['waitOnCommandLine'] > 1;
		}

		return $this->removeDuplicates;
	}

	/**
	 * Only run the job via commandLine or the cronJob and avoid execution via
	 * Special:RunJobs as it can cause the script to timeout.
	 */
	public function waitOnCommandLineMode(): bool {
		if ( !$this->hasParameter( 'waitOnCommandLine' ) || Site::isCommandLineMode() ) {
			return false;
		}

		if ( $this->hasParameter( 'waitOnCommandLine' ) ) {
			$this->params['waitOnCommandLine'] = $this->getParameter( 'waitOnCommandLine' ) + 1;
		} else {
			$this->params['waitOnCommandLine'] = 1;
		}

		$job = new static( $this->title, $this->params );
		$job->insert();

		return true;
	}

	protected function getJobQueue() {
		if ( $this->jobQueue === null ) {
			$this->jobQueue = ApplicationFactory::getInstance()->getJobQueue();
		}

		return $this->jobQueue;
	}

}

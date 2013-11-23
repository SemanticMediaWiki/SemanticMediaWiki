<?php

namespace SMW;

use Job;
use Title;

/**
 * Dispatcher class to invoke UpdateJob's
 *
 * Can be run either in deferred or immediate mode to restore the data parity
 * between a property and its attached subjects
 *
 * @ingroup SMW
 *
 * @licence GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class UpdateDispatcherJob extends JobBase {

	/** $var Job */
	protected $jobs = array();

	/** $var boolean */
	protected $enabled = true;

	/**
	 * @since  1.9
	 *
	 * @param Title $title
	 * @param array $params job parameters
	 * @param integer $id job id
	 */
	public function __construct( Title $title, $params = array(), $id = 0 ) {
		parent::__construct( 'SMW\UpdateDispatcherJob', $title, $params, $id );
		$this->removeDuplicates = true;
	}

	/**
	 * Disables ability to insert jobs into the
	 * JobQueue
	 *
	 * @since 1.9
	 *
	 * @return PropertySubjectsUpdateDispatcherJob
	 */
	public function disable() {
		$this->enabled = false;
		return $this;
	}

	/**
	 * @see Job::run
	 *
	 * @since  1.9
	 *
	 * @return boolean
	 */
	public function run() {
		Profiler::In( __METHOD__, true );

		if ( $this->getTitle()->getNamespace() === SMW_NS_PROPERTY ) {
			$this->distribute( DIProperty::newFromUserLabel( $this->getTitle()->getText() ) )->push();
		}

		Profiler::Out( __METHOD__, true );
		return true;
	}

	/**
	 * Insert batch jobs
	 *
	 * @note Job::batchInsert was deprecated in MW 1.21
	 * JobQueueGroup::singleton()->push( $job );
	 *
	 * @since 1.9
	 */
	public function push() {
		$this->enabled ? Job::batchInsert( $this->jobs ) : null;
	}

	/**
	 * Generates list of involved subjects
	 *
	 * @since 1.9
	 *
	 * @param DIProperty $property
	 */
	protected function distribute( DIProperty $property ) {
		Profiler::In( __METHOD__, true );

		/**
		 * @var Store $store
		 */
		$store = $this->withContext()->getStore();

		// Array of all subjects that have some value for the given property
		$subjects = $store->getAllPropertySubjects( $property );

		$this->addJobs( $subjects );

		// Hook deprecated with SMW 1.9 and will vanish with SMW 1.11
		wfRunHooks( 'smwUpdatePropertySubjects', array( &$this->jobs ) );

		// Hook since 1.9
		wfRunHooks( 'SMW::Dispatcher::updateJobs', array( $property, &$this->jobs ) );

		// Fetch all those that have an error property attached and
		// re-run it through the job-queue
		$subjects = $store->getPropertySubjects(
			new DIProperty( DIProperty::TYPE_ERROR ),
			DIWikiPage::newFromTitle( $this->title )
		);

		$this->addJobs( $subjects );

		Profiler::Out( __METHOD__, true );
		return $this;
	}

	/**
	 * Helper method to iterate over an array of DIWikiPage and return and
	 * array of UpdateJobs
	 *
	 * Check whether a job with the same getPrefixedDBkey string (prefixed title,
	 * with underscores and any interwiki and namespace prefixes) is already
	 * registered and if so don't insert a new job. This is particular important
	 * for pages that include a large amount of subobjects where the same Title
	 * and ParserOutput object is used (subobjects are included using the same
	 * WikiPage which means the resulting ParserOutput object is the same)
	 *
	 * @since 1.9
	 *
	 * @param DIWikiPage[] $subjects
	 */
	protected function addJobs( array $subjects = array() ) {

		foreach ( $subjects as $subject ) {

			$duplicate = false;
			$title     = $subject->getTitle();

			if ( $title instanceof Title ) {

				// Avoid duplicates by comparing the title DBkey
				foreach ( $this->jobs as $job ) {
					if ( $job->getTitle()->getPrefixedDBkey() === $title->getPrefixedDBkey() ){
						$duplicate = true;
						break;
					}
				}

				if ( !$duplicate ) {
					$this->jobs[] = new UpdateJob( $title );
				}
			}
		}
	}

	/**
	 * @see Job::insert
	 *
	 * @since 1.9
	 * @codeCoverageIgnore
	 */
	public function insert() {
		if ( $this->withContext()->getSettings()->get( 'smwgEnableUpdateJobs' ) ) {
			parent::insert();
		}
	}
}

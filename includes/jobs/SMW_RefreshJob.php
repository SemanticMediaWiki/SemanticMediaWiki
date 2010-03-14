<?php
/**
 * @file
 * @ingroup SMW
 */

/**
 * SMWRefreshJob iterates over all page ids of the wiki, to perform an update
 * action for all of them in sequence. This corresponds to the in-wiki version of
 * the SMW_refreshData.php script for updating the whole wiki, but it also fixes
 * problems with SMWSQLStore2 (which may have objects in its database that are not
 * proper wiki pages).
 *
 * @note this class ignores $smwgEnableUpdateJobs and always creates updates. In
 * fact, it might be needed specifically on wikis that do not use update jobs in
 * normal operation.
 *
 * @author Markus Krötzsch
 * @ingroup SMW
 */
class SMWRefreshJob extends Job {

	/**
	 * The parameters optionally specified in the second argument of this constructor
	 * use the following array keys:  'spos' (start index, default 1), 'prog' (progress
	 * indicator, default 0), 'rc' (number of runs to be done, default 1). If more than
	 * one run is done, then the first run will restrict to properties and types.
	 * The progress indication refers to the current run, not to the overall job.
	 */
	function __construct( $title, $params = array( 'spos' => 1, 'prog' => 0, 'rc' => 1 ) ) {
		parent::__construct( 'SMWRefreshJob', $title, $params );
	}

	/**
	 * Run job
	 * @return boolean success
	 */
	function run() {
		wfProfileIn( 'SMWRefreshJob::run (SMW)' );
		if ( !array_key_exists( 'spos', $this->params ) ) {
			return true;
			wfProfileOut( 'SMWRefreshJob::run (SMW)' );
		}
		$run = array_key_exists( 'run', $this->params ) ? $this->params['run']:1;
		$spos = $this->params['spos'];
		$store = smwfGetStore();
		$namespaces = ( ( $this->params['rc'] > 1 ) && ( $run == 1 ) ) ? array( SMW_NS_PROPERTY, SMW_NS_TYPE ):false;
		$progress = smwfGetStore()->refreshData( $spos, 20, $namespaces );

		if ( $spos > 0 ) {
			$nextjob = new SMWRefreshJob( $this->title, array( 'spos' => $spos, 'prog' => $progress, 'rc' => $this->params['rc'], 'run' => $run ) );
			$nextjob->insert();
		} elseif ( $this->params['rc'] > $run ) { // do another run from the beginning
			$nextjob = new SMWRefreshJob( $this->title, array( 'spos' => 1, 'prog' => 0, 'rc' => $this->params['rc'], 'run' => $run + 1 ) );
			$nextjob->insert();
		}
		wfProfileOut( 'SMWRefreshJob::run (SMW)' );
		return true;
	}

	/**
	 * Report the estimated progress status of this job as a number between 0 and 1 (0% to 100%).
	 * The progress refers to the state before processing this job.
	 */
	public function getProgress() {
		$prog = array_key_exists( 'prog', $this->params ) ? $this->params['prog']:0;
		$run = array_key_exists( 'run', $this->params ) ? $this->params['run']:1;
		return ( $run - 1 + $prog ) / $this->params['rc'];
	}
}

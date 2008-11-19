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

	function __construct($title, $params = array('spos'=>1, 'prog'=>0) ) {
		parent::__construct( 'SMWRefreshJob', $title, $params);
	}

	/**
	 * Run job
	 * @return boolean success
	 */
	function run() {
		wfProfileIn('SMWRefreshJob::run (SMW)');
		if (!array_key_exists('spos',$this->params)) {
			return true;
			wfProfileOut('SMWRefreshJob::run (SMW)');
		}
		$spos = $this->params['spos'];
		$store = smwfGetStore();
		$progress = smwfGetStore()->refreshData($spos, 20);

		if ($spos > 0) {
			$nextjob = new SMWRefreshJob($this->title, array('spos' => $spos, 'prog' => $progress));
			$nextjob->insert();
		}
		wfProfileOut('SMWRefreshJob::run (SMW)');
		return true;
	}

	/**
	 * Report the estimated progress status of this job as a number between 0 and 1 (0% to 100%).
	 * The progress refers to the state before processing this job.
	 */
	public function getProgress() {
		return array_key_exists('prog',$this->params)?$this->params['prog']:0;
	}
}

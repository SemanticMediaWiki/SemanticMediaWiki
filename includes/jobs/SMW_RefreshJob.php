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

	function __construct($title, $params = array('spos'=>1) ) {
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
		$batchsize = 20; // twice this many jobs may be created each time, if SQLStore2 is used
		$store = smwfGetStore();
		$updatejobs = array();
		$emptyrange = true; // was nothing found in this run?

		// update by MediaWiki page id --> make sure we get all pages
		$tids = array();
		for ($i = $spos; $i < $spos + $batchsize; $i++) { // array of ids
			$tids[] = $i;
		}
		$titles = Title::newFromIDs($tids);
		foreach ($titles as $title) {
			$updatejobs[] = new SMWUpdateJob($title);
			$emptyrange = false;
		}

		// update by internal SMW id --> make sure we get all objects in SMW
		if ($store instanceof SMWSQLStore2) {
			$db =& wfGetDB( DB_SLAVE );
			$res = $db->select('smw_ids', array('smw_id', 'smw_title','smw_namespace','smw_iw'),
			                   "smw_id >= $spos AND smw_id < " . $db->addQuotes($spos+$batchsize), __METHOD__);
			foreach ($res as $row) {
				$emptyrange = false; // note this even if no jobs were created
				if ( ($row->smw_iw == '') || ($row->smw_iw == SMW_SQL2_SMWREDIIW) ) {
					// TODO: special treament of redirects needed, since the store will not act on redirects that did not change according to its records
					$title = Title::makeTitle($row->smw_namespace, $row->smw_title);
					if ( !$title->exists() ) {
						$updatejobs[] = new SMWUpdateJob($title);
					}
				} elseif ($row->smw_iw != SMW_SQL2_SMWIW) { // refresh all "normal" interwiki pages
					$dv = SMWDataValueFactory::newTypeIDValue('_wpg');
					$dv->setValues($row->smw_namespace, $row->smw_title, false, $row->smw_iw);
					$store->deleteSemanticData($dv);
				}
			}
			$db->freeResult($res);
		}
		Job::batchInsert($updatejobs);

		$nextpos = $spos + $batchsize;
		if ($emptyrange) {
			$db =& wfGetDB( DB_SLAVE );
			// check if there will be more pages later on
			$res = $db->selectField('page', 'page_id', "page_id >= $nextpos", __METHOD__, array('ORDER BY' => "page_id ASC"));
			if ($store instanceof SMWSQLStore2) {
				$res2 = $db->selectField('smw_ids', 'smw_id', "smw_id >= $nextpos", __METHOD__, array('ORDER BY' => "smw_id ASC"));
				if ( ($res2 != 0) && ($res2<$res) ) {
					$res = $res2;
				}
			}
			$nextpos = $res;
		}
		if ($nextpos != 0) {
			$nextjob = new SMWRefreshJob($this->title, array('spos' => $nextpos));
			$nextjob->insert();
		}
		wfProfileOut('SMWRefreshJob::run (SMW)');
		return true;
	}
}

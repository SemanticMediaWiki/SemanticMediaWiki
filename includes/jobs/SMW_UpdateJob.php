<?php
/**
 * File containing SMWUpdateJob.
 *
 * @author Daniel M. Herzig
 * @author Markus Krötzsch
 * @file
 * @ingroup SMW
 */

/**
 * SMWUpdateJob updates the semantic data in the database for a given title 
 * using the MediaWiki JobQueue. Update jobs are created if, when saving an article,
 * it is detected that the content of other pages must be re-parsed as well (e.g. 
 * due to some type change).
 *
 * @note This job does not update the page display or parser cache, so in general
 * it might happen that part of the wiki page still displays based on old data (e.g.
 * formatting in-page values based on a datatype thathas since been changed), whereas
 * the Factbox and query/browsing interfaces might already show the updatd records.
 *
 * @ingroup SMW
 */
class SMWUpdateJob extends Job {

	function __construct(Title $title) {
		parent::__construct( 'SMWUpdateJob', $title);
	}

	/**
	 * Run job
	 * @return boolean success
	 */
	function run() {
		wfProfileIn('SMWUpdateJob::run (SMW)');
		global $wgParser, $smwgHeadItems;

		$linkCache =& LinkCache::singleton();
		$linkCache->clear();

		if ( is_null( $this->title ) ) {
			$this->error = "SMWUpdateJob: Invalid title";
			wfProfileOut('SMWUpdateJob::run (SMW)');
			return false;
		} elseif (!$this->title->exists()) {
			smwfGetStore()->deleteSubject($this->title); // be sure to clear the data
			wfProfileOut('SMWUpdateJob::run (SMW)');
			return true;
		}

		$revision = Revision::newFromTitle( $this->title );
		if ( !$revision ) {
			$this->error = 'SMWUpdateJob: Article not found "' . $this->title->getPrefixedDBkey() . '"';
			wfProfileOut('SMWUpdateJob::run (SMW)');
			return false;
		}

		wfProfileIn( __METHOD__.'-parse' );
		$options = new ParserOptions;
		$output = $wgParser->parse($revision->getText(), $this->title, $options, true, true, $revision->getID());

		wfProfileOut( __METHOD__.'-parse' );
		wfProfileIn( __METHOD__.'-update' );

		SMWParseData::storeData($output, $this->title, false);
		wfProfileOut( __METHOD__.'-update' );
		wfProfileOut('SMWUpdateJob::run (SMW)');
		return true;
	}

	/**
	 * This actually files the job. This is prevented if the configuration of SMW
	 * disables jobs.
	 * NOTE: Any method that inserts jobs with Job::batchInsert or otherwise must
	 * implement this check individually. The below is not called in these cases.
	 */
	function insert() {
		global $smwgEnableUpdateJobs;
		if ($smwgEnableUpdateJobs) {
			parent::insert();
		}
	}
}

<?php
/**
 * SMWUpdateJob updates the semantic data in the database for a given title 
 * using the MediaWiki JobQueue.
 * Update jobs are created if, when saving an article, it is detected that the
 * content of other pages must be re-parsed as well (e.g. due to some type change).
 *
 * @author Daniel M. Herzig
 * @file
 * @ingroup SMW
 */

class SMWUpdateJob extends Job {

	function __construct($title) {
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
		/// FIXME: we do not care about the parser cache here, and additional information such as the header scripts
		/// that the above parsing might have created is simply discarded. This yields trouble: if some datatype changes
		/// such that it now requires a stylesheet to display, then the parsercache will not be aware of this and hence
		/// the header item will be missing!
		/// Besides this problem, the architecture since SMW 1.4 should at least ensure that no other globals are used 
		/// to pass around data *over long distances* and the above call thus should not disturb any other data.

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

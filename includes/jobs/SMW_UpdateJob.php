<?php
/**
 * SMWUpdateJob updates the semantic data in the database for a given title 
 * using the MediaWiki JobQueue.
 * Update jobs are created if, when saving an article, it is detected that the
 * content of other pages must be re-parsed as well (e.g. due to some type change).
 * 
 * @author Daniel M. Herzig
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
		global $wgParser, $smwgHeadItems, $smwgConceptText;

		$linkCache =& LinkCache::singleton();
		$linkCache->clear();

		if ( is_null( $this->title ) ) {
			$this->error = "SMWUpdateJob: Invalid title";
			wfProfileOut('SMWUpdateJob::run (SMW)');
			return false;
		}

		$revision = Revision::newFromTitle( $this->title );
		if ( !$revision ) {
			$this->error = 'SMWUpdateJob: Article not found "' . $this->title->getPrefixedDBkey() . '"';
			wfProfileOut('SMWUpdateJob::run (SMW)');
			return false;
		}

		wfProfileIn( __METHOD__.'-parse' );
		$options = new ParserOptions;
		//$parserOutput = $wgParser->parse( $revision->getText(), $this->title, $options, true, true, $revision->getId() );

		/// NOTE: subparses will purge/mess up our globals; every such global would require similar handling here
		/// (semdata anyone?!); this is all rather nasty and needs a unified architecture (e.g. one object to
		/// manage/copy/restore all SMW globals). The best solution would be to have current globals moved into
		/// parser member variables, so that other parsers do not affect one parser's data.
		$cur_headitems = $smwgHeadItems;
		$cur_conctext = $smwgConceptText;
		$smwgHeadItems = array();
		$wgParser->parse($revision->getText(), $this->title, $options, true, true, $revision->getID());
		$smwgHeadItems = $cur_headitems;
		$smwgConceptText = $cur_conctext;

		wfProfileOut( __METHOD__.'-parse' );
		wfProfileIn( __METHOD__.'-update' );

		SMWFactbox::storeData(true); /// FIXME: why is this just fixed to "true"?
		wfProfileOut( __METHOD__.'-update' );
		wfProfileOut('SMWUpdateJob::run (SMW)');
		return true;
	}

	/**
	 * This actually files the job. This is prevented if the configuration of SMW
	 * disables jobs.
	 */
	function insert() {
		global $smwgEnableUpdateJobs;
		if ($smwgEnableUpdateJobs) {
			parent::insert();
		}
	}
}

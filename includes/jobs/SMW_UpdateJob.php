<?php
/**
 * SMWUpdateJob updates the semantic data in the database for a given title 
 * using the MediaWiki JobQueue.
 * Update jobs are created if, when saving an article, it is detected that the
 * content of other pages must be re-parsed as well (e.g. due to some type change).
 * 
 * @author Daniel M. Herzig
 */

if ( !defined( 'MEDIAWIKI' ) ) {
  die( "This file is part of the Semantic MediaWiki extension. It is not a valid entry point.\n" );
}

global $IP;
require_once( $IP . "/includes/JobQueue.php" );

class SMWUpdateJob extends Job {

	function __construct($title) {
		wfDebug(__METHOD__." SMWUpdateJob " . $title->getText() . " \r\n");
		parent::__construct( 'SMWUpdateJob', $title);
	}

	/**
	 * Run job
	 * @return boolean success
	 */
	function run() {
		wfDebug(__METHOD__);
		global $wgParser;
		wfProfileIn( __METHOD__ );

		$linkCache =& LinkCache::singleton();
		$linkCache->clear();

		if ( is_null( $this->title ) ) {
			$this->error = "SMWUpdateJob: Invalid title";
			wfDebug($this->error);
			wfProfileOut( __METHOD__ );
			return false;
		}

		$revision = Revision::newFromTitle( $this->title );
		if ( !$revision ) {
			$this->error = 'SMWUpdateJob: Article not found "' . $this->title->getPrefixedDBkey() . '"';
			wfDebug($this->error);
			wfProfileOut( __METHOD__ );
			return false;
		}

		wfProfileIn( __METHOD__.'-parse' );
		$options = new ParserOptions;
		//$parserOutput = $wgParser->parse( $revision->getText(), $this->title, $options, true, true, $revision->getId() );
		$wgParser->parse($revision->getText(), $this->title, $options, true, true, $revision->getID());

		wfProfileOut( __METHOD__.'-parse' );
		wfProfileIn( __METHOD__.'-update' );

		SMWFactbox::storeData(true); /// FIXME: why is this always true?
		wfDebug("SMWUpdateJob done for ".$this->title->getText()."\r\n");	
		wfProfileOut( __METHOD__.'-update' );
		return true;
	}
}

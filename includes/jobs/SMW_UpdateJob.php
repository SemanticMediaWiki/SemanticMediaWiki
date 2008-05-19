<?php
/*
 * SMW_UpdateJob
 * Updates the semantic data in the database for a given title using the
 * MediaWiki JobQueue.
 * Triggered in the smwfSaveHook.
 * 
 * @author Daniel M. Herzig
 */

if ( !defined( 'MEDIAWIKI' ) ) {
  die( "This file is part of the SemanticMediaWiki Extension. It is not a valid entry point.\n" );
}

global $IP;
require_once( $IP."/includes/JobQueue.php" );

class SMW_UpdateJob extends Job {
	
	function __construct($title) {
		wfDebug(__METHOD__." SMW_UpdateJob ".$title->getText()." \r\n");
		parent::__construct( 'SMW_UpdateJob', $title);
	}

	/**
	 * Run a SMW_SemanticUpdate job
	 * @return boolean success
	 */
	function run() {
		wfDebug(__METHOD__);
		global $wgParser;
		wfProfileIn( __METHOD__ );
	
		$linkCache =& LinkCache::singleton();
		$linkCache->clear();
		
		if ( is_null( $this->title ) ) {
			$this->error = "SMW_UpdateJob: Invalid title";
			wfDebug($this->error);
			wfProfileOut( __METHOD__ );
			return false;
		}
		
		$revision = Revision::newFromTitle( $this->title );
		if ( !$revision ) {
			$this->error = 'SMW_UpdateJob: Article not found "' . $this->title->getPrefixedDBkey() . '"';
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
		
		SMWFactbox::storeData($this->title, true);
		wfDebug("SMW_UpdateJob done for ".$this->title->getText()."\r\n");	
		wfProfileOut( __METHOD__.'-update' );
		return true;
	}
}

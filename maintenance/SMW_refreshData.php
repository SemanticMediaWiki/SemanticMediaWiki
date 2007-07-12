<?php
/**
 * Recreates all the semantic data in the database, by cycling through all
 * the pages that might have semantic data, and calling functions that
 * re-save semantic data for each one.
 *
 * Note: this file must be placed in MediaWiki's "maintenance" directory!
 *
 * Usage:
 * php SMW_refreshData.php [options...]
 *
 * -d <delay>   Wait for this many milliseconds after processing an article, useful for limiting server load.
 * -v           Be verbose about the progress.
 *
 * @author Yaron Koren
 * @author Markus Krötzsch
 */

$optionsWithArgs = array( 'd' ); // -d <delay>

require_once( 'commandLine.inc' );

global $smwgIP;
require_once($smwgIP . '/includes/SMW_Factbox.php');

if ( array_key_exists( 'd', $options ) ) {
	$delay = intval($options['d']) * 100000; // sleep 100 times the given time, but do so only each 100 pages
} else {
	$delay = false;
}

if (  array_key_exists( 'v', $options ) ) {
	$verbose = true;
} else {
	$verbose = false;
}

global $wgParser;

$dbr =& wfGetDB( DB_MASTER );
$start = 0;
$end = $dbr->selectField( 'page', 'max(page_id)', false, 'SMW_refreshData' );

print "Refreshing all semantic data in the database!\n";
print "Processing pages from ID $start to ID $end ...\n";

$num_files = 0;
$options = new ParserOptions();

for ($id = $start; $id <= $end; $id++) {
	$title = Title::newFromID($id);
	if ( ($title === NULL) ) continue;
	if ($verbose) {
		print "($num_files) Processing page with ID " . $id . " ...\n";
	}
	if ( smwfIsSemanticsProcessed($title->getNamespace()) ) {
		$revision = Revision::newFromTitle( $title );
		if ( $revision === NULL ) continue;
		$wgParser->parse($revision->getText(), $title, $options, true, true, $revision->getID());
		SMWFactbox::storeData($title, true);
		// sleep to be nice to the server
		if ( ($delay !== false) && (($num_files+1) % 100 === 0) ) {
			usleep($delay);
		}
	} else {
		smwfGetStore()->deleteSubject($title);
		// sleep to be nice to the server 
		// (for this case, sleep only every 1000 pages, so that large chunks of e.g. messages are processed more quickly)
		if ( ($delay !== false) && (($num_files+1) % 1000 === 0) ) {
			usleep($delay);
		}
	}
	$num_files++;
}

print "$num_files pages refreshed.\n";



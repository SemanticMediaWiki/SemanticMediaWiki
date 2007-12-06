<?php
/**
 * Announces selected OWL/RDF-files of the wiki to http://pingthesemanticweb.com.
 *
 * Note: this file must be placed in MediaWiki's "maintenance" directory!
 *
 * Usage:
 * php SMW_pingSemWeb.php [options...]
 *
 * -d <delay>   Wait for this many milliseconds after processing an article, useful for limiting server load.
 * -s <startid> Start refreshing at given article ID, useful for partial refreshing
 * -e <endid>   Stop refreshing at given article ID, useful for partial refreshing 
 * -v           Be verbose about the progress.
 *
 * @author Markus Krötzsch
 */

$optionsWithArgs = array( 'd', 's', 'e' ); // -d <delay>, -s <startid>

require_once( 'commandLine.inc' );

global $smwgIP;
// require_once($smwgIP . '/includes/SMW_Factbox.php');
include_once($smwgIP . '/includes/SMW_Infolink.php');

$dbr =& wfGetDB( DB_MASTER );

if ( array_key_exists( 'd', $options ) ) {
	$delay = intval($options['d']) * 100000; // sleep 100 times the given time, but do so only each 100 pages
} else {
	$delay = false;
}

if ( array_key_exists( 's', $options ) ) {
	$start = intval($options['s']);
} else {
	$start = 0;
}
$end = $dbr->selectField( 'page', 'max(page_id)', false, 'SMW_refreshData' );
if ( array_key_exists( 'e', $options ) ) {
	$end = min(intval($options['e']), $end);
}

if (  array_key_exists( 'v', $options ) ) {
	$verbose = true;
} else {
	$verbose = false;
}

$linkCache =& LinkCache::singleton();
global $wgUser, $wgServer;
if ($wgServer == 'http://localhost' ) {
	print "Sorry, you cannot publicly announce a local wiki. Aborting.\n";
	die;
}

print "Refreshing all semantic data in the database!\n";
print "Processing pages from ID $start to ID $end ...\n";

$num_files = 0;

$skin = $wgUser->getSkin();
for ($id = $start; $id <= $end; $id++) {
	$title = Title::newFromID($id);
	if ( ($title === NULL) ) continue;
	$url = $wgServer . $skin->makeSpecialUrl( 'ExportRDF/' . $title->getPrefixedText() );
	if ($verbose) {
		print "($num_files) Processing page with ID " . $id . " ($url).\n";
	}
	print " Pinging http://pingthesemanticweb.com/rest/?url=" . rawurlencode($url) . "...";
	$fp = fsockopen('pingthesemanticweb.com/rest/?url=' . rawurlencode($url));
	if (!$fp) {
		print " failed.\n";
	} else {
		fclose($fp);
		print " done.\n";
	}

// 		$revision = Revision::newFromTitle( $title );
// 		if ( $revision === NULL ) continue;
// 		$wgParser->parse($revision->getText(), $title, $options, true, true, $revision->getID());
// 		SMWFactbox::storeData($title, true);

	// sleep to be nice to the server
	if ( ($delay !== false) && (($num_files+1) % 100 === 0) ) {
		usleep($delay);
	}
	$num_files++;
	$linkCache->clear(); // avoid memory leaks
}

print "$num_files pages pinged.\n";



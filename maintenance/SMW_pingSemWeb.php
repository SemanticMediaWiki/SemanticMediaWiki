<?php
/**
 * Announces selected OWL/RDF-files of the wiki to http://pingthesemanticweb.com.
 * See http://pingthesemanticweb.com/about.php for the rational behind this.
 * This script may be extended to other such services if desired.
 *
 * Note: this file must be placed in MediaWiki's "maintenance" directory!
 *
 * Usage:
 * php SMW_pingSemWeb.php [options...]
 *
 * -d <delay>   Wait for this many milliseconds after processing an article, useful for limiting server load.
 * -s <startid> Start refreshing at given article ID, useful for partial refreshing
 * -e <endid>   Stop refreshing at given article ID, useful for partial refreshing 
 * -q           Be quite (no output).
 * -h <server>  Define the server (without sub-path, e.g. "http://example.org" even for "http://example.org/wiki"
 *              Use "-" for reusing the server name given to "enableSemantics()" when loading SMW. If omitted,
 *              the value of $wgServer is used, though this may be localhost in many cases.
 *
 * @author Markus Krötzsch
 * @TODO it should be possible to ping based on pages' modification dates
 */

$optionsWithArgs = array( 'd', 's', 'e', 'h' ); // -d <delay>, -s <startid>, -e <endid>

require_once( 'commandLine.inc' );

global $smwgIP, $wgServer;
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

if (  array_key_exists( 'q', $options ) ) {
	$verbose = false;
} else {
	$verbose = true;
}

if ( array_key_exists( 'h', $options ) ) {
	$server = $options['h'];
} else {
	$server = $wgServer;
}

if ( ($server == 'http://localhost') || ($server == '') || ($server == '-') ) {
	global $smwgNamespace;
	$server = $smwgNamespace;
	if ($server[0] == '.') {
		$resolver = Title::makeTitle( NS_SPECIAL, 'URIResolver');
		$server = "http://" . mb_substr($server, 1);
	}
	if ($verbose) print "Trying to construct ping URL with use server parameter given to SMW:\n  $server\nAlternatively, you can specify a publicly reachable server via the parameter -h with this script.\n\n";
}

$linkCache =& LinkCache::singleton();
global $wgUser;

if ($verbose) {
	print "Notifying pingthesemanticweb.com of all semantic data in this wiki!\n\n";
	print "Processing pages from ID $start to ID $end ...\n";
}

$num_files = 0;

$skin = $wgUser->getSkin();
for ($id = $start; $id <= $end; $id++) {
	$title = Title::newFromID($id);
	if ( ($title === NULL) ) continue;
	if ( !smwfIsSemanticsProcessed($title->getNamespace()) ) continue;
	$url = $server . $skin->makeSpecialUrl( 'ExportRDF/' . $title->getPrefixedText() );
	if ($verbose) {
		print "($num_files) Processing page with ID " . $id . " ($url).\n";
		print ' Pinging http://pingthesemanticweb.com/rest/?url=' . rawurlencode($url) . ' ...';
	}
	$fp = fopen('http://pingthesemanticweb.com/rest/?url=' . rawurlencode($url), 'r');
	if ($fp === false) {
		if ($verbose) print " failed.\n";
	} else {
		fclose($fp);
		if ($verbose) print " done.\n";
	}

	// sleep to be nice to the server
	if ( ($delay !== false) && (($num_files+1) % 100 === 0) ) {
		usleep($delay);
	}
	$num_files++;
	$linkCache->clear(); // avoid memory leaks
}

if ($verbose) print "$num_files pages pinged.\n";



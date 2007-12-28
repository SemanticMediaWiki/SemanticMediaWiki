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
 * -s <startid> Start refreshing at given article ID, useful for partial refreshing
 * -e <endid>   Stop refreshing at given article ID, useful for partial refreshing 
 * -v           Be verbose about the progress.
 * -c           Will refresh only category pages (and other explicitly named namespaces)
 * -p           Will refresh only property pages (and other explicitly named namespaces)
 * -t           Will refresh only type pages (and other explicitly named namespaces)
 * -f           Fully delete all content instead of just refreshing relevant entries. This will also
 *              rebuild the whole storage structure. May leave the wiki temporarily incomplete.
 *
 * @author Yaron Koren
 * @author Markus Krötzsch
 */

$optionsWithArgs = array( 'd', 's', 'e' ); // -d <delay>, -s <startid>

require_once('counter.php');
require_once('commandLine.inc');

global $smwgIP;
require_once($smwgIP . '/includes/SMW_Factbox.php');

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

$filter = false;
$categories = false;
$properties = false;
$types = false;
$articles = false;

if (  array_key_exists( 'c', $options ) ) {
	$filter = true;
	$categories = true;
}
if (  array_key_exists( 'p', $options ) ) {
	$filter = true;
	$properties = true;
}
if (  array_key_exists( 't', $options ) ) {
	$filter = true;
	$types = true;
}

if (  array_key_exists( 'f', $options ) ) {
	print "\n  Deleting all stored data completely and rebuilding it again later!\n  Semantic data in the wiki might be incomplete for some time while this operation runs.\n\n  NOTE: It is usually necessary to run this script ONE MORE TIME after this operation,\n  since some properties' types are not stored yet in the first run.\n  The first run can normally use the parameter -p to refresh only properties.\n\n";
	if ( (array_key_exists( 's', $options ))  || (array_key_exists( 'e', $options )) ) {
		print "  WARNING: -s or -e are used, so some pages will not be refreshed at all!\n    Data for those pages will only be available again when they have been\n    refreshed as well!\n\n";
	}

	print "Abort with control-c in the next five seconds ...  ";

	for ($i = 6; $i >= 1;) {
		print_c($i, --$i);
		sleep(1);
	}
	echo "\n";
	smwfGetStore()->drop($verbose);
	print "\n";
	smwfGetStore()->setup($verbose);
	while (ob_get_level() > 0) { // be sure to have some buffer, otherwise some PHPs complain
		ob_end_flush();
	}
	echo "\nAll storage structures have been deleted and recreated.\n\n";
}

global $wgParser;

print "Refreshing all semantic data in the database!\n";
print "Processing pages from ID $start to ID $end ...\n";

$num_files = 0;
$options = new ParserOptions();

$linkCache =& LinkCache::singleton();
for ($id = $start; $id <= $end; $id++) {
	$title = Title::newFromID($id);
	if ( ($title === NULL) ) continue;
	if ($filter) {
		$ns = $title->getNamespace();
		$doit = false;
		if (($categories) && ($ns == NS_CATEGORY))
			$doit = true;
		if (($properties) && ($ns == SMW_NS_PROPERTY))
			$doit = true;
		if (($types) && ($ns == SMW_NS_TYPE))
			$doit = true;
		if (!$doit) continue;
	}
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
	$linkCache->clear(); // avoid memory leaks
}

print "$num_files pages refreshed.\n";



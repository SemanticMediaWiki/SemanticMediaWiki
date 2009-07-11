<?php
/**
 * Manage SMW concept caches, as supplied by SMWSQLStore2.
 * Use option --help for usage details.
 *
 * Note: if SMW is not installed in its standard path under ./extensions
 *       then the MW_INSTALL_PATH environment variable must be set.
 *       See README in the maintenance directory.
 *
 * @author Markus Krötzsch
 * @file
 * @ingroup SMWMaintenance
 */

$optionsWithArgs = array( 'concept', 'old', 's', 'e');

require_once ( getenv('MW_INSTALL_PATH') !== false
    ? getenv('MW_INSTALL_PATH')."/maintenance/commandLine.inc"
    : dirname( __FILE__ ) . '/../../../maintenance/commandLine.inc' );

$output_level  = array_key_exists('quiet', $options)?0:
                 (array_key_exists('verbose', $options)?2:1);

if (array_key_exists( 'help', $options )) {
	$action = 'help';
} elseif (array_key_exists( 'status', $options )) {
	$action = 'status';
	outputMessage("\nDisplaying concept cache status information. Use CTRL-C to abort.\n\n");
} elseif (array_key_exists( 'create', $options )) {
	$action = 'create';
	outputMessage("\nCreating/updating concept caches. Use CTRL-C to abort.\n\n");
} elseif (array_key_exists( 'delete', $options )) {
	$action = 'delete';
	require_once("$IP/maintenance/counter.php");
	outputMessage("\nDeleting concept caches. Use CTRL-C to abort.\n\n");
	$delay = 5;
	if (outputMessage(print "Waiting for $delay seconds ...  ")) {
		// TODO
		// Remove the following section and replace it with a simple
		// wfCountDown as soon as we switch to MediaWiki 1.16. 
		// Currently, wfCountDown is only supported from
		// revision 51650 (Jun 9 2009) onward.
		if (function_exists("wfCountDown")) {
			wfCountDown( $delay );	
		} else {
    		for ( $i = $delay; $i >= 0; $i-- ) {
	        	if ( $i != $delay ) {
    	        	echo str_repeat( "\x08", strlen( $i + 1 ) );
        		} 
        		echo $i;
	        	flush();
    	    	if ( $i ) {
        	    	sleep( 1 );
        		}
    		}
	    	echo "\n";		
		}
		// Remove up to here and just uncomment the following line:
		// wfCountDown( $delay );
	}
} else {
	$action = 'help';
}

if ($action == 'help') {
	print <<<ENDS

This script is used to manage concept caches for Semantic MediaWiki. Concepts
are semantic queries stored on Concept: pages. The elements of concepts can be
computed online, or they can come from a pre-computed cache. The wiki may even
be configured to display certain concepts only if they are available cached.

This script can create, delete and update these caches, or merely show their
status.

Usage: php SMW_conceptCache.php <action> [<select concepts>] [<options>]

Actions:
  --help      Show this message.
  --status    Show the cache status of the selected concepts.
  --create    Rebuild caches for the selected concepts.
  --delete    Remove all caches for the selected concepts.

If no further options are given, all concepts in the wiki are processed.

Select concepts:
  --concept "Concept name"      Process only this one concept.
  --hard          Process only concepts that are not allowed to be computed
                  online according to the current wiki settings.
  --update        Process only concepts that already have some cache, i.e. do
                  not create any new caches. For the opposite (only concepts
                  without caches), use --old with a very high number.
  --old <min>     Process only concepts with caches older than <min> minutes
                  or with no caches at all.
  -s <startid>    Process only concepts with page id of at least <startid>
  -e <endid>      Process only concepts with page id of at most <endid>

Selection options can be combined to process only concepts that meet all the
requirements at once. If --concept is given, then -s and -e are ignored.

Options:
  --quiet         Do not give any output.
  --verbose       Give additional output. No effect if --quiet is given.


ENDS
;
	return;
}

global $smwgIP;
if (! isset($smwgIP)) 
     $smwgIP = dirname(__FILE__) . '/..';

require_once($smwgIP . '/includes/SMW_GlobalFunctions.php');

$store = smwfGetStore();
$db =& wfGetDB( DB_SLAVE );

if (!($store instanceof SMWSQLStore2)) {
	outputMessage("Only SMWSQLStore2 supports this operation.\n Aborting.");
	return;
}

$select_hard   = array_key_exists('hard', $options);
$select_update = array_key_exists('update', $options);
$select_old    = isset( $options['old'] )?intval($options['old']):false;

if( isset( $options['concept'] ) ) { // single concept mode
	// 	$concept = SMWDataValueFactory::newTypeIDValue('_wpg');
	// 	$concept->setValues('African_countries',SMW_NS_CONCEPT);
	global $wgContLang;
	$concept = Title::newFromText($wgContLang->getNsText(SMW_NS_CONCEPT) . ':' . $options['concept']);
	if ($concept !== NULL) {
		doAction($concept);
	}
} else { // iterate over concepts
	if ( array_key_exists( 's', $options ) ) {
		$start = intval($options['s']);
	} else {
		$start = 0;
	}
	$end = $db->selectField( 'page', 'max(page_id)', false, 'SMW_refreshData' );
	if ( array_key_exists( 'e', $options ) ) {
		$end = min(intval($options['e']), $end);
	}
	$num_lines = 0;

	for ($id = $start; $id <= $end; $id++) {
		$title = Title::newFromID($id);
		if (($title === NULL) || ($title->getNamespace() != SMW_NS_CONCEPT)) continue;
		$num_lines += doAction($title, $num_lines);
	}
}

outputMessage("\n\nDone.\n");


function doAction($title, $numlines = false) {
	global $action, $store, $select_hard, $select_old, $select_update, $smwgQMaxSize, $smwgQMaxDepth, $smwgQFeatures;
	$errors = array();
	$status = false;
	if ($select_hard || $select_old || $select_update || ($action == 'status')) {
		$status = $store->getConceptCacheStatus($title);
	}
	$skip = false;
	if (($status !== false) && ($status['status'] == 'no')) {
		$skip = 'page not cachable (no concept description, maybe a redirect)';
	} elseif (($select_update) && ($status['status'] != 'full')) {
		$skip = 'page not cached yet';
	} elseif ( ($select_old) && ($status['status'] == 'full') && ($status['date'] > (strtotime("now") - $select_old*60) )) {
		$skip = 'cache is not old yet';
	} elseif ( ($select_hard) && ($smwgQMaxSize >= $status['size']) && 
	           ($smwgQMaxDepth >= $status['depth'] && 
	           ( (~(~($status['features']+0) | $smwgQFeatures)) == 0) ) ) {
		$skip = 'concept is not "hard" according to wiki settings';
	}
	if ($skip) {
		$pref = ($numlines !== false)?"($numlines) ":'';
		return (outputMessage($pref . "Skipping concept \"" . $title->getPrefixedText() . "\": $skip\n",2))?1:0;
	}
	if ($numlines !== false) {
		outputMessage("($numlines) ");
	}
	switch ($action) {
		case 'delete':
			outputMessage("Deleting cache for \"" . $title->getPrefixedText() . "\" ...\n");
			$errors = $store->deleteConceptCache($title);
		break;
		case 'create':
			outputMessage("Creating cache for \"" . $title->getPrefixedText() . "\" ...\n");
			$errors = $store->refreshConceptCache($title);
		break;
		default:
			outputMessage("Status of cache for \"" . $title->getPrefixedText() . "\": ");
			if ($status['status'] == 'no') {
				outputMessage("Concept not known or redirect.\n");
			} elseif ($status['status'] == 'full') {
				outputMessage("Cache created at " . date("Y-m-d H:i:s",$status['date']) . " (" . floor((strtotime("now") - $status['date'])/60) . " minutes old), " . $status['count'] . " elements in cache\n");
			} else {
				outputMessage("Not cached.\n");
			}
		break;
	}
	if (count($errors) > 0) {
		outputMessage("  " . implode($errors,"\n  ") . "\n");
	}
	return 1;
}

function outputMessage($message, $level = 1) {
	global $output_level;
	if ($output_level < $level) {
		return false;
	}
	print $message;
	return true;
}
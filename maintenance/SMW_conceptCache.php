<?php
/**
 * Manage SMW concept caches, as supplied by SMWSQLStore2.
 *
 * Note: if SMW is not installed in its standard path under ./extensions
 *       then the MW_INSTALL_PATH environment variable must be set.
 *       See README in the maintenance directory.
 *
 * @author Markus Krötzsch
 * @file
 * @ingroup SMWMaintenance
 */


$optionsWithArgs = array( 'concept', 's', 'e');

require_once ( getenv('MW_INSTALL_PATH') !== false
    ? getenv('MW_INSTALL_PATH')."/maintenance/commandLine.inc"
    : dirname( __FILE__ ) . '/../../../maintenance/commandLine.inc' );
require_once("$IP/maintenance/counter.php");

global $smwgIP;
if (! isset($smwgIP)) 
     $smwgIP = dirname(__FILE__) . '/..';

require_once($smwgIP . '/includes/SMW_GlobalFunctions.php');

$store = smwfGetStore();

if (!($store instanceof SMWSQLStore2)) {
	print "Only SMWSQLStore2 supports this operation.\n Aborting.";
	return;
}

if (array_key_exists( 'delete', $options )) {
	$action = 'delete';
} elseif (array_key_exists( 'create', $options )) {
	$action = 'create';
} else {
	$action = 'status';
}

if( isset( $options['concept'] ) ) { // single concept mode
	// 	$concept = SMWDataValueFactory::newTypeIDValue('_wpg');
	// 	$concept->setValues('African_countries',SMW_NS_CONCEPT);
	global $wgContLang;
	$concept = Title::newFromText($wgContLang->getNsText(SMW_NS_CONCEPT) . ':' . $options['concept']);
	if ($concept !== NULL) {
		doAction($concept, $store);
	}
} else { // iterate over concepts
	$db =& wfGetDB( DB_SLAVE );
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
		doAction($title, $store, $num_lines);
		$num_lines++;
	}
}

print "\n\nDone.\n";


function doAction($title, $store, $numlines = false) {
	global $action;
	$errors = array();
	if ($numlines !== false) {
		print "($numlines) ";
	}
	switch ($action) {
		case 'delete':
			print "Deleting cache for \"" . $title->getPrefixedText() . "\" ...\n";
			$errors = $store->deleteConceptCache($title);
		break;
		case 'create':
			print "Creating cache for \"" . $title->getPrefixedText() . "\" ...\n";
			$errors = $store->refreshConceptCache($title);
		break;
		default:
			print "Status of cache for \"" . $title->getPrefixedText() . "\": " . $store->showConceptCache($title) . "\n";
		break;
	}
	if (count($errors) > 0) {
		print "  " . implode($errors,"\n  ") . "\n";
	}
}
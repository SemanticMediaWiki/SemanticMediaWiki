<?php
/**
 * Unifies relations and attributes to properties. Prior to SMW 1.0,
 * there used to be two kinds of properties, relations (between two
 * pages) and attributes (between a page and a value). In order to
 * streamline usage, those were unified.
 *
 * This script helps by moving pages in the wrong namespace (i.e.
 * the former relations namespace) to the property namespace.
 *
 * Note: if SMW is not installed in its standard path under ./extensions
 *       then the MW_INSTALL_PATH environment variable must be set.
 *       See README in the maintenance directory.
 *
 * Usage:
 * php SMW_unifyProperties.php [options...]
 *
 * -v           Be verbose about the progress.
 * -c           Checks if there are any pages that need to be moved
 *
 * @author Denny Vrandecic
 * @file
 * @ingroup SMWMaintenance
 */

require_once ( getenv('MW_INSTALL_PATH') !== false
    ? getenv('MW_INSTALL_PATH')."/maintenance/commandLine.inc"
    : dirname( __FILE__ ) . '/../../../maintenance/commandLine.inc' );

global $smwgIP;
global $wgParser;

$verbose = array_key_exists( 'v', $options );
$check_only = array_key_exists( 'c', $options );

print "Checking if properties need to be unified\n";

$dbr =& wfGetDB( DB_MASTER );
$relations = $dbr->select( 'page' , 'page_id , page_title' , 'page_is_redirect = 0 AND page_namespace = ' . SMW_NS_RELATION , 'SMW_unifyProperties script' );
$numRels = $relations->numRows();

if ($numRels === 0) {
	print "No unification required. Everything is fine.\n";
} else {
	print "Unification is required. $numRels relation pages need to be moved.\n";
	$page_table = $dbr->tableName( 'page' );
	$conflicts = $dbr->query( 'SELECT p1.page_id , p1.page_title ' .
							  'FROM ' . $page_table . ' AS p1 , ' . $page_table . ' AS p2 ' .
							  'WHERE p1.page_namespace = ' . SMW_NS_RELATION . 
							  ' AND  p2.page_namespace = ' . SMW_NS_PROPERTY . 
							  ' AND  p1.page_is_redirect = 0 ' .
							  ' AND   p1.page_title = p2.page_title' ,
							  'SMW_unifyProperties script' );
	$numConflicts = $conflicts->numRows();
	$con = array();
	if ($numConflicts === 0) {
		print "No conflicts have been detected.\n";
	} else {
		print "$numConflicts conflicts have been detected.\n";
		while( $conflict = $conflicts->fetchObject() ) {
			$con[] = $conflict->page_id;
			print $conflict->page_title . "\n";
		}
		print "Please unify these conflicts manually.\n";
	}
	$conflicts->free();
	if (( $numRels > $numConflicts ) && !$check_only ) {
		$moving = $numRels - $numConflicts;
		print "Moving " . $moving . " pages now.\n";
		while ( $relation = $relations->fetchObject() ) {
			if ( !in_array( $relation->page_id , $con ) ) {
				$title = Title::newFromID( $relation->page_id );
				$newTitle = Title::makeTitle ( SMW_NS_PROPERTY , $relation->page_title );
				if ( $verbose ) print "Moving page with ID " . $relation->page_id . ": " . $relation->page_title . "\n";
				$err = $title->moveTo( $newTitle , false , "Unifying properties" );
				if ( !$err ) print $err;
			}
		}
	}
}
$relations->free();
print "Unify properties script done.\n";


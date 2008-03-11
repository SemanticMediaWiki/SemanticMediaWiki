<?php

/**
 * Usage:
 * php SMW_dumpRDF.php [options...]
 *
 * -o <filename>  output file, stdout is used if omitted; 
 *                file output is generally better and strongly recommended for large wikis
 * --categories   only do categories
 * --properties   only do properties
 * --types        only do types
 * --individuals  only do pages that are no categories, properties, or types
 * -d <delay>     slows down the export in order to stress the server less,
 *                sleeping for <delay> milliseconds every now and then
 * -e <each>      after how many exported entities should the server take a nap?
 * --server=<server> The protocol and server name to as base URLs, e.g.
 *                http://en.wikipedia.org. This is sometimes necessary because
 *                server name detection may fail in command line scripts.
 *
 * @author Markus Krötzsch
 */

$optionsWithArgs = array( 'o', 'd', 'e' ); 

require_once( 'commandLine.inc' );
require_once( "$IP/extensions/SemanticMediaWiki/specials/Export/SMW_SpecialOWLExport.php");

if ( !empty( $options['o'] ) ) {
	$outfile = $options['o'];
} else {
	$outfile = false;
}
if ( !empty( $options['d'] ) ) {
	$delay = intval($options['d']) * 1000;
} else {
	$delay = 0;
}
if ( !empty( $options['e'] ) ) {
	$delayeach = intval($options['e']);
} else {
	$delayeach = ( $delay === 0 ) ? 0 : 1;
}


if ( array_key_exists( 'categories' , $options ) ) {
	$export_ns = NS_CATEGORY;
} elseif ( array_key_exists( 'properties' , $options ) ) {
	$export_ns = SMW_NS_PROPERTY;
} elseif ( array_key_exists( 'types' , $options ) ) {
	$export_ns = SMW_NS_TYPE;
} elseif ( array_key_exists( 'individuals' , $options ) ) {
	$export_ns = -1;
} else {
	$export_ns = false;
}

if ( isset( $options['server'] ) ) {
	$wgServer = $options['server'];
}

$exRDF = new OWLExport();
$exRDF->printAll($outfile, $export_ns, $delay, $delayeach);


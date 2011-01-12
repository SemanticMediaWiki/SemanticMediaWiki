<?php
/**
 * Usage:
 * php SMW_dumpRDF.php [options...]
 *
 * Note: if SMW is not installed in its standard path under ./extensions
 *       then the MW_INSTALL_PATH environment variable must be set.
 *       See README in the maintenance directory.
 *
 * -o <filename>  output file, stdout is used if omitted;
 *                file output is generally better and strongly recommended for large wikis
 * --categories   do only categories
 * --concepts     do only concepts
 * --classes      do only concepts and categories
 * --properties   do only properties
 * --types        do only types
 * --individuals  do only pages that are no categories, properties, or types
 * -d <delay>     slows down the export in order to stress the server less,
 *                sleeping for <delay> milliseconds every now and then
 * -e <each>      after how many exported entities should the process take a nap?
 * --server=<server> The protocol and server name to as base URLs, e.g.
 *                http://en.wikipedia.org. This is sometimes necessary because
 *                server name detection may fail in command line scripts.
 *
 * @author Markus Krötzsch
 * @file
 * @ingroup SMWMaintenance
 */

$optionsWithArgs = array( 'o', 'd', 'e', 'server' );

require_once ( getenv( 'MW_INSTALL_PATH' ) !== false
	? getenv( 'MW_INSTALL_PATH' ) . "/maintenance/commandLine.inc"
	: dirname( __FILE__ ) . '/../../../maintenance/commandLine.inc' );
global $smwgIP, $wgServer;
//require_once( "$smwgIP/specials/Export/SMW_SpecialOWLExport.php" );

if ( !empty( $options['o'] ) ) {
	$outfile = $options['o'];
} else {
	$outfile = false;
}
if ( !empty( $options['d'] ) ) {
	$delay = intval( $options['d'] ) * 1000;
} else {
	$delay = 0;
}
if ( !empty( $options['e'] ) ) {
	$delayeach = intval( $options['e'] );
} else {
	$delayeach = ( $delay === 0 ) ? 0 : 1;
}


if ( array_key_exists( 'categories', $options ) ) {
	$export_ns = NS_CATEGORY;
} elseif ( array_key_exists( 'concepts', $options ) ) {
	$export_ns = SMW_NS_CONCEPT;
} elseif ( array_key_exists( 'classes', $options ) ) {
	$export_ns = array( NS_CATEGORY, SMW_NS_CONCEPT );
} elseif ( array_key_exists( 'properties', $options ) ) {
	$export_ns = SMW_NS_PROPERTY;
} elseif ( array_key_exists( 'types', $options ) ) {
	$export_ns = SMW_NS_TYPE;
} elseif ( array_key_exists( 'individuals', $options ) ) {
	$export_ns = - 1;
} else {
	$export_ns = false;
}

if ( isset( $options['server'] ) ) {
	$wgServer = $options['server'];
}

if ( $outfile && empty( $options['q'] ) ) {
	print "\nWriting OWL/RDF dump to file \"$outfile\" ...\n";
}

$exRDF = new SMWExportController( new SMWRDFXMLSerializer() );
$exRDF->printAll( $outfile, $export_ns, $delay, $delayeach );

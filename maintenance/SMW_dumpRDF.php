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
 * 
 * @author Markus Krötzsch
 */

$optionsWithArgs = array( 'o' ); // -o <output file>

require_once( 'commandLine.inc' );
require_once( "$IP/extensions/SemanticMediaWiki/specials/ExportRDF/SMW_SpecialExportRDF.php");

if ( !empty( $options['o'] ) ) {
	$outfile = $options['o'];
} else {
	$outfile = false;
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

$exRDF = new ExportRDF();
$exRDF->printAll($outfile, $export_ns);


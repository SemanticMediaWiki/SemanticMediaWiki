<?php
/**
 * @author Daniel M. Herzig
 *
 * This special page of the Semantic MediaWiki extension displays some
 * statistics about properties.
 * @file
 * @ingroup SMWSpecialPage
 * @ingroup SpecialPage
 */

function smwfExecuteSemanticStatistics() {
	global $wgOut, $wgLang;
	$dbr =& wfGetDB( DB_SLAVE );

	$semstats = smwfGetStore()->getStatistics();

	$page_table = $dbr->tableName( 'page' );
	$sql = "SELECT Count(page_id) AS count FROM $page_table WHERE page_namespace=" . SMW_NS_PROPERTY;
	$res = $dbr->query( $sql );
	$row = $dbr->fetchObject( $res );
	$property_pages = $row->count;
	$dbr->freeResult( $res );

	wfLoadExtensionMessages( 'SemanticMediaWiki' );

	$out = wfMsgExt( 'smw_semstats_text', array( 'parse' ),
		$wgLang->formatNum( $semstats['PROPUSES'] ), $wgLang->formatNum( $semstats['USEDPROPS'] ),
		$wgLang->formatNum( $property_pages ), $wgLang->formatNum( $semstats['DECLPROPS'] )
	);

	$wgOut->addHTML( $out );
}

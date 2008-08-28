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

	// Do not give these statistics here. They are quite unrelated.
	// 		$views = SiteStats::views();
	// 		$edits = SiteStats::edits();
	// 		$good = SiteStats::articles();
	// 		$images = SiteStats::images();
	// 		$users = SiteStats::users();

	$semstats = smwfGetStore()->getStatistics();

	$page_table = $dbr->tableName( 'page' );
	$sql = "SELECT Count(page_id) AS count FROM $page_table WHERE page_namespace=" . SMW_NS_PROPERTY;
	$res = $dbr->query( $sql );
	$row = $dbr->fetchObject( $res );
	$property_pages = $row->count;
	$dbr->freeResult( $res );

	$sp = Title::makeTitle( NS_SPECIAL, 'Properties');
	$purl = $sp->getFullURL();
	$sp = Title::makeTitle( NS_SPECIAL, 'WantedProperties');
	$wpurl = $sp->getFullURL();
	$sp = Title::makeTitle( NS_SPECIAL, 'UnusedProperties');
	$upurl = $sp->getFullURL();
	wfLoadExtensionMessages('SemanticMediaWiki');
	$out = wfMsg('smw_semstats_text',
	             $wgLang->formatNum($semstats['PROPUSES']), $wgLang->formatNum($semstats['USEDPROPS']),
	             $purl, $wgLang->formatNum($property_pages), $wgLang->formatNum($semstats['DECLPROPS']),
	             $upurl, $wpurl);

	$wgOut->addHTML( $out );
}




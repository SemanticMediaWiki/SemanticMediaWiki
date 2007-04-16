<?php
/**
 * @author Daniel M. Herzig
 *
 * This page shows all relations which don't have an explanatory page
 */

if (!defined('MEDIAWIKI')) die();

global $IP, $smwgIP;

require_once( "$IP/includes/SpecialPage.php" );
require_once( "$IP/includes/Title.php" );
require_once("$IP/includes/QueryPage.php");

function doSpecialRelationsWithoutPage($par = null) {
	list( $limit, $offset ) = wfCheckLimits();
	$rep = new RelationsWithoutPage();
	return $rep->doQuery( $offset, $limit );
}

SpecialPage::addPage( new SpecialPage('RelationsWithoutPage','',true,'doSpecialRelationsWithoutPage',false) );



class RelationsWithoutPage extends QueryPage {

	function getName() {
		return "relation_without";
	}

	function isExpensive() {
		return true;
	}

	function isSyndicated() { return false; }

	function getPageHeader() {
		return '<p>' . wfMsg('smw_relations_withoutpage') . "</p><br />\n";
	}

	function getSQL() {
		$dbr =& wfGetDB( DB_SLAVE );
		$relations = $dbr->tableName( 'smw_relations' );
		$page = $dbr->tableName( 'page' );
		$NSrel = SMW_NS_RELATION;

		return
				"SELECT 'RelationsWithoutPage' as type,
				{$NSrel} as namespace,
				relation_title as title,
				COUNT(*) as value
				FROM $relations
				WHERE relation_title
					NOT IN
					(SELECT DISTINCT page_title	FROM $page)
				GROUP BY relation_title
				";
	}

	function sortDescending() {
		return true;
	}

	function formatResult( $skin, $result ) {
		global $wgLang;
		$title = Title::makeTitle( SMW_NS_RELATION, $result->title );
		$rlink = $skin->makeLinkObj( $title, $title->getText() );
		
		$searchlink = SMWInfolink::newRelationSearchLink('+', $title->getText(), null);
		return "$rlink ($result->value) " . $searchlink->getHTML($skin);
	}
}

?>

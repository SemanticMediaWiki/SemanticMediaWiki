<?php
/**
 * @author Daniel M. Herzig
 *
 * This page shows all relations which don't have an explanatory page
 */

if (!defined('MEDIAWIKI')) die();

global $IP;

require_once( "$IP/includes/SpecialPage.php" );
require_once( "$IP/includes/Title.php" );
require_once("$IP/includes/QueryPage.php");

function doSpecialWantedRelations($par = null) {
	list( $limit, $offset ) = wfCheckLimits();
	$rep = new SMWWantedRelationsPage();
	return $rep->doQuery( $offset, $limit );
}

SpecialPage::addPage( new SpecialPage('WantedRelations','',true,'doSpecialWantedRelations',false) );



class SMWWantedRelationsPage extends QueryPage {

	function getName() {
		return "wanted_relations";
	}

	function isExpensive() {
		return true;
	}

	function isSyndicated() { return false; }

	function getPageHeader() {
		return '<p>' . wfMsg('smw_wanted_relations') . "</p><br />\n";
	}

	function getSQL() {
		$dbr =& wfGetDB( DB_SLAVE );
		$relations = $dbr->tableName( 'smw_relations' );
		$page = $dbr->tableName( 'page' );
		$NSrel = SMW_NS_RELATION;

		return
				"SELECT 'WantedRelations' as type,
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

		return "$rlink ($result->value) ";
	}
}

?>

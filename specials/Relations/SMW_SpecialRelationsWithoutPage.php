<?php
/**
 * @author Daniel M. Herzig
 *
 * This page shows all relations which don't have an explanatory page
 */

if (!defined('MEDIAWIKI')) die();

$wgExtensionFunctions[] = "wfSMWRelationsWithoutPage";

function wfSMWRelationsWithoutPage()
{
	global $wgMessageCache;
	smwfInitMessages();
	global $IP, $smwgIP;

	require_once( "$IP/includes/SpecialPage.php" );
	require_once( "$IP/includes/Title.php" );
	require_once("$IP/includes/QueryPage.php");

	// define this inline, since QueryPage.php cannot be loaded at setup-time
	class RelationsWithoutPage extends QueryPage {

		function getName() {
			return "relation_without";
		}

		function isExpensive() {
			return false;
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
					relation_title as value,
					COUNT(*) as count
					FROM $relations
					WHERE relation_title
					NOT IN
					(SELECT DISTINCT page_title
					FROM $page)
					GROUP BY relation_title";
		}

		function sortDescending() {
			return false;
		}

		function formatResult( $skin, $result ) {
			global $wgLang;
			$title = Title::makeTitle( SMW_NS_RELATION, $result->title );
			$rlink = $skin->makeLinkObj( $title, $title->getText() );
			// Note: It doesn't seem possible to reuse this infolink object.
			$searchlink = new SMWInfolink(
			    SMWInfolink::makeRelationSearchURL($title->getText(),'',$skin),
			    '+','smwsearch');

			return "$rlink ($result->count) " . $searchlink->getHTML();
		}
	}


	function doSpecialRelationsWithoutPage($par = null)
	{
		list( $limit, $offset ) = wfCheckLimits();
		$rep = new RelationsWithoutPage();
		return $rep->doQuery( $offset, $limit );
	}

	SpecialPage::addPage( new SpecialPage('RelationsWithoutPage','',true,'doSpecialRelationsWithoutPage',false) );
}

?>

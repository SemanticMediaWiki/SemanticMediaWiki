<?php
/**
 * @author Denny Vrandecic
 *
 * This page shows all used relations.
 */

if (!defined('MEDIAWIKI')) die();

$wgExtensionFunctions[] = "wfSMWRelations";

function wfSMWRelations()
{
	global $wgMessageCache;
	smwfInitMessages(); // initialize messages, always called before anything else on this page
	global $IP, $smwgIP;
	require_once($smwgIP . '/includes/SMW_Storage.php');
	require_once( "$IP/includes/SpecialPage.php" );
	require_once( "$IP/includes/Title.php" );
	require_once("$IP/includes/QueryPage.php");
	
	// define this inline, since QueryPage.php cannot be loaded at setup-time
	class RelationsPage extends QueryPage {
	
		function getName() {
			return "Relations";
		}
	
		function isExpensive() {
			return false;
		}
	
		function isSyndicated() { return false; }
	
		function getPageHeader() {
			return '<p>' . wfMsg('smw_relations_docu') . "</p><br />\n";
		}
		function getSQL() {
			$dbr =& wfGetDB( DB_SLAVE );
			$relations = $dbr->tableName( 'smw_relations' );
			$NSrel = SMW_NS_RELATION;
			# QueryPage uses the value from this SQL in an ORDER clause.
			return "SELECT 'Relations' as type,
						{$NSrel} as namespace,
						relation_title as title,
						relation_title as value,
						COUNT(*) as count
						FROM $relations
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
	
	
	function doSpecialRelations($par = null)
	{
		list( $limit, $offset ) = wfCheckLimits();
		$rep = new RelationsPage();
		return $rep->doQuery( $offset, $limit );
	}
	
	SpecialPage::addPage( new SpecialPage('Relations','',true,'doSpecialRelations',false) );
}

?>
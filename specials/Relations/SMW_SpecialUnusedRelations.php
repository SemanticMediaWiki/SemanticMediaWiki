<?php
/**
 * @author Denny Vrandecic
 *
 * This page shows all relations that have a page but are
 * are not instantiated.
 */

if (!defined('MEDIAWIKI')) die();

$wgExtensionFunctions[] = "wfSMWUnusedRelations";

function wfSMWUnusedRelations()
{
	global $wgMessageCache;
	smwfInitMessages(); // initialize messages, always called before anything else on this page
	global $IP, $smwgIP;
	require_once($smwgIP . '/includes/SMW_Storage.php');
	require_once( "$IP/includes/SpecialPage.php" );
	require_once( "$IP/includes/Title.php" );
	require_once("$IP/includes/QueryPage.php");
	
	class UnusedRelationsPage extends QueryPage {
	
		function getName() {
			return "UnusedRelations";
		}
	
		function isExpensive() {
			return false;
		}
	
		function isSyndicated() { return false; }
	
		function getPageHeader() {
			return '<p>' . wfMsg('smw_unusedrelations_docu') . "</p><br />\n";
		}
		function getSQL() {
			$NScat = SMW_NS_RELATION;
			$dbr =& wfGetDB( DB_SLAVE );
			extract( $dbr->tableNames( 'smw_relations','page' ));
			return "SELECT 'Unusedrelations' as type, 
					{$NScat} as namespace,
					page_title as title,
					1 as value
					FROM $page
					LEFT JOIN $smw_relations ON page_title=relation_title
					WHERE subject_id IS NULL
					AND page_namespace = {$NScat}
					AND page_is_redirect = 0";
	//		return "SELECT 'Unusedcategories' as type,
	//				{$NScat} as namespace, page_title as title, 1 as value
	//				FROM $page
	//				LEFT JOIN $categorylinks ON page_title=cl_to
	//				WHERE cl_from IS NULL
	//				AND page_namespace = {$NScat}
	//				AND page_is_redirect = 0";
		}
		
		function sortDescending() {
			return false;
		}
	
		function formatResult( $skin, $result ) {
			global $wgLang;
			$title = Title::makeTitle( SMW_NS_RELATION, $result->title );
			return $skin->makeLinkObj( $title, $title->getText() );
		}
	}	
	
	function doSpecialUnusedRelations($par = null)
	{
		list( $limit, $offset ) = wfCheckLimits();
		$rep = new UnusedRelationsPage();
		return $rep->doQuery( $offset, $limit );
	}
	
	SpecialPage::addPage( new SpecialPage('UnusedRelations','',true,'doSpecialUnusedRelations',false) );
}




class SMWUnusedRelationsPage extends SpecialPage
{
	/**#@+
	 * @access private
	 */
	/**
	 * The name of the exported page.
	 */
	var $pagename;

	function SMWUnusedRelationsPage()
	{
		SpecialPage::SpecialPage('SMWUnusedRelations');
	}



}
?>
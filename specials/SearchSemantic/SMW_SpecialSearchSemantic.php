<?php
/**
 * @author Klaus Lassleben
 *
 * This special page for MediaWiki implements the semantic search.
 * It works only on the relations (subject/relation/object) which
 * are stored in the separate table.
 * The text input for "relation" has an autocomplete function
 * realized with the AJAX implementation "JPSPAN". Therefore
 * "JPSPAN" has to be installed on your server.
 */

if (!defined('MEDIAWIKI')) die();
$wgExtensionFunctions[] = "wfSearchSemanticExtension";


function wfSearchSemanticExtension()
{
	global $IP, $wgMessageCache, $wgOut;
	require_once( "$IP/includes/SpecialPage.php" );

	$wgMessageCache->addMessages(array('searchsemantic' => 'Semantic Search'));

	class SearchSemanticPage extends SpecialPage
	{
		function SearchSemanticPage()
		{
			SpecialPage::SpecialPage('SearchSemantic');
			$this->includable( true );
		}

		function execute($par = null)
		{
			global $wgOut, $wgRequest;

			$out = '';
			$searchx = $wgRequest->getVal('searchx');
			$relation = $wgRequest->getVal('relation');
			$object = $wgRequest->getVal('object');

			// build search form
			$out .= $this->getSearchForm($relation, $object);
			
			// if relation and object are specified
			// perform search and show results
			if($relation && $object)
			{
				$out .= $this->searchSemantic($relation, $object);
			}
			
			$wgOut->addHTML($out);
		}
		
		function getSearchForm($relation, $object)
		{
			global $wgOut;
			
			$form = '';

			$form .= '<link rel="stylesheet" href="../extensions/SemanticMediaWiki/autocomplete.css"/>';
			$form .= '<script type="text/javascript" src="../extensions/SemanticMediaWiki/autocomplete.js"></script>';
			$form .= '<script type="text/javascript" src="../extensions/SMW_Autocomplete.php?client"></script>';
			$form .= '<script type="text/javascript" src="../extensions/SemanticMediaWiki/semanticSearch.js"></script>';
			
			$form .= '<p><strong>This search interface is not fully functional at the moment, since it needs to be adjusted to the new internal data format. You may want to check out the Special "Triple Search" for an alternative. However, the following form can still be used to printout the unformated contents of the database. Be careful when entering unspecific queries, such as "http," that occur in a large number of entries. </strong></p>';
			$form .= '<form name="semanticSearch" action="" method="GET">';
			$form .= '	Please show me everthing/everybody which/who...';
			$form .= '	<input type="text" name="relation" value="' . $relation . '" autocomplete="off" language="javascript" onkeyup="actb_tocomplete(this,event,relations,false);" onblur="actb_removedisp(this);" onkeydown="actb_checkkey(event);" onkeypress="return handleEnter(this, event)"/>';
			$form .= '	<input type="text" name="object" value="' . $object . '" autocomplete="off"/>';
			$form .= '	<input type="submit" name="searchx" value="' . htmlspecialchars( wfMsg('powersearch') ) . '" />';
			$form .= '</form>';
			
			$form .= '<script language="javascript">';
			$form .= '	document.semanticSearch.relation.focus();';
			$form .= '	document.semanticSearch.relation.select();';
			$form .= '</script>';
			
			return $form;
		}
		
		function searchSemantic($relation, $object)
		{
			global $glTableName;
			global $wgUser;
			
			$searchResult = '';
			
			$fname = 'SpecialSearchSemantic::searchSemantic';
			$db =& wfGetDB(DB_MASTER);
	
	   	$sql = 'SELECT subject, relation, object
	   				FROM ' . $glTableName . '
	   				WHERE relation like \'%' . $relation . '%\'
	   				AND object like \'%' . $object . '%\'
	   				ORDER BY subject';
	   	$res = $db->query( $sql, $fname );
	
			$searchResult .= '<br/><hr/><b>' . wfMsg('searchresults') . '</b><br/>';
			
			$subjects = array();
			if($db->numRows( $res ) > 0)
			{
				$searchResult .= '<table>';
				$sk =& $wgUser->getSkin();
				
				$row = $db->fetchObject($res);
				while($row)
				{
					$searchResult .= '<tr>';
					$searchResult .= '<td>' . $row->subject . '</td>';
					$searchResult .= '<td>' . $row->relation . '</td>';
					$searchResult .= '<td>' . $row->object . '</td>';
					$searchResult .= '</td>';

					$row = $db->fetchObject($res);
				}
				$db->freeResult($res);
				//$searchResult .= implode('<br/>', $subjects);
				$searchResult .= '</table>';
			}
			else
			{
				$searchResult .= wfMsg('notitlematches');
			}
			
			return $searchResult;
		}
	}

	SpecialPage::addPage( new SearchSemanticPage );
}
?>

<?php
/**
 * @author Markus Krötzsch
 * @author Klaus Lassleben
 *
 * This special page for MediaWiki implements a simple triple search.
 * It only supports full matching but can also be useful to introspect
 * the database.
 *
 * TODO: This function is obsolete and will be removed in some future 
 * (included for backwards compatibility now).
 */

if (!defined('MEDIAWIKI')) die();


global $IP, $smwgIP;
require_once($smwgIP . '/includes/SMW_Datatype.php');
require_once( "$IP/includes/SpecialPage.php" );

function doSpecialSearchTriple($par = null)	{
	global $wgOut, $wgRequest, $wgUser;

	$out = '';
	$searchtype = $wgRequest->getVal('do');
	$subject = $wgRequest->getVal('subject');
	$relation = $wgRequest->getVal('relation');
	$object = $wgRequest->getVal('object');
	$attribute = $wgRequest->getVal('attribute');
	$value = $wgRequest->getVal('value');
	
	$relation = ucfirst($relation);
	$attribute = ucfirst($attribute);


	$sk =& $wgUser->getSkin();
	$out .= '<h2>' . wfMsg('redirectpagesub') . '</h2>';
	$out .= '<ul><li>' . $sk->makeKnownLinkObj(Title::newFromText('Browse',NS_SPECIAL), wfMsg('browse') )  . '</li>';
	$out .= '<li>' . $sk->makeKnownLinkObj(Title::newFromText('SearchByRelation',NS_SPECIAL), wfMsg('searchbyrelation') )  . '</li>';
	$out .= '<li>' . $sk->makeKnownLinkObj(Title::newFromText('SearchByAttribute',NS_SPECIAL), wfMsg('searchbyattribute') )  . '</li>';
	$out .= '<li>' . $sk->makeKnownLinkObj(Title::newFromText('Ask',NS_SPECIAL), wfMsg('ask') ) . '</li></ul>';
	$out .= '<br /><br /><div style="border: 1px solid grey; padding: 20px; background: #DDD; ">';
	
	$out .= SMWSpecialSearchTriple::getSearchForm($subject, $relation, $object, $attribute, $value, $searchtype);
	// find out what the user wants:
	if ( ($searchtype==wfMsg('smw_searchtriple_searchatt')) || ($searchtype=='Search Attributes')) {
		// Search for attributes
		$out .= SMWSpecialSearchTriple::searchAttributes($subject, $attribute, $value);
	} elseif ( ($searchtype==wfMsg('smw_searchtriple_searchrel')) || ($searchtype=='Search Relations') ) { 
		// Search for relations
		$out .= SMWSpecialSearchTriple::searchRelations($subject, $relation, $object);
	} // else: just don't do anything
	
	$wgOut->setPageTitle(wfMsg('searchtriple'));
	$out .= '</div>';
	$wgOut->addHTML($out);
}

SpecialPage::addPage( new SpecialPage('SearchTriple','',false,'doSpecialSearchTriple',false));



// static class to encapsulate some functions
class SMWSpecialSearchTriple {

	function getSearchForm($subject, $relation, $object, $attribute, $value) {
		global $wgOut;
		
		$form = '';
		
		$title = Title::makeTitle( NS_SPECIAL, 'SearchTriple' );

		$form .= '<form name="tripleSearch" action="' . $title->escapeLocalURL() . '" method="get"><input type="hidden" name="title" value="' . $title->getPrefixedText() . '"/>';
		$form .= wfMsg('smw_searchtriple_docu') . "\n\n";
// 			$form .= 
// 			'<input id="rel" type="radio" name="searchtype" value="relation" checked="checked" /><label for="rel">Relation</label>'.
// 			'<input id="att" type="radio" name="searchtype" value="attribute" checked="checked" /><label for="att">Attribute</label>';
		
		$form .= '<table summary="layout table" style="padding: 4px 5px; border:0px; border-collapse:collapse; background: none; ">' . "\n";
		//line 1 and 2: search for relations
		$form .= '<tr>' . "\n";
		$form .= '<td>' . wfMsg('smw_searchtriple_subject') . '</td>' . "\n";
		$form .= '<td>' . wfMsg('smw_searchtriple_relation') . '</td>' . "\n";
		$form .= '<td>' . wfMsg('smw_searchtriple_object') . '</td>' . "\n";
		$form .= '<td></td>' . "\n";
		$form .= '</tr>' . "\n";			
		$form .= '<tr>' . "\n";
		$form .= '<td><input disabled="disabled" type="text" name="subject" value="' . htmlspecialchars($subject) . '"/></td>' . "\n";
		$form .= '<td><input disabled="disabled" type="text" name="relation" value="' . htmlspecialchars($relation) . '"/></td>' . "\n";
		$form .= '<td><input disabled="disabled" type="text" name="object" value="' . htmlspecialchars($object) . '"/></td>' . "\n";
		$form .= '<td><input disabled="disabled" type="submit" name="do" value="' . wfMsg('smw_searchtriple_searchrel') . '"/></td>' . "\n";
		$form .= '</tr>' . "\n";
		//line 3 and 4: search for attributes
		$form .= '<tr>' . "\n";
		$form .= '<td></td>' . "\n";
		$form .= '<td>' . wfMsg('smw_searchtriple_attribute') . '</td>' . "\n";
		$form .= '<td>' . wfMsg('smw_searchtriple_attvalue') . '</td>' . "\n";
		$form .= '<td></td>' . "\n";
		$form .= '</tr>' . "\n";			
		$form .= '<tr>' . "\n";
		$form .= '<td></td>' . "\n";
		$form .= '<td><input disabled="disabled" type="text" name="attribute" value="' . htmlspecialchars($attribute) . '"/></td>' . "\n";
		$form .= '<td><input disabled="disabled" type="text" name="value" value="' . htmlspecialchars($value) . '"/></td>' . "\n";
		$form .= '<td><input disabled="disabled" type="submit" name="do" value="' . wfMsg('smw_searchtriple_searchatt') . '"/></td>' . "\n";
		$form .= '</tr>' . "\n";
		$form .= "</table> \n </form> \n <br/><hr/> \n";
		
// 			$form .= '<script language="javascript">' . "\n";
// 			$form .= '	document.tripleSearch.subject.focus();' . "\n";
// 			$form .= '	document.tripleSearch.subject.select();' . "\n";
// 			$form .= '</script>' . "\n";
		
		return $form;
	}
}



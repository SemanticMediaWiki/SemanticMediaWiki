<?php
/**
 * @author Markus Krötzsch
 * @author Klaus Lassleben
 *
 * This special page for MediaWiki implements a simple triple search.
 * It only supports full matching but can also be useful to introspect
 * the database.
 */

if (!defined('MEDIAWIKI')) die();

$wgExtensionFunctions[] = "wfSearchTripleExtension";

function wfSearchTripleExtension()
{
	global $IP, $smwgIP, $wgMessageCache, $wgOut;
	require_once($smwgIP . '/includes/SMW_Datatype.php');
	require_once( "$IP/includes/SpecialPage.php" );

	smwfInitMessages(); // initialize messages, always called before anything else on this page

	function doSpecialSearchTriple($par = null)
	{
		global $wgOut, $wgRequest;

		$out = '';
		$searchtype = $wgRequest->getVal('do');
		$subject = $wgRequest->getVal('subject');
		$relation = $wgRequest->getVal('relation');
		$object = $wgRequest->getVal('object');
		$attribute = $wgRequest->getVal('attribute');
		$value = $wgRequest->getVal('value');
		
		$relation = ucfirst($relation);
		$attribute = ucfirst($attribute);

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
		$wgOut->addHTML($out);
	}
	
	SpecialPage::addPage( new SpecialPage('SearchTriple','',true,'doSpecialSearchTriple',false));
}


// static class to encapsulate some functions
class SMWSpecialSearchTriple
{
	
	function getSearchForm($subject, $relation, $object, $attribute, $value)
	{
		global $wgOut;
		
		$form = '';
		
		$title = Title::makeTitle( NS_SPECIAL, 'SearchTriple' );

		$form .= '<form name="tripleSearch" action="' . $title->escapeLocalURL() . '" method="GET"><input type="hidden" name="title" value="' . $title->getPrefixedText() . '"/>';
		$form .= wfMsg('smw_searchtriple_docu') . "\n\n";
// 			$form .= 
// 			'<input id="rel" type="radio" name="searchtype" value="relation" checked="checked" /><label for="rel">Relation</label>'.
// 			'<input id="att" type="radio" name="searchtype" value="attribute" checked="checked" /><label for="att">Attribute</label>';
		
		$form .= '<table summary="layout table" style="padding: 4px 5px; border:0px; border-collapse:collapse;">' . "\n";
		//line 1 and 2: search for relations
		$form .= '<tr>' . "\n";
		$form .= '<td>' . wfMsg('smw_searchtriple_subject') . '</td>' . "\n";
		$form .= '<td>' . wfMsg('smw_searchtriple_relation') . '</td>' . "\n";
		$form .= '<td>' . wfMsg('smw_searchtriple_object') . '</td>' . "\n";
		$form .= '<td></td>' . "\n";
		$form .= '</tr>' . "\n";			
		$form .= '<tr>' . "\n";
		$form .= '<td><input type="text" name="subject" value="' . htmlspecialchars($subject) . '"/></td>' . "\n";
		$form .= '<td><input type="text" name="relation" value="' . htmlspecialchars($relation) . '"/></td>' . "\n";
		$form .= '<td><input type="text" name="object" value="' . htmlspecialchars($object) . '"/></td>' . "\n";
		$form .= '<td><input type="submit" name="do" value="' . wfMsg('smw_searchtriple_searchrel') . '"/></td>' . "\n";
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
		$form .= '<td><input type="text" name="attribute" value="' . htmlspecialchars($attribute) . '"/></td>' . "\n";
		$form .= '<td><input type="text" name="value" value="' . htmlspecialchars($value) . '"/></td>' . "\n";
		$form .= '<td><input type="submit" name="do" value="' . wfMsg('smw_searchtriple_searchatt') . '"/></td>' . "\n";
		$form .= '</tr>' . "\n";
		$form .= "</table> \n </form> \n <br/><hr/> \n";
		
// 			$form .= '<script language="javascript">' . "\n";
// 			$form .= '	document.tripleSearch.subject.focus();' . "\n";
// 			$form .= '	document.tripleSearch.subject.select();' . "\n";
// 			$form .= '</script>' . "\n";
		
		return $form;
	}
	
	
	function searchRelations($subject, $relation, $object) 
	{
		global $wgUser;

		if ($subject!='') {
			$stitle = Title::newFromText($subject);
		} else {
			$stitle = NULL;
		}
		if ($relation!='') {
			$rtitle = Title::newFromText($relation,SMW_NS_RELATION);
		} else { 
			$rtitle = NULL; 
		}
		if ($object!='') {
			$otitle = Title::newFromText($object);
		} else {
			$otitle = NULL;
		}

		$res=smwfGetRelations($stitle,$rtitle,$otitle,true);
		$result_header = wfMsg('smw_searchtriple_resultrel');
		
		/* Print results */
		if ($res===false || count($res)<=0) {
			return '<strong>' . $result_header . ':</strong> ' . wfMsg('notitlematches');
		}
		
		$sk =& $wgUser->getSkin();
		
		$searchResult = '';
		$searchResult .= '<p><strong>' . $result_header .
						'</strong></p>' . "\n";
		
		$searchResult .= '<table summary="' . $result_header . '" cellpadding="0" style=" border:0px solid #000; border-collapse:collapse;">' . "\n";
		
		global $wgContLang;
		
		foreach($res as $reldata) {
			$t = Title::newFromID($reldata[0]);
			if ($t != NULL) {
				$s = $sk->makeKnownLink($t->getPrefixedText());
			} else {
				$s = '?';
			}
			$t = Title::newFromText($reldata[1],SMW_NS_RELATION);
			if ($t != NULL) {
				$r = $sk->makeLink($t->getPrefixedText(),$t->getText());
			} else {
				$r = $reldata[1];
			}
			$o = $sk->makeLink($wgContLang->getNsText($reldata[2]) . ':' . $reldata[3]);
			
			$searchResult .= '<tr><td style="text-align:right;"> ' . $s . ' </td><td style="padding:3px 20px; text-align:center;"> ' . $r . ' </td><td style="text-align:left;"> ' . $o . ' </td></tr>' . "\n";
		}
		
		$searchResult .= '</table>' . "\n";
		return $searchResult;
	}
	
	function searchAttributes($subject, $attribute, $value) 
	{
		global $wgUser;
		
		if ($subject!='') {
			$stitle = Title::newFromText($subject);
		} else {
			$stitle=NULL;
		}
		// set unit and (XSD) value
		if ($attribute == '') { 
			$atitle = NULL; 
			$unit = NULL;
			if ($value == '') { $value = NULL; }
		} else {
			$atitle = Title::newFromText($attribute,SMW_NS_ATTRIBUTE);
			$datavalue=SMWDataValue::newAttributeValue($attribute);
			if ( $datavalue->getTypeID() == 'error') {
				$unit=NULL;
				$type=NULL; // unset type
				if ($value=='') { $value=NULL; }
			} else {
				if ($value=='') { // value-wildcard
					////use the standard unit:
					//$units=$type->getUnits();
					//$unit=$units['STDUNIT'];
					////ignore unit:
					$unit=NULL;
					$value=NULL;
				} else { // some value string was given: try to parse it
					// TODO: Performance (medium): setUserValue() calls the data type's processValue() which does a lot of conversion and tooltip work that's unused here.
					$datavalue->setUserValue($value);
					if ( $datavalue->isValid() === false ) { 
						// try to use unparsable values as units
						$unit=$value;
						$value=NULL;
					} else {
						$unit = $datavalue->getUnit();
						$value  = $datavalue->getXSDValue();
					}
				}
			}
		}
		
		$res=smwfGetAttributes($stitle,$atitle,$unit,NULL,$value,true); // do not care about the typeid here
		$result_header = wfMsg('smw_searchtriple_resultatt');
		
		/* Print results */
		if ($res===false || count($res)<=0) {
			return '<strong>' . $result_header . ':</strong> ' . wfMsg('notitlematches');
		}
		
		$sk =& $wgUser->getSkin();
		
		$searchResult = '';
		$searchResult .= '<p><strong>' . $result_header .
						'</strong></p>' . "\n";
		
		$searchResult .= '<table summary="' . $result_header . '" cellpadding="0" style=" border:0px solid #000; border-collapse:collapse;">' . "\n";
		
		global $wgContLang;
		
		foreach($res as $attdata) {
			$t = Title::newFromID($attdata[0]);
			if ($t != NULL) {
				$s = $sk->makeKnownLink($t->getPrefixedText());
			} else {
				$s = '?';
			}
			$t = Title::newFromText($attdata[1],SMW_NS_ATTRIBUTE);
			if ($t != NULL) {
				$r = $sk->makeLink($t->getPrefixedText(),$t->getText());
			} else {
				$r = $attdata[1];
			}
			
			$parsed_value = SMWDataValue::newAttributeValue($attdata[1],$sk);
			$parsed_value->setXSDValue($attdata[4],$attdata[2]);
			if ($parsed_value->isValid()) {
				$o = $parsed_value->getValueDescription();
				//$o = $parsed_value->getStringValue(); //shorter
				foreach ($parsed_value->getInfolinks() as $link) {
						$o .= ' &nbsp;&nbsp;' . $link->getHTML();
				}
			} else {
				$o = $attdata[4] . $attdata[2];
			}
			
			$searchResult .= '<tr><td style="text-align:right;"> ' . $s . ' </td><td style="padding:3px 20px; text-align:center;"> ' . $r . ' </td><td style="text-align:left;"> ' . $o . ' </td></tr>' . "\n";
		}
		
		$searchResult .= '</table>' . "\n";
		return $searchResult;
	}
}

?>

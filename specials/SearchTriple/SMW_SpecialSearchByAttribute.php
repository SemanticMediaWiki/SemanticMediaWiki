<?php
/**
 * @author Denny Vrandecic
 *
 * This special page for Semantic MediaWiki implements a
 * view on a relation-object pair, i.e. a typed baclink.
 * For example, it shows me all persons born in Croatia,
 * or all winners of the Academy Award for best actress.
 */

if (!defined('MEDIAWIKI')) die();

global $IP, $smwgIP, $wgExtensionFunctions;

require_once( "$IP/includes/SpecialPage.php" );
require_once( "$smwgIP/includes/storage/SMW_Store.php" );
$wgExtensionFunctions[] = "wfSearchByAttributeExtension";

function wfSearchByAttributeExtension()
{
	global $wgMessageCache, $wgOut;
	smwfInitMessages(); // initialize messages, always called before anything else on this page

	function doSpecialSearchByAttribute($query = '') {
		SMW_SearchByAttribute::execute($query);
	}

	SpecialPage::addPage( new SpecialPage('SearchByAttribute','',true,'doSpecialSearchByAttribute',false) );
}

class SMW_SearchByAttribute {

	static function execute($query = '') {
		global $wgRequest, $wgOut, $wgUser, $smwgIQMaxLimit;
		$skin = $wgUser->getSkin();

		// get the GET parameters
		$attribute = $wgRequest->getVal( 'attribute' );
		$valuestring = $wgRequest->getVal( 'value' );
		// no GET parameters? Then try the URL
		if (('' == $attribute) && ('' == $valuestring)) {
			$queryparts = explode(':=', $query);
			$attribute = $query;
			if (count($queryparts) == 2) {
				$attribute = $queryparts[0];
				$valuestring = str_replace("_", " ", $queryparts[1]);
			}
		}
		$attributetitle = Title::newFromText( $attribute, SMW_NS_ATTRIBUTE );

		$limit = $wgRequest->getVal( 'limit' );
		if ('' == $limit) $limit =  20;
		$offset = $wgRequest->getVal( 'offset' );
		if ('' == $offset) $offset = 0;
		$html = '';
		$spectitle = Title::makeTitle( NS_SPECIAL, 'SearchByAttribute' );

		// display query form
		$html .= '<form name="searchbyattribute" action="' . $spectitle->escapeLocalURL() . '" method="get">' . "\n" .
		         '<input type="hidden" name="title" value="' . $spectitle->getPrefixedText() . '"/>' ;
		$html .= wfMsg('smw_sbv_attribute') . ' <input type="text" name="attribute" value="' . htmlspecialchars($attribute) . '" />' . "\n";
		$html .= wfMsg('smw_sbv_value') . ' <input type="text" name="value" value="' . htmlspecialchars($valuestring) . '" />' . "\n";
		$html .= '<input type="submit" value="' . wfMsg('smw_sbv_submit') . "\"/>\n</form>\n";

		if ('' == $attribute) { // empty page. If no attribute given the value does not matter
			$html .= wfMsg('smw_sbv_docu') . "\n";
		} elseif ('' == $valuestring) { // no value given
			$html .= wfMSG('smw_sbv_novalue', $skin->makeLinkObj($attributetitle, $attributetitle->mTextform));
		} else { // everything is given
			$unit = NULL;
			$type = NULL;
			$value = NULL;
			// set unit and (XSD) value
			$datavalue = SMWDataValue::newAttributeValue($attribute);
			if ( $datavalue->getTypeID() != 'error') {
				// TODO: Performance (medium): setUserValue() calls the data type's processValue() which does a lot of conversion and tooltip work that's unused here.
				$datavalue->setUserValue($valuestring);
				if ( $datavalue->isValid() === false ) {
					// try to use unparsable values as units
					$unit=$value;
					$value=NULL;
				} else {
					$unit = $datavalue->getUnit();
					$value  = $datavalue->getXSDValue();
				}
			}

			$res = smwfGetAttributes(NULL, $attributetitle, $unit, NULL, $value, false); // do not care about the typeid here
			// TODO change to new SMW_Store asap
			//$results = &smwfGetStore()->getRelationSubjects($relation, $object, $limit+1, $offset);
			$count = count($res);


			$html .= "<p>&nbsp;</p>\n" . wfMsg('smw_sbv_displayresult', $skin->makeLinkObj($attributetitle, $attributetitle->mTextform), $valuestring) . "<br />\n";

			// prepare navigation bar
			if ($offset > 0)
				$navigation = '<a href="' . htmlspecialchars($skin->makeSpecialUrl('SearchByAttribute','offset=' . max(0,$offset-$limit) . '&limit=' . $limit . '&attribute=' . urlencode($attribute) .'&value=' . urlencode($valuestring))) . '">' . wfMsg('smw_result_prev') . '</a>';
			else
				$navigation = wfMsg('smw_result_prev');

			$navigation .= '&nbsp;&nbsp;&nbsp;&nbsp; <b>' . wfMsg('smw_result_results') . ' ' . ($offset+1) . '&ndash; ' . ($offset + min($count, 20)) . '</b>&nbsp;&nbsp;&nbsp;&nbsp;';

			if ($count>$limit)
				$navigation .= ' <a href="' . htmlspecialchars($skin->makeSpecialUrl('SearchByAttribute', 'offset=' . ($offset+$limit) . '&limit=' . $limit . '&attribute=' . urlencode($attribute) . '&value=' . urlencode($valuestring)))  . '">' . wfMsg('smw_result_next') . '</a>';
			else
				$navigation .= wfMsg('smw_result_next');

			$max = false; $first=true;
			foreach (array(20,50,100,250,500) as $l) {
				if ($max) continue;
				if ($first) {
					$navigation .= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;(';
					$first = false;
				} else
					$navigation .= ' | ';
				if ($l > $smwgIQMaxLimit) {
					$l = $smwgIQMaxLimit;
					$max = true;
				}
				if ( $limit != $l ) {
					$navigation .= '<a href="' . htmlspecialchars($skin->makeSpecialUrl('SearchByAttribute','offset=' . $offset . '&limit=' . $l . '&attribute=' . urlencode($attribute) . '&value=' . urlencode($valuestring))) . '">' . $l . '</a>';
				} else {
					$navigation .= '<b>' . $l . '</b>';
				}
			}
			$navigation .= ')';

			// The navigation display is currently disabled, as long as the new
			// storage abstraction layer is not available.

			// no need to show the navigation bars when there is not enough to navigate
			///if (($offset>0) || ($count>$limit))
				///$html .= '<br />' . $navigation;
			if ($count == 0)
				$html .= wfMsg( 'smw_result_noresults' );
			else {
				$html .= "<ul>\n";
				foreach ($res as $line) {
					$t = Title::newFromID($line[0]);
					$html .= "<li>" . $skin->makeKnownLink($t->getPrefixedText()) . "</li>\n";
				}
				$html .= "</ul>\n";
			}
			///if (($offset>0) || ($count>$limit))
				///$html .= $navigation;
		}

		$wgOut->addHTML($html);
	}

}
?>

<?php
/**
 * @author Markus Krötzsch
 *
 * This special page for MediaWiki implements a customisable form for
 * executing queries outside of articles.
 */

if (!defined('MEDIAWIKI')) die();

//require_once($smwgIP . '/includes/SMW_Storage.php');
global $IP;
require_once( "$IP/includes/SpecialPage.php" );
global $wgExtensionFunctions;
$wgExtensionFunctions[] = "wfAskExtension";

// standard functions for creating a new special
function wfAskExtension() {
	smwfInitMessages(); // initialize messages, always called before anything else on this page
	
	function doSpecialAsk() {
		SMW_AskPage::execute();
	}
	
	SpecialPage::addPage( new SpecialPage('Ask','',true,'doSpecialAsk',false) );
}


class SMW_AskPage {

	static function execute() {
		global $wgRequest, $wgOut, $smwgIQEnabled, $smwgIQMaxLimit, $wgUser, $smwgIQSortingEnabled;
		$skin = $wgUser->getSkin();

		$query = $wgRequest->getVal( 'query' );
		$sort = $wgRequest->getVal( 'sort' );
		$order = $wgRequest->getVal( 'order' );
		$limit = $wgRequest->getVal( 'limit' );
		if ('' == $limit) $limit =  20; //$smwgIQDefaultLimit;
		$offset = $wgRequest->getVal( 'offset' );
		if ('' == $offset) $offset = 0;

		// display query form
		$spectitle = Title::makeTitle( NS_SPECIAL, 'Ask' );		
		$docutitle = Title::newFromText(wfMsg('smw_ask_doculink'), NS_HELP);
		$html = wfMsg('smw_ask_docu', $docutitle->getFullURL()) . "\n" .
				'<form name="ask" action="' . $spectitle->escapeLocalURL() . '" method="get">' . "\n";
		$html .= '<textarea name="query" cols="40" rows="6">' . htmlspecialchars($query) . '</textarea><br />' . "\n";
		
		if ($smwgIQSortingEnabled) {
			$html .=  wfMsg('smw_ask_sortby') . ' <input type="text" name="sort" value="' .
					htmlspecialchars($sort) . '"/> <select name="order"><option ';
			// TODO: don't show sort widgets if sorting is not enabled
			if ($order == 'ASC') $html .= 'selected="selected" ';
			$html .=  'value="ASC">' . wfMsg('smw_ask_ascorder') . '</option><option ';
			if ($order == 'DESC') $html .= 'selected="selected" ';
			$html .=  'value="DESC">' . wfMsg('smw_ask_descorder') . '</option></select> <br />';
		}
		$html .= '<br /><input type="submit" value="' . wfMsg('smw_ask_submit') . "\"/>\n</form>";
		
		// print results if any
		if ($smwgIQEnabled && ('' != $query) ) {
			$iq = new SMWInlineQuery(array('offset' => $offset, 'limit' => $limit, 'format' => 'broadtable', 'mainlabel' => ' ', 'link' => 'all', 'default' => wfMsg('smw_ask_noresults'), 'sort' => $sort, 'order' => $order), false);
			$result = $iq->getHTMLResult($query);

			// prepare navigation bar
			if ($offset > 0) 
				$navigation = '<a href="' . $skin->makeSpecialUrl('Ask','offset=' . max(0,$offset-$limit) . '&limit=' . $limit . '&query=' . urlencode($query) . '&sort=' . urlencode($sort) .'&order=' . urlencode($order)) . '">' . wfMsg('smw_ask_prev') . '</a>';
			else $navigation = wfMsg('smw_ask_prev');

			$navigation .= '&nbsp;&nbsp;&nbsp;&nbsp; <b>' . wfMsg('smw_ask_results') . ' ' . ($offset+1) . '&ndash; ' . ($offset + $iq->getDisplayCount()) . '</b>&nbsp;&nbsp;&nbsp;&nbsp;';

			if ($iq->hasFurtherResults()) 
				$navigation .= ' <a href="' . $skin->makeSpecialUrl('Ask','offset=' . ($offset+$limit) . '&limit=' . $limit . '&query=' . urlencode($query) . '&sort=' . urlencode($sort) .'&order=' . urlencode($order)) . '">' . wfMsg('smw_ask_next') . '</a>';
			else $navigation .= wfMsg('smw_ask_next');

			$max = false; $first=true;
			foreach (array(20,50,100,250,500) as $l) {
				if ($max) continue;
				if ($first) {
					$navigation .= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;(';
					$first = false;
				} else $navigation .= ' | ';
				if ($l > $smwgIQMaxLimit) {
					$l = $smwgIQMaxLimit;
					$max = true;
				}
				if ( $limit != $l ) {
					$navigation .= '<a href="' . $skin->makeSpecialUrl('Ask','offset=' . $offset . '&limit=' . $l . '&query=' . urlencode($query) . '&sort=' . urlencode($sort) .'&order=' . urlencode($order)) . '">' . $l . '</a>';
				} else {
					$navigation .= '<b>' . $l . '</b>';
				}
			}
			$navigation .= ')';

			$html .= '<br /><div style="text-align: center;">' . $navigation;
			$html .= '<br />' . $result;
			$html .= '<br />' . $navigation . '</div>';
		} elseif (!$smwgIQEnabled) {
			$html .= '<br />' . wfMsgForContent('smw_iq_disabled');
		}
		$wgOut->addHTML($html);
	}

}

?>

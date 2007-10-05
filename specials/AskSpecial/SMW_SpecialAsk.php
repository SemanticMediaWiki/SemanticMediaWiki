<?php

if (!defined('MEDIAWIKI')) die();

global $IP;
include_once($IP . '/includes/SpecialPage.php');

/**
 * @author Markus Krötzsch
 *
 * This special page for MediaWiki implements a customisable form for
 * executing queries outside of articles.
 *
 * @note AUTOLOAD
 */
class SMWAskPage extends SpecialPage {

	/**
	 * Constructor
	 */
	public function __construct() {
		smwfInitUserMessages();
		parent::__construct('Ask');
	}

	function execute() {
		wfProfileIn('doSpecialAsk (SMW)');
		global $wgRequest, $wgOut, $smwgQEnabled, $smwgQMaxLimit, $wgUser, $smwgQSortingSupport, $smwgIP;

		$skin = $wgUser->getSkin();

		$query = $wgRequest->getVal( 'query' );
		$sort  = $wgRequest->getVal( 'sort' );
		$order = $wgRequest->getVal( 'order' );
		$limit = $wgRequest->getVal( 'limit' );
		if ('' == $limit) $limit =  20;
		$offset = $wgRequest->getVal( 'offset' );
		if ('' == $offset) $offset = 0;

		// display query form
		$spectitle = Title::makeTitle( NS_SPECIAL, 'Ask' );		
		$docutitle = Title::newFromText(wfMsg('smw_ask_doculink'), NS_HELP);
		$html = wfMsg('smw_ask_docu', $docutitle->getFullURL()) . "\n";
		$html .= '<form name="ask" action="' . $spectitle->escapeLocalURL() . '" method="get">' . "\n" .
		         '<input type="hidden" name="title" value="' . $spectitle->getPrefixedText() . '"/>' ;
		$html .= '<textarea name="query" cols="40" rows="6">' . htmlspecialchars($query) . '</textarea><br />' . "\n";
		
		if ($smwgQSortingSupport) {
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
		if ($smwgQEnabled && ('' != $query) ) {
			include_once( "$smwgIP/includes/SMW_QueryProcessor.php" );
			$params = array('offset' => $offset, 'limit' => $limit, 'format' => 'broadtable', 'mainlabel' => ' ', 'link' => 'all', 'default' => wfMsg('smw_result_noresults'), 'sort' => $sort, 'order' => $order);
			$queryobj = SMWQueryProcessor::createQuery($query, $params, false);
			$res = smwfGetStore()->getQueryResult($queryobj);
			$printer = new SMWTableResultPrinter('broadtable',false);
			$result = $printer->getResultHTML($res, $params);

			// prepare navigation bar
			if ($offset > 0) 
				$navigation = '<a href="' . htmlspecialchars($skin->makeSpecialUrl('Ask','offset=' . max(0,$offset-$limit) . '&limit=' . $limit . '&query=' . urlencode($query) . '&sort=' . urlencode($sort) .'&order=' . urlencode($order))) . '">' . wfMsg('smw_result_prev') . '</a>';
			else $navigation = wfMsg('smw_result_prev');

			$navigation .= '&nbsp;&nbsp;&nbsp;&nbsp; <b>' . wfMsg('smw_result_results') . ' ' . ($offset+1) . '&ndash; ' . ($offset + $res->getCount()) . '</b>&nbsp;&nbsp;&nbsp;&nbsp;';

			if ($res->hasFurtherResults()) 
				$navigation .= ' <a href="' . htmlspecialchars($skin->makeSpecialUrl('Ask','offset=' . ($offset+$limit) . '&limit=' . $limit . '&query=' . urlencode($query) . '&sort=' . urlencode($sort) .'&order=' . urlencode($order))) . '">' . wfMsg('smw_result_next') . '</a>';
			else $navigation .= wfMsg('smw_result_next');

			$max = false; $first=true;
			foreach (array(20,50,100,250,500) as $l) {
				if ($max) continue;
				if ($first) {
					$navigation .= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;(';
					$first = false;
				} else $navigation .= ' | ';
				if ($l > $smwgQMaxLimit) {
					$l = $smwgQMaxLimit;
					$max = true;
				}
				if ( $limit != $l ) {
					$navigation .= '<a href="' . htmlspecialchars($skin->makeSpecialUrl('Ask','offset=' . $offset . '&limit=' . $l . '&query=' . urlencode($query) . '&sort=' . urlencode($sort) .'&order=' . urlencode($order))) . '">' . $l . '</a>';
				} else {
					$navigation .= '<b>' . $l . '</b>';
				}
			}
			$navigation .= ')';

			$html .= '<br /><div style="text-align: center;">' . $navigation;
			$html .= '<br />' . $result;
			$html .= '<br />' . $navigation . '</div>';
		} elseif (!$smwgQEnabled) {
			$html .= '<br />' . wfMsgForContent('smw_iq_disabled');
		}
		$wgOut->addHTML($html);
		wfProfileOut('doSpecialAsk (SMW)');
	}

}



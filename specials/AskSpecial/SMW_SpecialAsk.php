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

	function execute($p = '') {
		// Processing steps:
		// * decode all parameters into raw parameter array as received by #ask
		// * use SMWQueryProcessor to parse parameters into params, querystring, extraprintouts
		// * overwrite limit
		// * overwrite certain formatting directives? (use broadtable unless format is "template", "ul", "ol", "embedded")
		// * UI: display textfields for: query, printouts (one per line?), parameters (one per line?)
		// * UI: display results of query if given

		global $wgOut, $wgRequest, $smwgIP, $smwgQEnabled, $smwgQMaxLimit;
		$querystring    = $wgRequest->getVal( 'q' );
		$paramstring    = $wgRequest->getVal( 'p' ) . $wgRequest->getVal( 'po' );
		$limit          = $wgRequest->getVal( 'limit' );
		$offset         = $wgRequest->getVal( 'offset' );
		if ( ($p == '') &&  ($querystring == '') ) { // old processing
			$this->executSimpleAsk();
			return;
		}
		wfProfileIn('doSpecialAsk (SMW)');

		// First make all those inputs into a simple parameter list that can again be parsed into components later
		$rawparams = array();
		if ($querystring != '') {
			$rawparams[] = $querystring;
		}
		if ($paramstring != '') {
			$ps = explode("\n", $paramstring); // params separated by newlines
			foreach ($ps as $param) {
				$rawparams[] = $param;
			}
		}
		if ($p != '') { // extract query from $p
			// unescape $p; escaping scheme: all parameters rawurlencoded, "-" and "/" urlencoded, all "%" replaced by "-", parameters then joined with /
			$ps = explode('/', $p);
			foreach ($ps as $param) {
				$rawparams[] = rawurldecode(str_replace('-', '%', $param));
			}
		}
		if ('' == $limit) $limit =  20;
		if ('' == $offset) $offset = 0;
		
		// Now parse parameters and rebuilt the param strings
		include_once( "$smwgIP/includes/SMW_QueryProcessor.php" );
		SMWQueryProcessor::processFunctionParams($rawparams,$querystring,$params,$printouts);
		$paramstring = '';
		foreach ($params as $key => $value) {
			if ( !in_array($key,array('format', 'limit', 'offset')) ) {
				$paramstring .= "$key=$value\n";
			}
		}
		$paramstring = str_replace('=','%3D',$paramstring);
		$printoutstring = '';
		foreach ($printouts as $printout) {
			$printoutstring .= $printout->getSerialisation() . "\n";
		}
		$params['format'] = 'broadtable';
		$params['limit']  = $limit;
		$params['offset'] = $offset;
	
		// Finally process query and build some HTML output
		$html = '';
		if ($smwgQEnabled && ('' != $querystring) ) { // print results if any
			global $wgUser;
			$skin = $wgUser->getSkin();
			$queryobj = SMWQueryProcessor::createQuery($querystring, $params, false, '', $printouts);
			$res = smwfGetStore()->getQueryResult($queryobj);

			// prepare navigation bar
			if ($offset > 0) 
				$navigation = '<a href="' . htmlspecialchars($skin->makeSpecialUrl('Ask','offset=' . max(0,$offset-$limit) . '&limit=' . $limit . '&q=' . urlencode($querystring) . '&p=' . urlencode($paramstring) .'&po=' . urlencode($printoutstring))) . '">' . wfMsg('smw_result_prev') . '</a>';
			else $navigation = wfMsg('smw_result_prev');

			$navigation .= '&nbsp;&nbsp;&nbsp;&nbsp; <b>' . wfMsg('smw_result_results') . ' ' . ($offset+1) . '&ndash; ' . ($offset + $res->getCount()) . '</b>&nbsp;&nbsp;&nbsp;&nbsp;';

			if ($res->hasFurtherResults()) 
				$navigation .= ' <a href="' . htmlspecialchars($skin->makeSpecialUrl('Ask','offset=' . ($offset+$limit) . '&limit=' . $limit . '&q=' . urlencode($querystring) . '&p=' . urlencode($paramstring) .'&po=' . urlencode($printoutstring))) . '">' . wfMsg('smw_result_next') . '</a>';
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
					$navigation .= '<a href="' . htmlspecialchars($skin->makeSpecialUrl('Ask','offset=' . $offset . '&limit=' . $l . '&q=' . urlencode($querystring) . '&p=' . urlencode($paramstring) .'&po=' . urlencode($printoutstring))) . '">' . $l . '</a>';
				} else {
					$navigation .= '<b>' . $l . '</b>';
				}
			}
			$navigation .= ')';

			$printer = SMWQueryProcessor::getResultPrinter('broadtable',false,$res);
			//$result = $printer->getResult($res, $params,SMW_OUTPUT_HTML);
			//$result = SMWQueryProcessor::getResultFromQueryString($querystring,$params,$printouts, SMW_OUTPUT_HTML, false);
			$html .= '<br /><div style="text-align: center;">' . $navigation;
			$html .= '<br />' . $printer->getResult($res, $params,SMW_OUTPUT_HTML);
			$html .= '<br />' . $navigation . '</div>';
		} elseif (!$smwgQEnabled) {
			$html .= '<br />' . wfMsgForContent('smw_iq_disabled');
		}
		$wgOut->addHTML($html);
		wfProfileOut('doSpecialAsk (SMW)');
	}
	
	/**
	 * Exectues ask-interface as done in SMW<=0.7, using a simple textbox interface and supporting only
	 * certain parameters.
	 */
	protected function executSimpleAsk() {
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



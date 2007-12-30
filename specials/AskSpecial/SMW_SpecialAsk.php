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

	protected $m_querystring = '';
	protected $m_params = array();
	protected $m_printouts = array();
	protected $m_editquery = false;
	protected $m_rssoutput = false;

	/**
	 * Constructor
	 */
	public function __construct() {
		smwfInitUserMessages();
		parent::__construct('Ask');
	}

	function execute($p = '') {
		global $wgOut, $wgRequest, $smwgIP, $smwgQEnabled, $smwgRSSEnabled;
		wfProfileIn('doSpecialAsk (SMW)');
		if ( ($wgRequest->getVal( 'query' ) != '') ) { // old processing
			$this->executSimpleAsk();
			wfProfileOut('doSpecialAsk (SMW)');
			return;
		}
		if (!$smwgQEnabled) {
			$wgOut->addHTML('<br />' . wfMsgForContent('smw_iq_disabled'));
			wfProfileOut('doSpecialAsk (SMW)');
			return;
		}

		$this->extractQueryParameters($p);

		if ($this->m_rssoutput) {
			if ($smwgRSSEnabled && ('' != $this->m_querystring )) {
				$this->makeRSSResult();
			} else {
				// nothing at the moment
			}
		} else { // HTML output
			$paramstring = '';
			foreach ($this->m_params as $key => $value) {
				if ( in_array($key,array('format', 'template')) ) {
					$paramstring .= "$key=$value\n";
				}
			}
			$paramstring = str_replace('=','%3D',$paramstring);
			$printoutstring = '';
			foreach ($this->m_printouts as $printout) {
				$printoutstring .= $printout->getSerialisation() . "\n";
			}
			$urltail = '&q=' . urlencode($this->m_querystring) . '&p=' . urlencode($paramstring) .'&po=' . urlencode($printoutstring);
			if ('' != $this->m_params['sort'])  $urltail .= '&sort=' . $this->m_params['sort'];
			if ('' != $this->m_params['order']) $urltail .= '&order=' . $this->m_params['order'];

			// Print input form (or links to display it)
			$this->makeInputForm($printoutstring, 'offset=' . $this->m_params['offset'] . '&limit=' . $this->m_params['limit'] . $urltail);
	
			// Finally process query and build more HTML output
			if ('' != $this->m_querystring ) { // print results if any
				$this->makeHTMLResult($urltail);
			}
		}
		wfProfileOut('doSpecialAsk (SMW)');
	}

	protected function extractQueryParameters($p) {
		global $wgRequest, $smwgIP;
		$this->m_querystring = $wgRequest->getVal( 'q' );
		$paramstring         = $wgRequest->getVal( 'p' ) . $wgRequest->getVal( 'po' );

		// First make all those inputs into a simple parameter list that can again be parsed into components later
		$rawparams = array();
		if ($this->m_querystring != '') {
			$rawparams[] = $this->m_querystring;
		}
		if ($paramstring != '') { // parameters from HTML input fields
			$ps = explode("\n", $paramstring); // params separated by newlines here (compatible with text-input for printouts)
			foreach ($ps as $param) { // add initial ? if omitted (all params considered as printouts)
				$param = trim($param);
				if ( ($param != '') && ($param{0} != '?') ) {
					$param = '?' . $param;
				}
				$rawparams[] = $param;
			}
		}
		if ($p != '') { // parameters from wiki-compatible URL encoding (further results etc.)
			// unescape $p; escaping scheme: all parameters rawurlencoded, "-" and "/" urlencoded, all "%" replaced by "-", parameters then joined with /
			$ps = explode('/', $p); // params separated by / here (compatible with wiki link syntax)
			foreach ($ps as $param) {
				$rawparams[] = rawurldecode(str_replace('-', '%', $param));
			}
		}

		// Now parse parameters and rebuilt the param strings for URLs
		include_once( "$smwgIP/includes/SMW_QueryProcessor.php" );
		SMWQueryProcessor::processFunctionParams($rawparams,$this->m_querystring,$this->m_params,$this->m_printouts);
		$this->m_rssoutput = (array_key_exists('rss', $this->m_params));

		// Try to complete undefined parameter values from dedicated URL params
		if ( !array_key_exists('order',$this->m_params) ) {
			$this->m_params['order'] = $wgRequest->getVal( 'order' );
		}
		if ( !array_key_exists('sort',$this->m_params) ) {
			$this->m_params['sort'] = $wgRequest->getVal( 'sort' );
		}
		if ( !array_key_exists('limit',$this->m_params) ) {
			$this->m_params['limit'] = $wgRequest->getVal( 'limit' );
			if ($this->m_params['limit'] == '') {
				 $this->m_params['limit'] = $this->m_rssoutput?10:20; // standard limit for RSS is 10
			}
		}
		if ( !array_key_exists('offset',$this->m_params) ) {
			$this->m_params['offset'] = $wgRequest->getVal( 'offset' );
			if ($this->m_params['offset'] == '')  $this->m_params['offset'] = 0;
		}
		$this->m_params['format'] = 'broadtable';

		$this->m_editquery = ( $wgRequest->getVal( 'eq' ) != '' ) || ('' == $this->m_querystring );
	}

	protected function makeInputForm($printoutstring, $urltail) {
		global $wgUser, $smwgQSortingSupport, $wgOut;
		$skin = $wgUser->getSkin();
		$result = '';
		if ($this->m_editquery) {
			$spectitle = Title::makeTitle( NS_SPECIAL, 'Ask' );
			$docutitle = Title::newFromText(wfMsg('smw_ask_doculink'), NS_HELP);
			$result .= '<form name="ask" action="' . $spectitle->escapeLocalURL() . '" method="get">' . "\n" .
			           '<input type="hidden" name="title" value="' . $spectitle->getPrefixedText() . '"/>';
			$result .= '<table style="width: 100%; "><tr><th>' . wfMsg('smw_ask_queryhead') . '</th><th>' . wfMsg('smw_ask_printhead') . '</th></tr>' .
			         '<tr><td><textarea name="q" cols="20" rows="6">' . htmlspecialchars($this->m_querystring) . '</textarea></td>' .
			         '<td><textarea name="po" cols="20" rows="6">' . htmlspecialchars($printoutstring) . '</textarea></td></tr></table>' . "\n";
			if ($smwgQSortingSupport) {
				$result .=  wfMsg('smw_ask_sortby') . ' <input type="text" name="sort" value="' .
				            htmlspecialchars($this->m_params['sort']) . '"/> <select name="order"><option ';
				if ($this->m_params['order'] == 'ASC') $result .= 'selected="selected" ';
				$result .=  'value="ASC">' . wfMsg('smw_ask_ascorder') . '</option><option ';
				if ($this->m_params['order'] == 'DESC') $result .= 'selected="selected" ';
				$result .=  'value="DESC">' . wfMsg('smw_ask_descorder') . '</option></select> <br />';
			}
			$result .= '<br /><input type="submit" value="' . wfMsg('smw_ask_submit') . '"/>' .
			           '<input type="hidden" name="eq" value="yes"/>' . 
			           ' <a href="' . htmlspecialchars($skin->makeSpecialUrl('Ask',$urltail)) . '">' . wfMsg('smw_ask_hidequery') . '</a> | <a href="' . $docutitle->getFullURL() . '">' . wfMsg('smw_ask_help') . '</a>' .
			           "\n</form><br />";
			$urltail .= '&eq=yes';
		} else {
			$result .= '<p><a href="' . htmlspecialchars($skin->makeSpecialUrl('Ask',$urltail . '&eq=yes')) . '">' . wfMsg('smw_ask_editquery') . '</a></p>';
		}
		$wgOut->addHTML($result);
	}

	protected function makeHTMLResult($urltail) {
		global $wgUser, $smwgQMaxLimit, $wgOut;
		$queryobj = SMWQueryProcessor::createQuery($this->m_querystring, $this->m_params, false, '', $this->m_printouts);
		$res = smwfGetStore()->getQueryResult($queryobj);
		$skin = $wgUser->getSkin();
		$offset = $this->m_params['offset'];
		$limit  = $this->m_params['limit'];
		// prepare navigation bar
		if ($offset > 0) 
			$navigation = '<a href="' . htmlspecialchars($skin->makeSpecialUrl('Ask','offset=' . max(0,$offset-$limit) . '&limit=' . $limit . $urltail)) . '">' . wfMsg('smw_result_prev') . '</a>';
		else $navigation = wfMsg('smw_result_prev');

		$navigation .= '&nbsp;&nbsp;&nbsp;&nbsp; <b>' . wfMsg('smw_result_results') . ' ' . ($offset+1) . '&ndash; ' . ($offset + $res->getCount()) . '</b>&nbsp;&nbsp;&nbsp;&nbsp;';

		if ($res->hasFurtherResults()) 
			$navigation .= ' <a href="' . htmlspecialchars($skin->makeSpecialUrl('Ask','offset=' . ($offset+$limit) . '&limit=' . $limit . $urltail)) . '">' . wfMsg('smw_result_next') . '</a>';
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
				$navigation .= '<a href="' . htmlspecialchars($skin->makeSpecialUrl('Ask','offset=' . $offset . '&limit=' . $l . $urltail)) . '">' . $l . '</a>';
			} else {
				$navigation .= '<b>' . $l . '</b>';
			}
		}
		$navigation .= ')';

		$printer = SMWQueryProcessor::getResultPrinter('broadtable',false,$res);
		$result = '<div style="text-align: center;">' . $navigation;
		$result .= '<br />' . $printer->getResult($res, $this->m_params,SMW_OUTPUT_HTML);
		$result .= '<br />' . $navigation . '</div>';
		$wgOut->addHTML($result);
	}

	protected function makeRSSResult() {
		global $wgOut, $wgRequest, $wgServer, $wgSitename;
		$wgOut->disable();
		$newprintouts = array(); // filter printouts
		foreach ($this->m_printouts as $printout) {
			if (strtolower($printout->getLabel()) == "creator") {
				$newprintouts[] = $printout;
			}
			if ((strtolower($printout->getLabel()) == "date") and ($printout->getTypeID() == "_dat")) {
				$newprintouts[] = $printout;
				$this->m_params['sort'] = $printout->getTitle()->getText();
				$this->m_params['order'] = 'DESC';
			}
		}
		$this->m_printouts = $newprintouts;

		$queryobj = SMWQueryProcessor::createQuery($this->m_querystring, $this->m_params, false, '', $this->m_printouts);
		$res = smwfGetStore()->getQueryResult($queryobj);

		if (!array_key_exists('rsstitle', $this->m_params)) {
			$this->m_params['rsstitle'] = $wgSitename;
		}
		if (!array_key_exists('rssdescription', $this->m_params)) {
			$this->m_params['rssdescription'] = wfMsg('smw_rss_description', $wgSitename);
		}

		$items = array();
		$row = $res->getNext();
		while ( $row !== false ) {
			$creator = array();
			$date = array();
			$wikipage = $row[0]->getNextObject(); // get the object
			foreach ($row as $result) {
				// for now we ignore everything but creator and date, later we may
				// add more things like geolocs, categories, and even a generic
				// mechanism to add whatever you want :)
				$req = $result->getPrintRequest();
				if (strtolower($req->getLabel()) == "creator") {
					$content = $result->getContent();
					foreach ($content as $entry) {
						$creator[] = $entry->getShortWikiText();
					}
				}
				if (strtolower($req->getLabel()) == "date") {
					$content = $result->getContent();
					foreach ($content as $entry) {
						$date[] = $entry->getShortWikiText();
					}
				}
			}
// 			print "Trying " . $wikipage->getShortWikiText() . '...'; // Debug
			$items[] = new SMWRSSEntry($wikipage->getTitle(), $creator, $date);
			$row = $res->getNext();
		}
			
		$text  = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
		$text .= "<rdf:RDF\n";
		$text .= "\txmlns:rdf=\"http://www.w3.org/1999/02/22-rdf-syntax-ns#\"\n";
		$text .= "\txmlns:content=\"http://purl.org/rss/1.0/modules/content/\"\n";
		$text .= "\txmlns:admin=\"http://webns.net/mvcb/\"\n";
		$text .= "\txmlns:dc=\"http://purl.org/dc/elements/1.1/\"\n";
		$text .= "\txmlns=\"http://purl.org/rss/1.0/\">\n";
		$text .= "\t<channel rdf:about=\"" . $wgRequest->getFullRequestURL() . "\">\n";
		$text .= "\t\t<admin:generatorAgent rdf:resource=\"http://ontoworld.org/wiki/Special:URIResolver/Semantic_MediaWiki\"/>\n";
		$text .= "\t\t<title>" . $this->m_params['rsstitle'] . "</title>\n";
		$text .= "\t\t<link>$wgServer</link>\n";
		$text .= "\t\t<description>" . $this->m_params['rssdescription'] . "</description>\n";
		if (count($items) > 0) {
			$text .= "\t\t<items>\n";
			$text .= "\t\t\t<rdf:Seq>\n";
			foreach($items as $item) {
				$text .= "\t\t\t\t<rdf:li rdf:resource=\"" . $item->uri() . "\"/>\n";
			}
			$text .= "\t\t\t</rdf:Seq>\n";
			$text .= "\t\t</items>\n";
		}
		$text .= "\t</channel>\n";
		foreach ($items as $item) {
			$text .= $item->text();
		}
		$text .= "</rdf:RDF>";

		print $text;
	}


	/**
	 * Exectues ask-interface as done in SMW<=0.7, using a simple textbox interface and supporting only
	 * certain parameters.
	 */
	protected function executSimpleAsk() {
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
		$html = '<form name="ask" action="' . $spectitle->escapeLocalURL() . '" method="get">' . "\n" .
		         '<input type="hidden" name="title" value="' . $spectitle->getPrefixedText() . '"/>' ;
		$html .= '<textarea name="query" cols="40" rows="6">' . htmlspecialchars($query) . '</textarea><br />' . "\n";
		
		if ($smwgQSortingSupport) {
			$html .=  wfMsg('smw_ask_sortby') . ' <input type="text" name="sort" value="' .
			          htmlspecialchars($sort) . '"/> <select name="order"><option ';
			if ($order == 'ASC') $html .= 'selected="selected" ';
			$html .=  'value="ASC">' . wfMsg('smw_ask_ascorder') . '</option><option ';
			if ($order == 'DESC') $html .= 'selected="selected" ';
			$html .=  'value="DESC">' . wfMsg('smw_ask_descorder') . '</option></select> <br />';
		}
		$html .= '<br /><input type="submit" value="' . wfMsg('smw_ask_submit') . '"/> <a href="' . $docutitle->getFullURL() . '">' . wfMsg('smw_ask_help') . "</a>\n</form>";
		
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
	}

}


/**
 * Represents a single entry, or item, in the feed.
 */
class SMWRSSEntry {

	private $uri;
	private $label;
	private $creator;
	private $date;
	private $articlename;
	private $title;

	/**
	 * Constructor for a single item in the feed. Requires the URI of the item.
	 */
	public function SMWRSSEntry(Title $t, $c, $d) {
		global $wgServer;
		$this->title = $t;
		$this->uri = $t->getFullURL();
		$this->label = $t->getText();
		$article = null;
		if (count($c)==0) {
			$article = new Article($t);
			$this->creator = array();
			$this->creator[] = $article->getUserText();
		} else {
			$this->creator = $c;
		}
		$this->date = array();
		if (count($d)==0) {
			if ($article === null) {
				$article = new Article($t);
			}
			$this->date[] = date("c", strtotime($article->getTimestamp()));
		} else {
			foreach ($d as $date) {
				$this->date[] = date("c", strtotime($date));
			}
		}

		// get content
		if ($t->getNamespace() == NS_MAIN) {
			$this->articlename = ':' . $t->getDBKey();
		} else {
			$this->articlename = $t->getPrefixedDBKey();
		}
	}

	/**
	 * Get function for the Item URI
	 */
	public function uri() {
		return $this->uri;
	}
	
	/**
	 * Creates the RSS output for the single item.
	 */
	public function text() {
		global $wgTitle, $wgServer, $wgParser, $smwgStoreActive, $smwgRSSWithPages;
		static $parser = null;
		static $parser_options = null;
		$smwgStoreActive = false; // make sure no Factbox is shown (RSS lacks the required styles)
		// do not bother to restore this later, not needed in this context

		$text  = "\t<item rdf:about=\"$this->uri\">\n";
		$text .= "\t\t<title>$this->label</title>\n";
		$text .= "\t\t<link>$this->uri</link>\n";
		foreach ($this->date as $date)
			$text .= "\t\t<dc:date>$date</dc:date>\n";
		foreach ($this->creator as $creator)
			$text .= "\t\t<dc:creator>" . smwfXMLContentEncode($creator) . "</dc:creator>\n";
		if ($smwgRSSWithPages) {
			if ($parser == null) {
				$parser_options = new ParserOptions();
				$parser_options->setEditSection(false);  // embedded sections should not have edit links
				$parser = clone $wgParser;
			}
			$parserOutput = $parser->parse('{{' . $this->articlename . '}}', $this->title, $parser_options);
			$content = $parserOutput->getText();
			$content = str_replace('<a href="/', '<a href="' . $wgServer . '/', $content);
			// This makes absolute URLs out of the local ones
			///TODO is there maybe a way in the parser options to make the URLs absolute?
			$text .= "\t\t<description>" . $this->clean($content) . "</description>\n";
			$text .= "\t\t<content:encoded  rdf:datatype=\"http://www.w3.org/1999/02/22-rdf-syntax-ns#XMLLiteral\"><![CDATA[$content]]></content:encoded>\n";
		}
		$text .= "\t</item>\n";
		return $text;
	}

	/**
	 * Descriptions are unescaped simple text. The content is given in HTML. This should
	 * clean the description.
	 */
	private function clean($t) {
		return trim(smwfXMLContentEncode($t, null, 'UTF-8'));
		//return trim(str_replace(array('&','<','>'), array('&amp;','&lt;','&gt;'), strip_tags(html_entity_decode($t, null, 'UTF-8')))); 
	}
}


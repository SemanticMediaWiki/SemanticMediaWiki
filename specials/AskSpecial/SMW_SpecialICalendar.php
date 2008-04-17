<?php

if (!defined('MEDIAWIKI')) die();

/**
 * TODO
 * 
 * This page copies a lot of code from SMW_SpecialAsk.php, which sucks high
 * time. But we cannot keep adding such exporters to the SMW_SpeciaAsk file
 * (like the RSS exporter), it is easy to think of further exporters like a
 * for KML,  VCard,  Bibtext, and heck, even Java,  Python, or such (anyone
 * cares about  distributed literate programming?). So  we  obviously  need
 * some structure  to allow us to make  this kind of exporters without such
 * spurious repetition,  and then refactor the RSS and iCalendar code to it
 */

global $IP;
include_once($IP . '/includes/SpecialPage.php');

/**
 * @author Markus Krötzsch
 * @author Denny Vrandecic
 *
 * This special page for MediaWiki implements an exporter for iCalendar data
 *
 * @note AUTOLOAD
 */
class SMWICalendarPage extends SpecialPage {

	protected $m_querystring = '';
	protected $m_params = array();
	protected $m_printouts = array();

	/**
	 * Constructor
	 */
	public function __construct() {
		smwfInitUserMessages();
		parent::__construct('ICalendar', '', false);
	}

	function execute($query = '') {
		global $wgOut, $wgRequest, $smwgIP, $smwgQEnabled, $smwgRSSEnabled;
		wfProfileIn('doSpecialICalendar (SMW)');
		if ( ($wgRequest->getVal( 'query' ) != '') or ( $query == '') ) { // old processing
			$wgOut->addHTML('<br /> This is the special page for iCalendar exports.');
			wfProfileOut('doSpecialICalendar (SMW)');
			return;
		}
		if (!$smwgQEnabled) {
			$wgOut->addHTML('<br />' . wfMsgForContent('smw_iq_disabled'));
			wfProfileOut('doSpecialICalendar (SMW)');
			return;
		}

		$this->extractQueryParameters($query);

		$this->makeICalendarResult();

		wfProfileOut('doSpecialICalendar (SMW)');
	}

	protected function extractQueryParameters($p) {
		// This code rather hacky since there are many ways to call that special page, the most involved of
		// which is the way that this page calls itself when data is submitted via the form (since the shape
		// of the parameters then is governed by the UI structure, as opposed to being governed by reason).
		global $wgRequest, $smwgIP;

		// First make all inputs into a simple parameter list that can again be parsed into components later.

		// Check for title-part params, as used when special page is called via internal wiki link:
		$p .= $wgRequest->getVal( 'raw' );
		if (!$wgRequest->getCheck('q')) { // only do that for calls from outside this special page
			$rawparams = SMWInfolink::decodeParameters($p);
		} else {
			$rawparams = array();
		}
		// Check for q= query string, used whenever this special page calls itself (via submit or plain link):
		$this->m_querystring = $wgRequest->getText( 'q' );
		if ($this->m_querystring != '') {
			$rawparams[] = $this->m_querystring;
		}
		// Check for param strings in po (printouts) and p (other settings) parameters, as used in calls of
		// this special page to itself (p only appears in some links, po also in submits):
		$paramstring = $wgRequest->getVal( 'p' ) . $wgRequest->getText( 'po' );
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

		// Now parse parameters and rebuilt the param strings for URLs
		include_once( "$smwgIP/includes/SMW_QueryProcessor.php" );
		SMWQueryProcessor::processFunctionParams($rawparams,$this->m_querystring,$this->m_params,$this->m_printouts);

		// Try to complete undefined parameter values from dedicated URL params
		$sortcount = $wgRequest->getVal( 'sc' );
		if (!is_numeric($sortcount)) {
			$sortcount = 0;
		}
		if ( !array_key_exists('order',$this->m_params) ) {
			$this->m_params['order'] = $wgRequest->getVal( 'order' ); // basic ordering parameter (, separated)
			for ($i=0; $i<$sortcount; $i++) {
				if ($this->m_params['order'] != '') {
					$this->m_params['order'] .= ',';
				}
				$value = $wgRequest->getVal( 'order' . $i );
				$value = ($value == '')?'ASC':$value;
				$this->m_params['order'] .= $value;
			}
		}
		if ( !array_key_exists('sort',$this->m_params) ) {
			$this->m_params['sort'] = $wgRequest->getText( 'sort' ); // basic sorting parameter (, separated)
			for ($i=0; $i<$sortcount; $i++) {
				if ( ($this->m_params['sort'] != '') || ($i>0) ) { // admit empty sort strings here
					$this->m_params['sort'] .= ',';
				}
				$this->m_params['sort'] .= $wgRequest->getText( 'sort' . $i );
			}
		}
		if ( !array_key_exists('limit',$this->m_params) ) {
			$this->m_params['limit'] = $wgRequest->getVal( 'limit' );
			if ($this->m_params['limit'] == '') {
				 $this->m_params['limit'] = 200; // magic number TODO
			}
		}
		if ( !array_key_exists('offset',$this->m_params) ) {
			$this->m_params['offset'] = $wgRequest->getVal( 'offset' );
			if ($this->m_params['offset'] == '')  $this->m_params['offset'] = 0;
		}

	}

	protected function makeICalendarResult() {
		global $wgOut, $wgRequest, $wgServer, $wgSitename;
		$wgOut->disable();
		header( "Content-type: text/calendar" );
		$newprintouts = array(); // filter printouts
		foreach ($this->m_printouts as $printout) {
			if ((strtolower($printout->getLabel()) == "start") and ($printout->getTypeID() == "_dat")) {
				$newprintouts[] = $printout;
				//$this->m_params['sort'] = $printout->getTitle()->getText();
				//$this->m_params['order'] = 'DESC';
			}
			if ((strtolower($printout->getLabel()) == "end") and ($printout->getTypeID() == "_dat")) {
				$newprintouts[] = $printout;
			}
			if ((strtolower($printout->getLabel()) == "location") and ($printout->getTypeID() == "_wpg")) {
				$newprintouts[] = $printout;
			}
		}
		$this->m_printouts = $newprintouts;

		$queryobj = SMWQueryProcessor::createQuery($this->m_querystring, $this->m_params, false, '', $this->m_printouts);
		
		// figure out if this is a singletong query. if yes we need to save the
		// singleton for later use, as the query result will not include it.
		$page = null;
		if ($queryobj->getDescription()->isSingleton()) {
			$desc = $queryobj->getDescription();
			if ($desc instanceof SMWValueDescription) {
				$page = $desc->getDataValue();
			}
		}
		
		$res = smwfGetStore()->getQueryResult($queryobj);

		if (!array_key_exists('icalendartitle', $this->m_params)) {
			$this->m_params['icalendartitle'] = $wgSitename;
		}
		if (!array_key_exists('icalendardescription', $this->m_params)) {
			$this->m_params['icalendardescription'] = '';
		}

		$items = array();
		$row = $res->getNext();
		while ( $row !== false ) {
			$wikipage = $row[0]->getNextObject(); // get the object
			$startdate = "";
			$enddate = "";
			$location = "";
			foreach ($row as $result) {
				// for now we ignore everything but start and end
				// later we may add more things like a generic
				// mechanism to add whatever you want :)
				// could include funny things like geo, description etc. though
				$req = $result->getPrintRequest();
				if (strtolower($req->getLabel()) == "start") {
					$content = $result->getContent();
					foreach ($content as $entry) { // saves only the last one
						$startdate = $entry->getShortWikiText();
					}
				}
				if (strtolower($req->getLabel()) == "end") {
					$content = $result->getContent();
					foreach ($content as $entry) { // saves only the last one
						$enddate = $entry->getShortWikiText();
					}
				}
				if (strtolower($req->getLabel()) == "location") {
					$content = $result->getContent();
					foreach ($content as $entry) { // saves only the last one
						$location = $entry->getShortWikiText();
					}
				}
			}
			if ($page !== null) $wikipage = $page;
			$items[] = new SMWICalendarEntry($wikipage->getTitle(), $startdate, $enddate, $location);
			$row = $res->getNext();
		}

		$text  = "BEGIN:VCALENDAR\n";
		$text .= "PRODID:-//AIFB//Semantic MediaWiki\n";
		$text .= "VERSION:2:0\n";
		$text .= "METHOD:PUBLISH\n";
		$text .= "X-WR-CALNAME:" . $this->m_params['icalendartitle'] . "\n";
		if ($this->m_params['icalendardescription'] !== '') $text .= "X-WR-CALDESC:" . $this->m_params['icalendardescription'] . "\n";
		foreach ($items as $item)
			$text .= $item->text();
		$text .= "END:VCALENDAR\n";

		print $text;
	}

}


/**
 * Represents a single entry in an iCalendar
 */
class SMWICalendarEntry {

	private $uri;
	private $label;
	private $startdate;
	private $enddate;
	private $sequence;
	private $dtstamp;
	private $lastmodified;
	private $title;
	private $revid;

	/**
	 * Constructor for a single item in the feed. Requires the URI of the item.
	 */
	public function SMWICalendarEntry(Title $t, $startdate, $enddate, $location='') {
		global $wgServer;
		$this->title = $t;
		$this->uri = $t->getFullURL();
		$this->label = $t->getText();
		$this->startdate = $this->parsedate($startdate, $enddate);
		$this->enddate = $this->parsedate($enddate, $startdate);
		$this->location = $location;
		
		$this->sequence = $t->getLatestRevID();

		$article = new Article($t);
		$this->dtstamp  = $this->parsedate($article->getTimestamp());
	}
	
	/**
	 * Parses the date as given by MediaWiki and returns the appropriate
	 * formatting for iCalendar.
	 * If the second parameter is '', the time is always given. If the second
	 * parameter is set, then the fucntion checks if both times are on midnight,
	 * and if so it assumes that only the date, not a datetime should be returned.
	 * This heuristic tries to go circumvent the problem that SMW saves only
	 * datetimes, but not just days, and thus it has trouble to display
	 * multi-day events and anniversaries and such.
	 */
	static private function parsedate($d, $check = '') {
		if ($d=='') return '';
		if ($check=='')
			return date("Ymd", strtotime(str_replace("&nbsp;", " ", $d))) . "T" . date("His", strtotime(str_replace("&nbsp;", " ", $d)));
		if ((date("His", strtotime(str_replace("&nbsp;", " ", $d)))=="000000") && ((date("His", strtotime(str_replace("&nbsp;", " ", $check)))=="000000")))
			return date("Ymd", strtotime(str_replace("&nbsp;", " ", $d)));
		else
			return date("Ymd", strtotime(str_replace("&nbsp;", " ", $d))) . "T" . date("His", strtotime(str_replace("&nbsp;", " ", $d)));			
	}
	
	/**
	 * Creates the iCalendar output for a single item.
	 */
	public function text() {
		$text  = "BEGIN:VEVENT\n";
		$text .= "SUMMARY:$this->label\n";
		$text .= "URL:$this->uri\n";
		$text .= "UID:$this->uri\n";
		if ($this->startdate !== "") $text .= "DTSTART:$this->startdate\n";
		if ($this->enddate !== "") $text .= "DTEND:$this->enddate\n";
		if ($this->location !== "") $text .= "LOCATION:$this->location\n";
		$text .= "DTSTAMP:$this->dtstamp\n"; 
		$text .= "SEQUENCE:$this->sequence\n"; 
		$text .= "END:VEVENT\n";
		return $text;
	}

}

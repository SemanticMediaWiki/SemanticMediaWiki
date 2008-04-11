<?php

if (!defined('MEDIAWIKI')) die();

global $IP;
include_once($IP . '/includes/SpecialPage.php');

global $wgAutoloadClasses, $wgSpecialPages;
$wgSpecialPages['ICalendar'] = array('SMWICalendarPage');


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
		parent::__construct('ICalendar');
	}

	function execute($p = '') {
		global $wgOut, $wgRequest, $smwgIP, $smwgQEnabled, $smwgRSSEnabled;
		wfProfileIn('doSpecialICalendar (SMW)');
		if ( ($wgRequest->getVal( 'query' ) != '') ) { // old processing
			$wgOut->addHTML('<br /> This is the special page for iCalendar exports.');
			wfProfileOut('doSpecialICalendar (SMW)');
			return;
		}
		if (!$smwgQEnabled) {
			$wgOut->addHTML('<br />' . wfMsgForContent('smw_iq_disabled'));
			wfProfileOut('doSpecialICalendar (SMW)');
			return;
		}

		$this->extractQueryParameters($p);

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
			$rawparams = SMWInfoLink::decodeParameters($p);
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
				 $this->m_params['limit'] = 50; // magic number TODO
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
		$newprintouts = array(); // filter printouts
		foreach ($this->m_printouts as $printout) {
			if ((strtolower($printout->getLabel()) == "start") and ($printout->getTypeID() == "_dat")) {
				$newprintouts[] = $printout;
				$this->m_params['sort'] = $printout->getTitle()->getText();
				$this->m_params['order'] = 'DESC';
			}
			if ((strtolower($printout->getLabel()) == "end") and ($printout->getTypeID() == "_dat")) {
				$newprintouts[] = $printout;
			}
		}
		$this->m_printouts = $newprintouts;

		$queryobj = SMWQueryProcessor::createQuery($this->m_querystring, $this->m_params, false, '', $this->m_printouts);
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
			foreach ($row as $result) {
				// for now we ignore everything but start and end
				// later we may add more things like a generic
				// mechanism to add whatever you want :)
				$req = $result->getPrintRequest();
				if (strtolower($req->getLabel()) == "start") {
					$content = $result->getContent();
					foreach ($content as $entry) {
						$startdate = $entry->getShortWikiText();
					}
				}
				if (strtolower($req->getLabel()) == "end") {
					$content = $result->getContent();
					foreach ($content as $entry) {
						$enddate = $entry->getShortWikiText();
					}
				}
			}
			$items[] = new SMWICalendarEntry($wikipage->getTitle(), $startdate, $enddate);
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
	private $title;

	/**
	 * Constructor for a single item in the feed. Requires the URI of the item.
	 */
	public function SMWICalendarEntry(Title $t, $startdate, $enddate) {
		global $wgServer;
		$this->title = $t;
		$this->uri = $t->getFullURL();
		$this->label = $t->getText();
		$this->startdate = $this->parsedate($startdate);
		$this->enddate = $this->parsedate($enddate);
	}
	
	static private function parsedate($d) {
		return date("Ymd", strtotime(str_replace("&nbsp;", " ", $d))) . "T" . date("His", strtotime(str_replace("&nbsp;", " ", $d)));
	}
	
	/**
	 * Creates the iCalendar output for a single item.
	 */
	public function text() {
		$text  = "BEGIN:VEVENT\n";
		$text .= "SUMMARY=$this->label\n";
		$text .= "URL=$this->uri\n";
		$text .= "DTSTART=$this->startdate\n";
		if ($this->enddate !== "") $text .= "DTEND=$this->enddate\n";
		$text .= "END:VEVENT\n";
		return $text;
	}

}


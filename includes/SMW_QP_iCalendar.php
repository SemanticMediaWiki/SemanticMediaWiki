<?php
/**
 * Create iCalendar exports
 * @file
 * @ingroup SMWQuery
 */

/**
 * Printer class for creating iCalendar exports
 * @author Markus Krötzsch
 * @author Denny Vrandecic
 * @ingroup SMWQuery
 */
class SMWiCalendarResultPrinter extends SMWResultPrinter {
	protected $m_title = '';
	protected $m_description = '';

	protected function readParameters($params,$outputmode) {
		SMWResultPrinter::readParameters($params,$outputmode);
		if (array_key_exists('icalendartitle', $this->m_params)) {
			$this->m_title = trim($this->m_params['icalendartitle']);
		}
		if (array_key_exists('icalendardescription', $this->m_params)) {
			$this->m_description = trim($this->m_params['icalendardescription']);
		}
	}

	public function getMimeType($res) {
		return 'text/calendar';
	}

	public function getFileName($res) {
		if ($this->m_title != '') {
			return str_replace(' ', '_',$this->m_title) . '.ics';
		} else {
			return 'iCalendar.ics';
		}
	}

	protected function getResultText($res, $outputmode) {
		global $smwgIQRunningNumber, $wgSitename, $wgServer, $wgRequest;
		$result = '';

		if ($outputmode == SMW_OUTPUT_FILE) { // make RSS feed
			if ($this->m_title == '') {
				$this->m_title = $wgSitename;
			}
			$result .= "BEGIN:VCALENDAR\r\n";
			$result .= "PRODID:-//SMW Project//Semantic MediaWiki\r\n";
			$result .= "VERSION:2.0\r\n";
			$result .= "METHOD:PUBLISH\r\n";
			$result .= "X-WR-CALNAME:" . $this->m_title . "\r\n";
			if ($this->m_description !== '') {
				$result .= "X-WR-CALDESC:" . $this->m_description . "\r\n";
			}

			$row = $res->getNext();
			while ( $row !== false ) {
				$wikipage = $row[0]->getNextObject(); // get the object
				$startdate = '';
				$enddate = '';
				$location = '';
				$description = '';
				foreach ($row as $field) {
					// later we may add more things like a generic
					// mechanism to add whatever you want :)
					// could include funny things like geo, description etc. though
					$req = $field->getPrintRequest();
					if ( (strtolower($req->getLabel()) == "start") && ($req->getTypeID() == "_dat") ) {
						$value = current($field->getContent()); // save only the first
						if ($value !== false) {
							$startdate = $value->getXSDValue(); 
						}
					}
					if ( (strtolower($req->getLabel()) == "end") && ($req->getTypeID() == "_dat") ) {
						$value = current($field->getContent()); // save only the first
						if ($value !== false) {
							$enddate = $value->getXSDValue();
						}
					}
					if (strtolower($req->getLabel()) == "location") {
						$value = current($field->getContent()); // save only the first
						if ($value !== false) {
							$location = $value->getShortWikiText();
						}
					}
					if (strtolower($req->getLabel()) == "description") {
						$value = current($field->getContent()); // save only the first
						if ($value !== false) {
							$description = $value->getShortWikiText();
						}
					}
				}
				$title = $wikipage->getTitle();
				$article = new Article($title);
				$url = $title->getFullURL();
				$result .= "BEGIN:VEVENT\r\n";
				$result .= "SUMMARY:" . $wikipage->getShortWikiText() . "\r\n";
				$result .= "URL:$url\r\n";
				$result .= "UID:$url\r\n";
				if ($startdate != "") $result .= "DTSTART:" . $this->parsedate($startdate,$enddate) . "\r\n";
				if ($enddate != "")   $result .= "DTEND:" . $this->parsedate($enddate,$startdate,true) . "\r\n";
				if ($location != "")  $result .= "LOCATION:$location\r\n";
				if ($description != "")  $result .= "DESCRIPTION:$description\r\n";
				$result .= "DTSTAMP:" . $this->parsedate($article->getTimestamp()) . "\r\n";
				$result .= "SEQUENCE:" . $title->getLatestRevID() . "\r\n";
				$result .= "END:VEVENT\r\n";
				$row = $res->getNext();
			}
			$result .= "END:VCALENDAR\r\n";
		} else { // just make link to feed
			if ($this->getSearchLabel($outputmode)) {
				$label = $this->getSearchLabel($outputmode);
			} else {
				wfLoadExtensionMessages('SemanticMediaWiki');
				$label = wfMsgForContent('smw_icalendar_link');
			}
			$link = $res->getQueryLink($label);
			$link->setParameter('icalendar','format');
			if ($this->m_title !== '') {
				$link->setParameter($this->m_title,'icalendartitle');
			}
			if ($this->m_description !== '') {
				$link->setParameter($this->m_description,'icalendardescription');
			}
			if (array_key_exists('limit', $this->m_params)) {
				$link->setParameter($this->m_params['limit'],'limit');
			} else { // use a reasonable default limit
				$link->setParameter(20,'limit');
			}

			$result .= $link->getText($outputmode,$this->mLinker);
			$this->isHTML = ($outputmode == SMW_OUTPUT_HTML); // yes, our code can be viewed as HTML if requested, no more parsing needed
		}

		return $result;
	}

	/**
	 * Parses a date string (XSD or MediaWiki output) and returns the appropriate
	 * formatting for iCalendar.
	 * If the second parameter is false, the time is always included. If the second
	 * parameter is set, it is assumed to be another date string. If both times are 
	 * on midnight, we assume that only the date, not a date+time should be returned.
	 * This heuristic tries to go circumvent the problem that SMW saves only
	 * datetimes, but not just days, and thus it has trouble to display
	 * multi-day events and anniversaries and such.
	 */
	static private function parsedate($d, $check = false, $isend=false) {
		if ($d=='') return '';
		$t = strtotime(str_replace('T', ' ', $d));
		if ($check) {
			$t2 = strtotime(str_replace('T', ' ', $check));
			if ( (date("His", $t)=="000000") && (date("His", $t2)=="000000") )  {
				if ($isend) $t = $t + 60*60*24;
				return date("Ymd", $t);
			}
		}
		return date("Ymd", $t) . "T" . date("His", $t);
	}

}


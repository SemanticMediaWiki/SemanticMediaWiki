<?php
/**
 * Print links to iCalendar files for query results.
 */

/**
 * Printer for creating a link to a iCalendar file.
 * @author Denny Vrandecic
 * @author Markus Krötzsch
 * @note AUTOLOADED
 */

class SMWiCalendarResultPrinter extends SMWResultPrinter {
	protected $title = '';
	protected $description = '';
	protected $icalendarlinktitle; // just a cache
	protected $icalendarlinkurl; // just a cache

	protected function readParameters($params,$outputmode) {
		SMWResultPrinter::readParameters($params,$outputmode);
		if (array_key_exists('icalendartitle', $this->m_params)) {
			$this->title = $this->m_params['icalendartitle'];
		}
		if (array_key_exists('icalendardescription', $this->m_params)) {
			$this->description = $this->m_params['icalendardescription'];
		}
		$this->icalendarlinktitle = '';
	}

	public function getResult($results, $params, $outputmode) { // skip all checks, the result is never populated
		$this->readParameters($params,$outputmode);
		return $this->getResultText($results,$outputmode) . $this->getErrorString($results);
	}

	protected function getResultText($res, $outputmode) {
		global $smwgIQRunningNumber;
		$result = '';
		if ($this->mSearchlabel) {
			$label = $this->mSearchlabel;
		} else { // default label
			$label = wfMsgForContent('smw_icalendar_link');
		}
		$result .= $this->getiCalendarLink($outputmode, $res, $label);
		$rurl = $this->getiCalendarURL($res);
		if ($rurl != false) {
			smwfRequireHeadItem('icalendar' . $smwgIQRunningNumber, '<link rel="alternate" type="text/calendar" title="' . $this->title . '" href="' . $rurl . '" />');
		}
		return $result;
	}

	protected function getiCalendarLink($outputmode,$res,$label) {
		switch ($outputmode) {
			case SMW_OUTPUT_WIKI:
				$title = Title::newFromText( $this->getiCalendarTitle($res), NS_SPECIAL );
				if ($title === NULL) {
					return '[' . $this->getiCalendarURL($res) . ' ' . $label . ']';
				} else {
					return '[[' . $this->getiCalendarTitle($res) . '|' . $label . ']]';
				}
			case SMW_OUTPUT_HTML: default: return '<a href="' . $this->getiCalendarURL($res) . '">' . $label . '</a>';
		}	
	}
	
	protected function getiCalendarURL($res) {
		$this->makeURLs($res);
		return $this->icalendarlinkurl;
	}

	protected function getiCalendarTitle($res) {
		$this->makeURLs($res);
		return $this->icalendarlinktitle;
	}

	protected function makeURLs($res) {
		if ($this->icalendarlinktitle != '') {
			return;
		}
		$paramstring = $res->getQueryTitle(false);
		$params = array('icalendar=1');
		if (array_key_exists('limit', $this->m_params)) {
			$params[] = 'limit=' . $this->m_params['limit'];
		}
		if ($this->title !== '') {
			$params[] = 'icalendartitle=' . $this->title;
		}
		if ($this->description !== '') {
			$params[] = 'icalendardescription=' . $this->description;	
		}
		foreach ($params as $p) {
			$p = str_replace(array('/','=','-','%'),array('-2F','-3D','-2D','-'), rawurlencode($p));
			$paramstring .= '/' . $p;
		}
		$title = Title::makeTitle(NS_SPECIAL, 'icalendar');
		$this->icalendarlinktitle = ':' . $title->getPrefixedText() . '/' . $paramstring;
		$this->icalendarlinkurl = $title->getFullURL('raw=' . $paramstring);
	}


}


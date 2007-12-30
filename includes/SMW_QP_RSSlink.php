<?php
/**
 * Print links to RSS feeds for query results.
 */

/**
 * Printer for creating a link to RSS feeds.
 * @author Denny Vrandecic
 * @author Markus Krötzsch
 * @note AUTOLOADED
 */

class SMWRSSResultPrinter extends SMWResultPrinter {
	protected $title = '';
	protected $description = '';

	protected function readParameters($params,$outputmode) {
		SMWResultPrinter::readParameters($params,$outputmode);
		if (array_key_exists('rsstitle', $this->m_params)) {
			$this->title = $this->m_params['rsstitle'];
		}
		if (array_key_exists('rssdescription', $this->m_params)) {
			$this->description = $this->m_params['rssdescription'];
		}
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
			$label = wfMsgForContent('smw_rss_link');
		}
		$result .= $this->getRSSLink($outputmode, $res, $label);
		smwfRequireHeadItem('rss' . $smwgIQRunningNumber, '<link rel="alternate" type="application/rss+xml" title="' . $this->title . '" href="' . $this->getRSSURL($res) . '" />');
		return $result;
	}

	protected function getRSSLink($outputmode,$res,$label) {
		switch ($outputmode) {
			case SMW_OUTPUT_WIKI: return '[[' . $this->getRSSTitle($res) . '|' . $label . ']]';
			case SMW_OUTPUT_HTML: default: return '<a href="' . $this->getRSSURL($res) . '">' . $label . '</a>';
		}	
	}
	
	protected function getRSSURL($res) {
		$title = Title::newFromText( $this->getRSSTitle($res) );
		return $title->getFullURL();
	}

	protected function getRSSTitle($res) {
		$result = $res->getQueryTitle();
		$params = array('rss=1');
		if (array_key_exists('limit', $this->m_params)) {
			$params[] = 'limit=' . $this->m_params['limit'];
		}
		if ($this->title !== '') {
			$params[] = 'rsstitle=' . $this->title;
		}
		if ($this->description !== '') {
			$params[] = 'rssdescription=' . $this->description;	
		}
		foreach ($params as $p) {
			$p = str_replace(array('/','=','-','%'),array('-2F','-3D','-2D','-'), rawurlencode($p));
			$result .= '/' . $p;
		}
		return $result;
	}

}


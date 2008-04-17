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
	protected $rsslinktitle; // just a cache
	protected $rsslinkurl; // just a cache

	protected function readParameters($params,$outputmode) {
		SMWResultPrinter::readParameters($params,$outputmode);
		if (array_key_exists('rsstitle', $this->m_params)) {
			$this->title = $this->m_params['rsstitle'];
		}
		if (array_key_exists('rssdescription', $this->m_params)) {
			$this->description = $this->m_params['rssdescription'];
		}
		$this->rsslinktitle = '';
	}

	public function getResult($results, $params, $outputmode) { // skip all checks, the result is never populated
		$this->readParameters($params,$outputmode);
		return $this->getResultText($results,$outputmode) . $this->getErrorString($results);
	}

	protected function getResultText($res, $outputmode) {
		global $smwgIQRunningNumber;
		$result = '';
		
		$link = $res->getQueryLink();
		if ($this->mSearchlabel) {
			$link->setCaption($this->mSearchlabel);
		} else {
			$link->setCaption(wfMsgForContent('smw_rss_link'));
		}
		$link->addParameter('1','rss');
		if ($this->title !== '') {
			$link->addParameter($this->title,'rsstitle');
		}
		if ($this->description !== '') {
			$link->addParameter($this->description,'rssdescription');
		}

		$result .= $link->getText($outputmode,$this->getLinker());

		smwfRequireHeadItem('rss' . $smwgIQRunningNumber, '<link rel="alternate" type="application/rss+xml" title="' . $this->title . '" href="' . $link->getURL() . '" />');

		return $result;
	}

}


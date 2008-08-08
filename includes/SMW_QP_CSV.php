<?php
/**
 * CSV export for SMW Queries
 */

/**
 * Printer class for generating CSV output
 * @author Nathan R. Yergler
 * @note AUTOLOADED
 */
class SMWCsvResultPrinter extends SMWResultPrinter {
	protected $m_sep;

	protected function readParameters($params,$outputmode) {
		SMWResultPrinter::readParameters($params,$outputmode);
		if (array_key_exists('sep', $params)) {
			$this->m_sep = str_replace('_',' ',$params['sep']);
		} else {
			$this->m_sep = ',';
		}
	}

	public function getResult($results, $params, $outputmode) { 
		// skip checks, results with 0 entries are normal
		$this->readParameters($params,$outputmode);
		return $this->getResultText($results,$outputmode) . $this->getErrorString($results);
	}

	public function getMimeType($res) {
		return 'text/csv';
	}

	public function getFileName($res) {
		return 'result.csv';
	}

	protected function getResultText($res, $outputmode) {

		global $smwgIQRunningNumber, $wgSitename, $wgServer, $wgRequest;
		$result = '';

		if ($outputmode == SMW_OUTPUT_FILE) { // make CSV file
			$csv = fopen('php://memory', 'r+');
			while ( $row = $res->getNext() ) {
				$row_items = array();
				foreach ($row as $field) {
					$growing = array();
					while (($object = $field->getNextObject()) !== false) {
						$text = Sanitizer::decodeCharReferences($object->getWikiValue());
						// decode: CSV knows nothing of possible HTML entities
						$growing[] = $text;
					} // while...
					$row_items[] = implode(',', $growing);
				} // foreach...
				fputcsv($csv, $row_items, $this->m_sep);
			} // while...

			rewind($csv);
			$result .= stream_get_contents($csv);
		} else { // just make link to feed
			if ($this->mSearchlabel) {
				$label = $this->mSearchlabel;
			} else {
				$label = wfMsgForContent('smw_csv_link');
			}

			$link = $res->getQueryLink($label);
			$link->setParameter('csv','format');
			$link->setParameter($this->m_sep,'sep');
			if (array_key_exists('limit', $this->m_params)) {
				$link->setParameter($this->m_params['limit'],'limit');
			} else { // use a reasonable default limit
				$link->setParameter(100,'limit');
			}
			$result .= $link->getText($outputmode,$this->mLinker);
		}
		return $result;
	}

}

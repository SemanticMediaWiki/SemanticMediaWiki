<?php
/**
 * A query printer using the Google Chart API
 *
 * @note AUTOLOADED
 */

if( !defined( 'MEDIAWIKI' ) ) {
	die( 'Not an entry point.' );
}

class SMWGoogleBarResultPrinter extends SMWResultPrinter {
	protected $m_width = '250';

	protected function readParameters($params,$outputmode) {
		SMWResultPrinter::readParameters($params,$outputmode);
		if (array_key_exists('width', $this->m_params)) {
			$this->m_width = $this->m_params['width'];
		}
	}

	public function getResult($results, $params, $outputmode) {
		$result = parent::getResult($results, $params, $outputmode);
		$this->readParameters($params,$outputmode);
		return array($result, 'isHTML' => true);
	}

	protected function getResultText($res, $outputmode) {
		global $smwgIQRunningNumber;
		
		$t = "";
		// print all result rows
		$first = true;
		$count = 0; // How many bars will they be? Needed to calculate the height of the image
		$max = 0; // the biggest value. needed for scaling
		while ( $row = $res->getNext() ) {
			$name = $row[0]->getNextObject()->getShortWikiText();
			foreach ($row as $field) {
					while ( ($object = $field->getNextObject()) !== false ) {
					if ($object->isNumeric()) { // use numeric sortkey
						$nr = $object->getNumericValue();
						$count++;
						$max = max($max, $nr);
						if ($first) {
							$first = false;
							$t .= $nr;
							$n = $name;
						} else {
							$t = $nr . ',' . $t;
							$n .= '|' . $name; // yes, this is correct, it needs to be the other way
						}
					}
				}
			}
		}
		$barwidth = 20; // width of each bar
		$bardistance = 4; // distance between two bars
		$height = $count* ($barwidth + $bardistance) + 15; // calculates the height of the image
		return 	'<img src="http://chart.apis.google.com/chart?cht=bhs&chbh=' . $barwidth . ',' . $bardistance . '&chs=' . $this->m_width . 'x' . $height . '&chds=0,' . $max . '&chd=t:' . $t . '&chxt=y&chxl=0:|' . $n . '" width="' . $this->m_width . '" height="' . $height . '" />';
		
	}

}

class SMWGooglePieResultPrinter extends SMWResultPrinter {
	protected $m_width = 250;
	protected $m_heighth = 250;

	protected function readParameters($params,$outputmode) {
		SMWResultPrinter::readParameters($params,$outputmode);
		if (array_key_exists('width', $this->m_params)) {
			$this->m_width = $this->m_params['width'];
		}
		if (array_key_exists('height', $this->m_params)) {
			$this->m_height = $this->m_params['height'];
		} else {
			$this->m_height = $this->m_width * 0.4;
		}
	}

	public function getResult($results, $params, $outputmode) {
		$result = parent::getResult($results, $params, $outputmode);
		$this->readParameters($params,$outputmode);
		return array($result, 'isHTML' => true);
	}

	protected function getResultText($res, $outputmode) {
		global $smwgIQRunningNumber;
		
		$t = "";
		// print all result rows
		$first = true;
		$max = 0; // the biggest value. needed for scaling
		while ( $row = $res->getNext() ) {
			$name = $row[0]->getNextObject()->getShortWikiText();
			foreach ($row as $field) {
					while ( ($object = $field->getNextObject()) !== false ) {
					if ($object->isNumeric()) { // use numeric sortkey
						$nr = $object->getNumericValue();
						$max = max($max, $nr);
						if ($first) {
							$first = false;
							$t .= $nr;
							$n = $name;
						} else {
							$t = $nr . ',' . $t;
							$n = $name . '|' . $n;
						}
					}
				}
			}
		}
		return 	'<img src="http://chart.apis.google.com/chart?cht=p3&chs=' . $this->m_width . 'x' . $this->m_height . '&chds=0,' . $max . '&chd=t:' . $t . '&chl=' . $n . '" width="' . $this->m_width . '" height="' . $this->m_height . '"  />';
		
	}

}
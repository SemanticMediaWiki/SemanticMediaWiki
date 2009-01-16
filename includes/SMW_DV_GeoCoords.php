<?php
/**
 * @file
 * @ingroup SMWDataValues
 */

/// Unicode symbols for coordinate minutes and seconds;
/// may not display in every font ...
define('SMW_GEO_MIN','′');
define('SMW_GEO_SEC','″');

/**
 * Implementation of datavalues that are geographic coordinates.
 *
 * @author Markus Krötzsch
 * @ingroup SMWDataValues
 */
class SMWGeoCoordsValue extends SMWDataValue {

	protected $m_N = false; // cache for localised direction labels
	protected $m_E = false; // cache for localised direction labels
	protected $m_W = false; // cache for localised direction labels
	protected $m_S = false; // cache for localised direction labels

	protected $m_wikivalue;
	protected $m_lat;   // decimal latitude of current value
	protected $m_long;  // decimal longitude of current value
	protected $m_latparts;  // latitude array of four entries: degrees, minutes, seconds, direction
	protected $m_longparts; // longitude array of four entries: degrees, minutes, seconds, direction
	// Note: signs are used as e.g. on Google maps, i.e. S and W are negative numbers.

	protected function parseUserValue($value) {
		wfLoadExtensionMessages('SemanticMediaWiki');
		$this->m_lat = false;
		$this->m_long = false;
		$this->m_latparts = false;
		$this->m_longparts = false;
		$this->m_wikivalue = $value;

		// first normalise some typical symbols
		$this->initDirectionLabels();
		$value = str_replace(array('&nbsp;', $this->m_N, $this->m_E, $this->m_W, $this->m_S, ),
		                     array(' ','N','E','W','S'),$value);
		$value = str_replace(array('&#176;', '&deg;'), '°', $value);
		$value = str_replace(array('&acute;', '&#180;'),'´',$value);
		$value = str_replace(array('&#8243;', '&Prime;', "''", '"', '´´', SMW_GEO_MIN . SMW_GEO_MIN),SMW_GEO_SEC,$value);
		$value = str_replace(array('&#8242;', '&prime;', "'", '´'),SMW_GEO_MIN,$value);
		// now split the string
		$parts = preg_split('/\s*(°|' . SMW_GEO_MIN . '|' . SMW_GEO_SEC . '|N|E|W|S|;)\s*/u',str_replace(', ', ';', $value) . ';', -1, PREG_SPLIT_DELIM_CAPTURE);
		$curnum = false;
		$angles = array(false, false, false); // temporary values for deg, min, sec
		foreach ($parts as $part) {
			switch ($part) {
				case '°':
					if ( ($angles[0] !== false) && ($this->m_lat === false) ) { // work off values found earlier
						$this->setAngleValues('N',$angles);
					} // else: we do not accept interchange of order (lat must be first), so there are just too many °s
					if ( $curnum !== false ) {
						$angles[0] = $curnum;
						$curnum = false;
					} else {
						$this->addError(wfMsgForContent('smw_lonely_unit', $part));
					}
				break;
				case SMW_GEO_MIN:
					if ( ($curnum !== false) && ($angles[1] === false) ) {
						$angles[1] = $curnum;
						if ($angles[0] === false) $angles[0] = 0;
						$curnum = false;
					} else {
						$this->addError(wfMsgForContent('smw_lonely_unit', $part));
					}
				break;
				case SMW_GEO_SEC:
					if ( ($curnum !== false) && ($angles[2] === false) ) {
						$angles[2] = $curnum;
						if ($angles[0] === false) $angles[0] = 0;
						if ($angles[1] === false) $angles[1] = 0;
						$curnum = false;
					} else {
						$this->addError(wfMsgForContent('smw_lonely_unit', $part));
					}
				break;
				case 'N': case 'S': // interpret findings as latitude
					if ( $curnum !== false ) { // work off number without °
						if ($angles[0] !== false) { // "12° 34" as coordinate, complain
							$this->addError(wfMsgForContent('smw_bad_latlong'));
							break;
						} else {
							$angles[0] = $curnum;
							$curnum = false;
						}
					}
					if (($this->m_lat === false) && ($angles[0] !== false)) {
						$this->setAngleValues($part,$angles);
					} else {
						$this->addError(wfMsgForContent('smw_bad_latlong'));
					}
				break;
				case 'E': case 'W': // interpret findings as longitude
					if ( $curnum !== false ) { // work off number without °
						if ($angles[0] !== false) { // "12° 34" as coordinate, complain
							$this->addError(wfMsgForContent('smw_bad_latlong'));
							break;
						} else {
							$angles[0] = $curnum;
							$curnum = false;
						}
					}
					if (($this->m_long === false) && ($angles[0] !== false)) {
						$this->setAngleValues($part,$angles);
					} else {
						$this->addError(wfMsgForContent('smw_bad_latlong'));
					}
				break;
				case ';': // interpret findings as latitude
					if ( $curnum !== false ) { // work off number without °
						if ($angles[0] !== false) { // "12° 34" as coordinate, complain
							$this->addError(wfMsgForContent('smw_bad_latlong'));
							break;
						} else {
							$angles[0] = $curnum;
							$curnum = false;
						}
					}
					if (($this->m_lat === false) && ($angles[0] !== false)) {
						$this->setAngleValues('N',$angles);
					} // else: ignore ";" without complaining
				break;
				case '': break; // ignore
				default: // should be a number (if not, errors appear elsewhere)
					// no kiloseps in coordinates, use as decsep as a convenience to some users (Bug 11808):
					$curnum = str_replace(wfMsgForContent('smw_kiloseparator'), wfMsgForContent('smw_decseparator'), $part);
				break;
			}
		}

		if ( ($this->m_lat !== false) && ($this->m_long === false) && ($angles[0] !== false) ) { // no final E or W?
			$this->setAngleValues('E',$angles);
		}
		if ( ($angles[0] !== false)||($curnum !== false)) { // unprocessed chunk, error

		}

		if ($this->m_caption === false) {
			$this->m_caption = $value;
		}
		return true;
	}

	protected function parseDBkeys($args) {
		$this->m_lat = false;
		$this->m_long = false;
		$this->m_latparts = false;
		$this->m_longparts = false;

		list($this->m_lat, $this->m_long) = split(',', $args[0]);
		$this->m_caption = $this->formatAngleValues(true) . ', ' . $this->formatAngleValues(false); // this is our output text
		$this->m_wikivalue = $this->m_caption;
	}

	public function getShortWikiText($linked = NULL) {
		if ($this->isValid() && ($linked !== NULL) && ($linked !== false)) {
			wfLoadExtensionMessages('SemanticMediaWiki');
			SMWOutputs::requireHeadItem(SMW_HEADER_TOOLTIP);
			return '<span class="smwttinline">' . $this->m_caption . '<span class="smwttcontent">' .
			        wfMsgForContent('smw_label_latitude') . ' ' . $this->formatAngleValues(true) . '<br />' .
			        wfMsgForContent('smw_label_longitude') . ' ' . $this->formatAngleValues(false) .
			        '</span></span>';
		} else {
			return $this->m_caption;
		}
	}

	public function getShortHTMLText($linker = NULL) {
		return $this->getShortWikiText($linker); // should be save (based on xsdvalue)
	}

	public function getLongWikiText($linked = NULL) {
		if (!$this->isValid()) {
			return $this->getErrorText();
		} else {
			return $this->formatAngleValues(true) . ', ' . $this->formatAngleValues(false);
		}
	}

	public function getLongHTMLText($linker = NULL) {
		return $this->getLongWikiText($linker);
	}

	public function getDBkeys() {
		$this->unstub();
		return array($this->m_lat . ',' . $this->m_long);
	}

	public function getWikiValue(){
		$this->unstub();
		return $this->m_wikivalue;
	}

	public function getExportData() {
		if ($this->isValid()) {
			$lit = new SMWExpLiteral($this->formatAngleValues(true, false) . ', ' . $this->formatAngleValues(false, false), $this, 'http://www.w3.org/2001/XMLSchema#string');
			return new SMWExpData($lit);
		} else {
			return NULL;
		}
	}

	/**
	 * Get and cache localised direction labels. Just for convenience.
	 */
	protected function initDirectionLabels() {
		wfLoadExtensionMessages('SemanticMediaWiki');
		$this->m_N = wfMsgForContent('smw_abb_north');
		$this->m_E = wfMsgForContent('smw_abb_east');
		$this->m_W = wfMsgForContent('smw_abb_west');
		$this->m_S = wfMsgForContent('smw_abb_south');
	}

	/**
	 * Helper function: read a possibly incomplete array of angles for one coordinate.
	 * The direction is one of N, E, W, S, and $angles is an array of three values,
	 * each possibly false if unset.
	 */
	protected function setAngleValues($direction, &$angles) {
		wfLoadExtensionMessages('SemanticMediaWiki');
		$numvalue = SMWDataValueFactory::newTypeIDValue('_num');
		$res = 0;
		$factor = 1;
		for ($i=0; $i<3; $i++) {
			if ($angles[$i] !== false) {
				$numvalue->setUserValue($angles[$i]);
				if ($numvalue->isValid() && ($numvalue->getUnit() == '')) {
					$res += $numvalue->getNumericValue() / $factor;
				} else {
					$this->addError(wfMsgForContent('smw_nofloat', $angles[$i]));
				}
			}
			$factor = $factor * 60;
		}
		switch ($direction) {
			case 'N': $this->m_lat = $res; break;
			case 'S': $this->m_lat = -1 * $res; break;
			case 'E': $this->m_long = $res; break;
			case 'W': $this->m_long = -1 * $res; break;
		}
		if ( (($direction == 'E') || ($direction == 'W')) &&
		     (($this->m_long > 180) || ($this->m_long <= -180)) ) { // bring values back into [180, -180)
			$this->m_long += ($this->m_long<0)?(round(abs($this->m_long)/360)*360):(round($this->m_long/360)*-360);
		}
		///TODO: also make such a normalisation for lat ...
		$angles = array(false, false, false);
	}

	/**
	 * Return array with four entries for deg, min, sec, direction,
	 * that corresponds to the current latitude or longitude.
	 */
	protected function getAngleValues($lat = true) {
		if ($lat) {
			if ($this->m_latparts !== false) {
				return $this->m_latparts;
			}
			$num = abs($this->m_lat);
			$d = ($this->m_lat<0)?'S':'N';
		} else {
			if ($this->m_longparts !== false) {
				return $this->m_longparts;
			}
			$num = abs($this->m_long);
			$d = ($this->m_long<0)?'W':'E';
		}
		$result = array(0,0,0,$d);
		$result[0] = floor($num);
		$num = ($num-$result[0]) * 60;
		$result[1] = floor($num);
		$result[2] = ($num-$result[1]) * 60;
		if ( abs($result[2]) < 0.001 ) { // limit precission, avoid conversion generated junk and EXP notation in coords
			$result[2] = 0;
		}
		if ($lat) {
			$this->m_latparts = $result;
		} else {
			$this->m_longparts = $result;
		}
		return $result;
	}

	/**
	 * Format the current latitude or longitude. The parameter $content states
	 * whether the result is for content printout. Alternatively, a language-
	 * independent result is generated.
	 */
	protected function formatAngleValues($lat = true, $content = true) {
		$values = $this->getAngleValues($lat);
		if ($content) {
			$this->initDirectionLabels();
			$result = smwfNumberFormat($values[0]) . '°' . smwfNumberFormat($values[1]) . SMW_GEO_MIN .
			          smwfNumberFormat($values[2]) . SMW_GEO_SEC;
			switch ($values[3]) {
				case 'N': return $result . $this->m_N;
				case 'E': return $result . $this->m_E;
				case 'W': return $result . $this->m_W;
				case 'S': return $result . $this->m_S;
			}
		} else {
			return smwfNumberFormat($values[0]) . '°' . smwfNumberFormat($values[1]) . SMW_GEO_MIN .
			       smwfNumberFormat($values[2]) . SMW_GEO_SEC . $values[3];
		}
	}

	protected function getServiceLinkParams() {
		// Create links to mapping services based on a wiki-editable message. The parameters
		// available to the message are:
		// $1: latitude integer degrees, $2: longitude integer degrees
		// $3: latitude integer minutes, $4: longitude integer minutes
		// $5: latitude integer seconds, $6: longitude integer seconds,
		// $7: latitude direction string (N or S), $8: longitude direction string (W or E)
		// $9: latitude in decimal degrees, $10: longitude in decimal degrees
		// $11: sign (- if south) for latitude, $12: sign (- if west) for longitude
		$latvals = $this->getAngleValues(true);
		$longvals = $this->getAngleValues(false);
		return array($latvals[0], $longvals[0],
		             $latvals[1], $longvals[1],
		             round($latvals[2]), round($longvals[2]),
		             $latvals[3], $longvals[3],
		             abs($this->m_lat), abs($this->m_long),
		             $latvals[3]=='S'?'-':'', $longvals[3]=='W'?'-':'');
	}

}

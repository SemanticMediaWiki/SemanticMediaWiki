<?php
/**
 * Support functions for evaluating datatypes that accept geographical locations.
 *
 * @author Kai Hüner
 */

/******* Datatype handler classes ********/

class SMWGeographicLocationTypeHandler implements SMWTypeHandler {

	/**
	* Patterns for parsing user input in any crazy form ...
	* These patterns are used in different functions of this file
	*/
	var $pSpace;
	var $pDegreeUnit;
	var $pMinUnit;
	var $pSecUnit;
	var $pInteger;
	var $pFloat;
	var $pCoordDelim;

	/**
	* This unicode symbols for ° and ′ are used to show
	* well formed strings representing geographic locations.
	*/
	var $degreeUnicode;
	var $minuteUnicode;
	var $secondUnicode;

	/** @public */

	function SMWGeographicLocationTypeHandler() {
		$this->pSpace      = " *";
		$this->pDegreeUnit = "°";
		$this->pMinUnit    = "(\'|´|′)";
		$this->pSecUnit    = '(' . $this->pMinUnit . '{2,2}|″|")';
		$this->pInteger    = '('.$this->pSpace.'[\d]+'.$this->pSpace.')';
		$this->pFloat      = '('.$this->pSpace.'[\d]+[.]?[\d]*'.$this->pSpace.')';
/* wfMsgForContent is not available during setup ... */
// 		$this->pDirection  = '('.$this->pSpace.'[' . wfMsgForContent('smw_abb_north') . '|'
// 		                                           . wfMsgForContent('smw_abb_east') . '|'
// 		                                           . wfMsgForContent('smw_abb_west') . '|'
// 		                                           . wfMsgForContent('smw_abb_south') . '])';
		$this->pCoordDelim = '('.$this->pSpace.'[,|;|\/|-]'.$this->pSpace.')';

		$this->degreeUnicode = '&#176;';
		$this->minuteUnicode = '&#8242;';
		$this->secondUnicode = '&#8243;';
	}

	function getID() {
		return '_geo';
	}

	function getXSDType() {
		return 'http://www.w3.org/2001/XMLSchema#string';
	}

	function getUnits() { //no units for string
		return array('STDUNIT'=>false, 'ALLUNITS'=>array());
	}


    /**
	 * This method transforms the user-provided value of an
	 * attribute into several output strings (one for XML,
	 * one for printout, etc.) and reports parsing errors if
	 * the value is not valid for the given data type.
	 *
	 * @public
	 */
	function processValue($v,&$datavalue) {

		$v = str_replace('&#176;','°',$v);
		$v = str_replace('&#8242;','′',$v);
		$v = str_replace('&#8243;','″',$v);

		$latLong = $this->ParseGeoLocationString($v);
		if (!is_array($latLong)) { // error
			$datavalue->setError($latLong);
			return;
		}

		$lat  = $latLong['lat'];
		$long = $latLong['long'];

		$declat= $this->Angle2Decimal($lat['deg'], $lat['min'],$lat['sec']);
		$declong= $this->Angle2Decimal($long['deg'], $long['min'],$long['sec']);

		$values = Array();
		// Full location with degrees, minutes, and seconds
		$values['latAngle']    = $this->GetWellFormedLocationString($lat['deg'], $lat['min'],$lat['sec'],$lat['dir']);
		$values['longAngle']   = $this->GetWellFormedLocationString($long['deg'], $long['min'],$long['sec'],$long['dir']);
		// Location in degrees with decimal part
		$values['latDecimal']  = $this->GetWellFormedDecimalDegree($declat,$lat['dir']);
		$values['longDecimal'] = $this->GetWellFormedDecimalDegree($declong,$long['dir']);
		// // Location with degrees and minutes with decimal part
		// $values['latMixed']    = $this->GetWellFormedMixedLocationString($lat['deg'], $lat['min'],$lat['sec'],$lat['dir']);
		// $values['longMixed']   = $this->GetWellFormedMixedLocationString($long['deg'], $long['min'],$long['sec'],$long['dir']);

		$xsd =    str_replace(
		            array(wfMsgForContent('smw_abb_north'),
		                  wfMsgForContent('smw_abb_east'),
		                  wfMsgForContent('smw_abb_south'),
		                  wfMsgForContent('smw_abb_west')),
		            array('N','E','S','W'),
		            $values['latAngle'].', '.$values['longAngle']
		          );
		$string = str_replace(' ','&nbsp;',
		                    $values['latAngle'] . ', ' . $values['longAngle']);
		$datavalue->setProcessedValues($v, $xsd);
		$datavalue->setPrintoutString($string);
		
		// other values for tooltip and factbox
		$datavalue->setPrintoutString(
		          wfMsgForContent('smw_label_latitude') . ' ' .
		          str_replace(' ','&nbsp;',$values['latDecimal']),
		          'lat');
		$datavalue->setPrintoutString( 
		          wfMsgForContent('smw_label_longitude') . ' ' .
		          str_replace(' ','&nbsp;',$values['longDecimal']),
		          'long');

		// infolinks for factbox
		$datavalue->addQuicksearchLink();
		
		if ($long['dir'] == wfMsgForContent('smw_abb_west') ) {
			$signlong = '-';
		} else $signlong = '';
		if ($lat['dir'] == wfMsgForContent('smw_abb_south') ) {
			$signlat = '-';
		} else $signlat = '';
		// Create links to mapping services based on a wiki-editable message. The parameters 
		// available to the message are:
		// $1: latitude integer degrees, $2: longitude integer degrees
		// $3: latitude integer minutes, $4: longitude integer minutes
		// $5: latitude integer seconds, $6: longitude integer seconds,
		// $7: latitude direction string (N or S), $8: longitude direction string (W or E)
		// $9: latitude in decimal degrees, $10: longitude in decimal degrees
		// $11: sign (- if south) for latitude, $12: sign (- if west) for longitude

		$datavalue->addServiceLinks( $lat['deg'], $long['deg'],
		                             $lat['min'], $long['min'],
		                             round($lat['sec']), round($long['sec']),
		                             $lat['dir'], $long['dir'],
		                             $declat, $declong,
		                             $signlat, $signlong
		                           );
		return;
	}

	/**
	 * This method parses the value in the XSD form that was
	 * generated by parsing some user input. It is needed since
	 * the XSD form must be compatible to XML, and thus does not
	 * respect the internationalization settings. E.g. the German
	 * input value "1,234" is translated into XSD "1.234" which,
	 * if reparsed as a user input would be misinterpreted as 1234.
	 *
	 * @public
	 */
	function processXSDValue($value,$unit,&$datavalue) {
		$value = str_replace(array('N','E','S','W'),
		                     array(wfMsgForContent('smw_abb_north'),
		                           wfMsgForContent('smw_abb_east'),
		                           wfMsgForContent('smw_abb_south'),
		                           wfMsgForContent('smw_abb_west')),
		                     $value);
		return $this->processValue($value . $unit, $datavalue);
	}

	/** @private */

	/**
	* Converts an angle representation of a geographic location
	* (x.xxx°x.xxx'x.xxx'') to a decimal representation of the
	* degree value (x.xxx°). Decimal parts of a level (e.g. .1°) are
	* added to next sublevel (e.g. + 6′).
	* No negative values are supported for any parameter!
	* @param $degree Degree part of the angle representation (x.xxx°)
	* @param $min Minute part of the angle representation (x.xxx′)
	* @param $sec Minute part of the angle representation (x.xxx′′)
	* @return decimal representation with degrees as top level (x.xxx°)
	*/
	function Angle2Decimal($degree, $min, $sec) {
		return $degree + $min/60 + $sec/3600;
	}

	/**
	* Converts an angle representation of only minutes and seconds
	* of a geographic location (x.xxx′x.xxx′′) to a decimal representation
	* of the minute value (x.xxx°). Decimal parts of minute value are
	* added to the seconds.
	* No negative values are supported for any parameter!
	* @param $min Minute part of the angle representation (x.xxx′)
	* @param $sec Minute part of the angle representation (x.xxx′′)
	* @return decimal representation with minutes as top level (x.xxx′)
	*/
	function MinSec2Decimal($min, $sec, $dir) {
		return ($min + $sec/60).$this->degreeUnicode.' '.$dir;
	}

	/**
	* Converts a decimal degree value (x.xxx°) for a geographic location to
	* values for an angle representation (x°x′x.xxx′′).
	* No negative input is supported!
	* @param $degDec Decimal representation of a degree value (x.xxx°)
	* @return A 3-value-array with keys 'deg', 'min' and 'sec'
	*/
	function DecimalDegree2Angle($degDec) {
		if ($degDec < 0) {
			return null; // no support for negative values!
		}
		$deg = floor($degDec);
		$minDec = ($degDec - $deg) * 60;
		$min    = floor($minDec);
		$secDec = ($minDec - floatval($min));
		$sec    = $secDec * 60;

		return array('deg'=>$deg, 'min'=>$min, 'sec'=>$sec);
	}

	/**
	* Converts a decimal minute value (x.xxx′) for a geographic location
	* to values for an angle representation (x′x.xxx′′).
	* No negative input is supported!
	* @param $minDec Decimal representation of a minute value (x.xxx′)
	* @return A 2-value-array with keys 'min' and 'sec'
	*/
	function DecimalMinute2Angle($minDec) {
		if ($minDec < 0) {
			return null; // no support for negative values!
		}
		$min    = floor($minDec);
		$secDec = ($minDec - $min);
		$sec    = $secDec * 60;

		return array('min'=>$min, 'sec'=>$sec);
	}

	/**
	* @return A well formed string representing a geographic location (x°x′x.xxx′′ X)
	*/
	function GetWellFormedLocationString($degree, $min, $sec, $direction) {
		return $degree.$this->degreeUnicode.$min.$this->minuteUnicode.smwfNumberFormat($sec, 3).$this->secondUnicode.' '.$direction;
	}

	/**
	* @return A well formed string representing a geographic location with degree and decimal
	* minute part (x°x.xxx′ X)
	*/
	function GetWellFormedMixedLocationString($degree, $min, $sec, $direction) {
		return $degree.$this->degreeUnicode.smwfNumberFormat($this->MinSec2Decimal($min, $sec, $direction),3).$this->minuteUnicode.' '.$direction;
	}

	/**
	* @return A well formed string representing a geographic location with decimal degree (x.xxx° X)
	*/
	function GetWellFormedDecimalDegree($decimal, $direction) {
		return smwfNumberFormat($decimal,3).$this->degreeUnicode.' '.$direction;
	}

	/**
	* Check, if the given angle value is in the allowed range of the given direction value.
	* @return a key-value-pair with key out of [lat long] and value as a 3-value-array of the
	* input parameters with keys deg, min, sec, dir. If the functions could not match neither
	* latitude nor longitude, the functions return an error message.
	*/
	function CheckLatLong($degree, $min, $sec, $direction) {
		$decimalValue = $this->Angle2Decimal($degree, $min, $sec);

		switch ($direction) {
			case wfMsgForContent('smw_abb_east'): case wfMsgForContent('smw_abb_west'):
				if (($decimalValue < 0) or ($decimalValue > 180)) {
					return  wfMsgForContent('smw_err_longitude', $decimalValue);
				} else {
					return array('long'=>array('deg'=>$degree, 'min'=>$min, 'sec'=>$sec, 'dir'=>$direction), 'lat'=>'');
				}
			case wfMsgForContent('smw_abb_south'): case wfMsgForContent('smw_abb_north'):
				if (($decimalValue < 0) or ($decimalValue > 90)) {
					return wfMsgForContent('smw_err_latitude', $decimalValue);
				} else {
					return array('lat'=>array('deg'=>$degree, 'min'=>$min, 'sec'=>$sec, 'dir'=>$direction), 'long'=>'');
				}
			default:
				return wfMsgForContent('smw_err_noDirection', $decimalValue);
		}
	}

	/**
	* @param $input Any supported string representing a geographic location
	* @return A 4-value-array with keys 'deg', 'min', 'sec' and 'dir' with values for
	* an angle representation of a geographic loction. Values for degree and
	* minute part are given as integers, the second part could be given as a
	* float. If the given string could not be parsed, the function returns an error message.
	*/
	function ParseLatOrLongString($input) {
		$pDirection  = '('.$this->pSpace.'[' . wfMsgForContent('smw_abb_north') . '|'
		                                     . wfMsgForContent('smw_abb_east') . '|'
		                                     . wfMsgForContent('smw_abb_west') . '|'
		                                     . wfMsgForContent('smw_abb_south') . '])';

		// Check syntax structure and perform conversions
		// Assumption: Direction is last information
		$arr= preg_split('/('.$this->pDegreeUnit.'|'.$this->pMinUnit.'|'.$this->pSecUnit.')/', $input);
		if (preg_match($pDirection, $arr[1])) { // Syntax like x.xxx° X
			$angelRep = $this->DecimalDegree2Angle($arr[0]);
			$angelRep['dir'] = $arr[1];
		}
		else if (preg_match($pDirection, $arr[2])) { //Syntax like x.xxx°x.xxx′ X
			$angelRep = $this->DecimalDegree2Angle($arr[0]);
			$minRep   = $this->DecimalMinute2Angle($arr[1]);
			$angelRep['min'] += $minRep['min'];
			$angelRep['sec'] += $minRep['sec'];
			$angelRep['dir']  = $arr[2];
		} else { //Syntax like x.xxx°x.xxx′x.xxx′′ X
			$angelRep = $this->DecimalDegree2Angle($arr[0]);
			$minRep   = $this->DecimalMinute2Angle($arr[1]);
			$angelRep['min'] += $minRep['min'];
			$angelRep['sec'] += $minRep['sec'] + $arr[2];
			// If " is used for seconds, direction is in $arr[3]
			$angelRep['dir']  = ($arr[3]== '')? $arr[4] : $arr[3];
		}

		if (($angelRep['deg'] !== '') and ($angelRep['min'] !== '') and ($angelRep['sec'] !== '') and ($angelRep['dir'] !== '')) {
			return array('deg'=>$angelRep['deg'], 'min'=>$angelRep['min'], 'sec'=>$angelRep['sec'], 'dir'=>strtoupper(trim($angelRep['dir'])));
		} else {
			return wfMsgForContent('smw_err_parsingLatLong', $input);
		}
	}

	/**
	* @param $input Any supported string representing a geographic location
	* @return A 2-value-array with keys 'lat' and 'long'. Both values are given
	* by a 4-value-array with keys 'deg', 'min', 'sec' and 'dir' with values for
	* an angle representation of a geographic location. Values for degree and
	* minute part are given as integers, the seconds could be given as a float. 
	* If the given string could not be parsed, the function returns an error message.
	*/
	function ParseGeoLocationString($input) {
		$pDirection  = '('.$this->pSpace.'[' . wfMsgForContent('smw_abb_north') . '|'
		                                     . wfMsgForContent('smw_abb_east') . '|'
		                                     . wfMsgForContent('smw_abb_west') . '|'
		                                     . wfMsgForContent('smw_abb_south') . '])';

		// matches single geo coordinate (latitude or longitude)
		$this->pSingleGeoCord = $this->pFloat . $this->pDegreeUnit . '(' . $this->pFloat . $this->pMinUnit . '(' . $this->pFloat . $this->pSecUnit . ')?)?' . $pDirection;

		// Check for roughly right syntax for geo location
		if (!preg_match('/'.$this->pSingleGeoCord.$this->pCoordDelim.$this->pSingleGeoCord.'/', $input)) {
			return wfMsgForContent('smw_err_wrongSyntax', $input);
		}

		// Check syntax structure and perform conversions

		/*************
		* Step 1:****
		* Assumption: Latitude and Longitude are seperated by a member of
		*             [, ; / -] given by pattern "$this->pCoordDelim".
		*************/
		$locations = preg_split($this->pCoordDelim, $input);
		if (count($locations) != 2) {
			return wfMsgForContent('smw_err_sepSyntax', $input);
		}
		$location1 = $this->ParseLatOrLongString($locations[0]);
		$location2 = $this->ParseLatOrLongString($locations[1]);

		if (!is_array($location1)) {
			return $location1;
		}
		if (!is_array($location2)) {
			return $location2;
		}

		/************
		* Step 2:****
		* Assumption: Latitude and Longitude are seperated and given by "$location1"
		*             and "location2" in the common array format for an angle representation.
		*             Now we have to check, if both latitude and longitude are given and which
		*             location represents latitude and which represents longitude.
		*************/
		$lat = null;
		$long = null;

		$latLong = $this->CheckLatLong($location1['deg'], $location1['min'], $location1['sec'], $location1['dir']);
		if (!is_array($latLong)) return $latLong;
		if ($latLong['lat'] != null) $lat = $latLong['lat'];
		else $long = $latLong['long'];

		$latLong = $this->CheckLatLong($location2['deg'], $location2['min'], $location2['sec'], $location2['dir']);
		if (!is_array($latLong)) return $latLong;
		if ($latLong['lat'] != null) $lat = $latLong['lat'];
		else $long = $latLong['long'];

		if (!is_array($lat) or !is_array($long)) return wfMsgForContent('smw_err_notBothGiven');
		/************
		* Step 3:****
		* Assumption: At this point, longitude and latitude are given by "$lat"
		*             and "$long". So we can return an array with the values.
		*************/

		return array('lat'=>$lat, 'long'=>$long);
	}

	function isNumeric() {
		return FALSE;
	}
}



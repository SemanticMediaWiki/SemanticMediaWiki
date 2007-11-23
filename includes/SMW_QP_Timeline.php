<?php
/**
 * Print query results in interactive timelines.
 * @author Markus Krötzsch
 */


/**
 * New implementation of SMW's printer for timeline data.
 *
 * @note AUTOLOADED
 */
class SMWTimelineResultPrinter extends SMWResultPrinter {

	protected $m_tlstart = '';  // name of the start-date property if any
	protected $m_tlend = '';  // name of the end-date property if any
	protected $m_tlsize = ''; // CSS-compatible size (such as 400px)
	protected $m_tlbands = ''; // array of band IDs (MONTH, YEAR, ...)
	protected $m_tlpos = ''; // position identifier (start, end, today, middle)

	protected function readParameters($params,$outputmode) {
		SMWResultPrinter::readParameters($params,$outputmode);

		if (array_key_exists('timelinestart', $params)) {
			$this->m_tlstart = smwfNormalTitleDBKey($params['timelinestart']);
		}
		if (array_key_exists('timelineend', $params)) {
			$this->m_tlend = smwfNormalTitleDBKey($params['timelineend']);
		}
		if (array_key_exists('timelinesize', $params)) {
			$this->m_tlsize = htmlspecialchars(str_replace(';', ' ', strtolower(trim($params['timelinesize'])))); 
			// str_replace makes sure this is only one value, not mutliple CSS fields (prevent CSS attacks)
			/// FIXME: this is either unsafe or redundant, since Timeline is Wiki-compatible. If the JavaScript makes user inputs to CSS then it is bad even if we block this injection path.
		} else {
			$this->m_tlsize = '300px';
		}
		if (array_key_exists('timelinebands', $params)) { 
		//check for band parameter, should look like "DAY,MONTH,YEAR"
			$this->m_tlbands = preg_split('/[,][\s]*/',trim($params['timelinebands']));
		} else {
			$this->m_tlbands = array('MONTH','YEAR'); /// TODO: check what default the JavaScript uses
		}
		if (array_key_exists('timelineposition', $params)) {
			$this->m_tlpos = strtolower(trim($params['timelineposition']));
		} else {
			$this->m_tlpos = 'middle';
		}
	}

	protected function getResultText($res, $outputmode) {
		global $smwgIQRunningNumber;
		smwfRequireHeadItem(SMW_HEADER_TIMELINE); //make sure JavaScripts are available

		$eventline =  ('eventline' == $this->mFormat);

		if ( !$eventline && ($this->m_tlstart == '') ) { // seek defaults
			foreach ($res->getPrintRequests() as $pr) {
				if ( ($pr->getMode() == SMW_PRINT_PROP) && ($pr->getTypeID() == '_dat') ) {
					if ( ($this->m_tlend == '') && ($this->m_tlstart != '') &&
					     ($this->m_tlstart != $pr->getTitle()->getDBKey()) ) {
						$this->m_tlend = $pr->getTitle()->getDBKey();
					} elseif ( ($this->m_tlstart == '') && ($this->m_tlend != $pr->getTitle()->getDBKey()) ) {
						$this->m_tlstart = $pr->getTitle()->getDBKey();
					}
				}
			}
		}

		// print header
		$result = "<div class=\"smwtimeline\" id=\"smwtimeline$smwgIQRunningNumber\" style=\"height: $this->m_tlsize\">";
		$result .= '<span class="smwtlcomment">' . wfMsgForContent('smw_iq_nojs',$res->getQueryURL()) . '</span>'; // note for people without JavaScript

		foreach ($this->m_tlbands as $band) {
			$result .= '<span class="smwtlband">' . htmlspecialchars($band) . '</span>';
			//just print any "band" given, the JavaScript will figure out what to make of it
		}

		// print all result rows
		$positions = array(); // possible positions, collected to select one for centering
		$curcolor = 0; // color cycling is used for eventline
		if ( ($this->m_tlstart != '') || $eventline ) {
			$output = false; // true if output for the popup was given on current line
			if ($eventline) $events = array(); // array of events that are to be printed
			while ( $row = $res->getNext() ) {
				$hastime = false; // true as soon as some startdate value was found
				$hastitle = false; // true as soon as some label for the event was found
				$curdata = ''; // current *inner* print data (within some event span)
				$curmeta = ''; // current event meta data
				$curarticle = ''; // label of current article, if it was found; needed only for eventline labeling
				$first_col = true;
				foreach ($row as $field) {
					$first_value = true;
					$pr = $field->getPrintRequest();
					while ( ($object = $field->getNextObject()) !== false ) {
						$l = $this->getLinker($first_col);
						$objectlabel = $object->getShortText($outputmode,$l);
						$urlobject =  ($l !== NULL);
						$header = '';
						if ($first_value) {
							// find header for current value:
							if ( $this->mShowHeaders && ('' != $pr->getLabel()) ) {
								$header = $pr->getText($outputmode,$this->mLinker) . ' ';
							}
							// is this a start date?
							if ( ($pr->getMode() == SMW_PRINT_PROP) && 
							     ($pr->getTitle()->getDBKey() == $this->m_tlstart) ) {
								//FIXME: Timeline scripts should support XSD format explicitly. They
								//currently seem to implement iso8601 which deviates from XSD in cases.
								//NOTE: We can assume $object to be an SMWDataValue in this case.
								$curmeta .= '<span class="smwtlstart">' . $object->getXSDValue() . '</span>';
								$positions[$object->getNumericValue()] = $object->getXSDValue();
								$hastime = true;
							}
							// is this the end date?
							if ( ($pr->getMode() == SMW_PRINT_PROP) && 
							     ($pr->getTitle()->getDBKey() == $this->m_tlend) ) {
								//NOTE: We can assume $object to be an SMWDataValue in this case.
								$curmeta .= '<span class="smwtlend">' . $object->getXSDValue() . '</span>';
							}
							// find title for displaying event
							if ( !$hastitle ) {
								if ($urlobject) {
									$curmeta .= '<span class="smwtlurl">' . $objectlabel . '</span>';
								} else {
									$curmeta .= '<span class="smwtltitle">' . $objectlabel . '</span>';
								}
								if ( ($pr->getMode() == SMW_PRINT_THIS) ) {
									// NOTE: type Title of $object implied
									$curarticle = $object->getText();
								}
								$hastitle = true;
							}
						} elseif ($output) $curdata .= ', '; //it *can* happen that output is false here, if the subject was not printed (fixed subject query) and mutliple items appear in the first row
						if (!$first_col || !$first_value || $eventline) {
							$curdata .= $header . $objectlabel;
							$output = true;
						}
						if ($eventline && ($pr->getMode() == SMW_PRINT_PROP) && ($pr->getTypeID() == '_dat') && ('' != $pr->getLabel()) && ($pr->getTitle()->getText() != $this->m_tlstart) && ($pr->getTitle()->getText() != $this->m_tlend) ) {
							$events[] = array($object->getXSDValue(), $pr->getLabel(), $object->getNumericValue());
						}
						$first_value = false;
					}
					if ($output) $curdata .= "<br />";
					$output = false;
					$first_col = false;
				}

				if ( $hastime ) {
					$result .= '<span class="smwtlevent">' . $curmeta . '<span class="smwtlcoloricon">' . $curcolor . '</span>' . $curdata . '</span>';
				}
				if ( $eventline ) {
					foreach ($events as $event) {
						$result .= '<span class="smwtlevent"><span class="smwtlstart">' . $event[0] . '</span><span class="smwtlurl">' . $event[1] . '</span><span class="smwtlcoloricon">' . $curcolor . '</span>';
						if ( $curarticle != '' ) $result .= '<span class="smwtlprefix">' . $curarticle . ' </span>';
						$result .=  $curdata . '</span>';
						$positions[$event[2]] = $event[0];
					}
					$events = array();
					$curcolor = ($curcolor + 1) % 10;
				}
			}
			if (count($positions) > 0) {
				ksort($positions);
				$positions = array_values($positions);
				switch ($this->m_tlpos) {
					case 'start':
						$result .= '<span class="smwtlposition">' . $positions[0] . '</span>';
						break;
					case 'end':
						$result .= '<span class="smwtlposition">' . $positions[count($positions)-1] . '</span>';
						break;
					case 'today': break; // default
					case 'middle': default:
						$result .= '<span class="smwtlposition">' . $positions[ceil(count($positions)/2)-1] . '</span>';
						break;
				}
			}
		}
		//no further results displayed ...

		// print footer
		$result .= "</div>";
		return $result;
	}
}

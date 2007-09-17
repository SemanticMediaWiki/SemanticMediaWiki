<?php
/**
 * This file provides various classes that print query results.
 * @author Markus Krötzsch
 */

/**
 * Abstract base class for SMW's novel query printing mechanism. It implements
 * part of the former functionality of SMWInlineQuery (everything related to
 * output formatting and the correspoding parameters) and is subclassed by concrete
 * printers that provide the main formatting functionality.
 */
abstract class SMWResultPrinter {

	// parameters:
	protected $mFormat;  // a string identifier describing a valid format
	protected $mIntro = ''; // text to print before the output in case it is *not* empty
	protected $mSearchlabel = NULL; // text to use for link to further results, or empty if link should not be shown
	protected $mLinkFirst; // should article names of the first column be linked?
	protected $mLinkOthers; // should article names of other columns (besides the first) be linked?
	protected $mDefault = ''; // default return value for empty queries
	protected $mShowHeaders = true; // should the headers (property names) be printed?
	protected $mInline; // is this query result "inline" in some page (only then a link to unshown results is created, error handling may also be affected)
	protected $mLinker; // Linker object as needed for making result links. Might come from some skin at some time.

	/**
	 * Constructor. The parameter $format is a format string
	 * that may influence the processing details.
	 */
	public function SMWResultPrinter($format, $inline) {
		global $smwgQDefaultLinking;
		$this->mFormat = $format;
		$this->mInline = $inline;
		$this->mLinkFirst = ($smwgQDefaultLinking != 'none');
		$this->mLinkOthers = ($smwgQDefaultLinking == 'all');
		$this->mLinker = new Linker(); ///TODO: how can we get the default or user skin here (depending on context)?
	}

	/**
	 * Main entry point: takes an SMWQueryResult and parameters
	 * given as key-value-pairs in an array and returns the 
	 * serialised version of the results, formatted as inline HTML
	 * or (for special printers) as RDF or XML or whatever. Normally
	 * not overwritten by subclasses.
	 */
	public function getResultHTML($results, $params) {
		$this->readParameters($params);
		if ($results->getCount() == 0) {
			return htmlspecialchars($this->mDefault);
		}
		return $this->getHTML($results);
	}

	/**
	 * Read an array of parameter values given as key-value-pairs and
	 * initialise internal member fields accordingly. Possibly overwritten
	 * (extended) by subclasses.
	 */
	protected function readParameters($params) {
		if (array_key_exists('intro', $params)) {
			$this->mIntro = htmlspecialchars(str_replace('_', ' ', $params['intro']));
		}
		if (array_key_exists('searchlabel', $params)) {
			$this->mSearchlabel = htmlspecialchars($params['searchlabel']);
		}
		if (array_key_exists('link', $params)) {
			switch (strtolower($params['link'])) {
			case 'head': case 'subject':
				$this->mLinkFirst = true;
				$this->mLinkOthers  = false;
				break;
			case 'all':
				$this->mLinkFirst = true;
				$this->mLinkOthers  = true;
				break;
			case 'none':
				$this->mLinkFirst = false;
				$this->mLinkOthers  = false;
				break;
			}
		}
		if (array_key_exists('default', $params)) {
			$this->mDefault = htmlspecialchars(str_replace('_', ' ', $params['default']));
		}
		if (array_key_exists('headers', $params)) {
			if ( 'hide' == strtolower($params['headers'])) {
				$this->mShowHeaders = false;
			} else {
				$this->mShowHeaders = true;
			}
		}
	}

	/**
	 * Return HTML version of serialised results.
	 * Implemented by subclasses.
	 */
	abstract protected function getHTML($res);

	/**
	 * Depending on current linking settings, returns a linker object
	 * for making hyperlinks or NULL if no links should be created.
	 *
	 * @param $firstrow True of this is the first result row (having special linkage settings).
	 */
	protected function getLinker($firstcol = false) {
		if ( ($firstcol && $this->mLinkFirst) || (!$firstcol && $this->mLinkOthers) ) {
			return $this->mLinker;
		} else {
			return NULL;
		}
	}

	/**
	 * Provides a simple formatted string of all the error messages that occurred.
	 * Can be used if not specific error formatting is desired. Compatible with HTML
	 * and Wiki.
	 */
	protected function getErrorString($res) {
		return smwfEncodeMessages($res->getErrors());
	}


}

/**
 * New implementation of SMW's printer for result tables.
 */
class SMWTableResultPrinter extends SMWResultPrinter {

	protected function getHTML($res) {
		global $smwgIQRunningNumber;

		

		// print header
		if ('broadtable' == $this->mFormat)
			$widthpara = ' width="100%"';
		else $widthpara = '';
		$result = $this->mIntro .
		          "<table class=\"smwtable\"$widthpara id=\"querytable" . $smwgIQRunningNumber . "\">\n";
		if ($this->mShowHeaders) { // building headers
			$result .= "\n\t\t<tr>";
			foreach ($res->getPrintRequests() as $pr) {
				$result .= "\t\t\t<th>" . $pr->getHTMLText($this->mLinker) . "</th>\n";
			}
			$result .= "\n\t\t</tr>";
		}

		// print all result rows
		while ( $row = $res->getNext() ) {
			$result .= "\t\t<tr>\n";
			$firstcol = true;
			foreach ($row as $field) {
				$result .= "<td>";
				$first = true;
				while ( ($text = $field->getNextHTMLText($this->getLinker($firstcol))) !== false ) {
					if ($first) $first = false; else $result .= '<br />';
					$result .= $text;
				}
				$result .= "</td>";
				$firstcol = false;
			}
			$result .= "\n\t\t</tr>\n";
		}

		// print further results footer
		if ($this->mInline && $res->hasFurtherResults()) {
			$label = $this->mSearchlabel;
			if ($label === NULL) { //apply default
				$label = wfMsgForContent('smw_iq_moreresults');
			}
			if ($label != '') {
				$result .= "\n\t\t<tr class=\"smwfooter\"><td class=\"sortbottom\" colspan=\"" . $res->getColumnCount() . '"> <a href="' . $res->getQueryURL() . '">' . $label . '</a></td></tr>';
			}
		}
		$result .= "\t</table>"; // print footer
		$result .= $this->getErrorString($res); // just append error messages
		return $result;
	}
}

/**
 * New implementation of SMW's printer for results in lists.
 *
 * Somewhat confusing code, since one has to iterate through lists, inserting texts 
 * in between their elements depending on whether the element is the first that is 
 * printed, the first that is printed in parentheses, or the last that will be printed.
 * Maybe one could further simplify this.
 */
class SMWListResultPrinter extends SMWResultPrinter {

	protected $mSep = '';
	protected $mTemplate = '';

	protected function readParameters($params) {
		SMWResultPrinter::readParameters($params);

		if (array_key_exists('sep', $params)) {
			$this->mSep = htmlspecialchars(str_replace('_',' ',$params['sep']));
		}
		if (array_key_exists('template', $params)) {
			$this->mTemplate = $params['template'];
		}
	}

	protected function getHTML($res) {
		global $wgTitle,$smwgStoreActive;
		// print header
		$result = $this->mIntro;
		if ( ('ul' == $this->mFormat) || ('ol' == $this->mFormat) ) {
			$result .= '<' . $this->mFormat . '>';
			$footer = '</' . $this->mFormat . '>';
			$rowstart = "\n\t<li>";
			$rowend = '</li>';
			$plainlist = false;
		} else {
			if ($this->mSep != '') {
				$listsep = $this->mSep;
				$finallistsep = $listsep;
			} else {  // default list ", , , and, "
				$listsep = ', ';
				$finallistsep = wfMsgForContent('smw_finallistconjunct') . ' ';
			}
			$footer = '';
			$rowstart = '';
			$rowend = '';
			$plainlist = true;
		}

		if ($this->mTemplate != '') {
			$parser_options = new ParserOptions();
			$parser_options->setEditSection(false);  // embedded sections should not have edit links
			$parser = new Parser();
			$usetemplate = true;
		} else {
			$usetemplate = false;
		}

		// print all result rows
		$first_row = true;
		$row = $res->getNext();
		while ( $row !== false ) {
			$nextrow = $res->getNext(); // look ahead
			if ( !$first_row && $plainlist )  {
				if ($nextrow !== false) $result .= $listsep; // the comma between "rows" other than the last one
				else $result .= $finallistsep;
			} else $result .= $rowstart;

			$first_col = true;
			if ($usetemplate) { // build template code
				$wikitext = '';
				foreach ($row as $field) {
					$wikitext .= "|";
					$first_value = true;
					while ( ($text = $field->getNextWikiText($this->getLinker($first_col))) !== false ) {
						if ($first_value) $first_value = false; else $wikitext .= ', ';
						$wikitext .= $text;
					}
					$first_col = false;
				}
				$result .= '{{' . $this->mTemplate . str_replace(array('=','|'), array('&#x003D;', '&#x007C;'), $wikitext) . '}}'; // encode '=' and '|' for use in templates (templates fail otherwise)
			} else {  // build simple list
				$first_col = true;
				$found_values = false; // has anything but the first column been printed?
				foreach ($row as $field) {
					$first_value = true;
					while ( ($text = $field->getNextHTMLText($this->getLinker($first_col))) !== false ) {
						if (!$first_col && !$found_values) { // first values after first column
							$result .= ' (';
							$found_values = true;
						} elseif ($found_values || !$first_value) {
						// any value after '(' or non-first values on first column
							$result .= ', ';
						}
						if ($first_value) { // first value in any column, print header
							$first_value = false;
							if ( $this->mShowHeaders && ('' != $field->getPrintRequest()->getLabel()) ) {
								$result .= $field->getPrintRequest()->getHTMLText($this->mLinker) . ' ';
							}
						}
						$result .= $text; // actual output value
					}
					$first_col = false;
				}
				if ($found_values) $result .= ')';
			}
			$result .= $rowend;
			$first_row = false;
			$row = $nextrow;
		}

		if ($usetemplate) {
			$old_smwgStoreActive = $smwgStoreActive;
			$smwgStoreActive = false; // no annotations stored, no factbox printed
			$parserOutput = $parser->parse($result, $wgTitle, $parser_options);
			$result = $parserOutput->getText();
			$smwgStoreActive = $old_smwgStoreActive;
		}

		if ($this->mInline && $res->hasFurtherResults()) {
			$label = $this->mSearchlabel;
			if ($label === NULL) { //apply defaults
				if ('ol' == $this->mFormat) $label = '';
				else $label = wfMsgForContent('smw_iq_moreresults');
			}
			if (!$first_row) $result .= ' '; // relevant for list, unproblematic for ul/ol
			if ($label != '') {
				$result .= $rowstart . '<a href="' . $res->getQueryURL() . '">' . $label . '</a>' . $rowend;
			}
		}

		// print footer
		$result .= $footer;
		$result .= $this->getErrorString($res); // just append error messages

		return $result;
	}
}


/**
 * New implementation of SMW's printer for timeline data.
 */
class SMWTimelineResultPrinter extends SMWResultPrinter {

	protected $m_tlstart = '';  // name of the start-date property if any
	protected $m_tlend = '';  // name of the end-date property if any
	protected $m_tlsize = ''; // CSS-compatible size (such as 400px)
	protected $m_tlbands = ''; // array of band IDs (MONTH, YEAR, ...)
	protected $m_tlpos = ''; // position identifier (start, end, today, middle)

	protected function readParameters($params) {
		SMWResultPrinter::readParameters($params);

		if (array_key_exists('timelinestart', $params)) {
			$this->m_tlstart = smwfNormalTitleDBKey($params['timelinestart']);
		}
		if (array_key_exists('timelineend', $params)) {
			$this->m_tlend = smwfNormalTitleDBKey($params['timelineend']);
		}
		if (array_key_exists('timelinesize', $params)) {
			$this->m_tlsize = htmlspecialchars(str_replace(';', ' ', strtolower($params['timelinesize']))); 
			// str_replace makes sure this is only one value, not mutliple CSS fields (prevent CSS attacks)
		} else {
			$this->m_tlsize = '300px';
		}
		if (array_key_exists('timelinebands', $params)) { 
		//check for band parameter, should look like "DAY,MONTH,YEAR"
			$this->m_tlbands = preg_split('/[,][\s]*/',$params['timelinebands']);
		} else {
			$this->m_tlbands = array('MONTH','YEAR'); // TODO: check what default the JavaScript uses
		}
		if (array_key_exists('timelineposition', $params)) {
			$this->m_tlpos = strtolower($params['timelineposition']);
		} else {
			$this->m_tlpos = 'middle';
		}
	}

	public function getHTML($res) {
		global $smwgIQRunningNumber;

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
						$objectlabel = $object->getShortHTMLText($l);
						$urlobject =  ($l !== NULL);
						$header = '';
						if ($first_value) {
							// find header for current value:
							if ( $this->mShowHeaders && ('' != $pr->getLabel()) ) {
								$header = $pr->getHTMLText($this->mLinker) . ' ';
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
		$result .= $this->getErrorString($res); // just append error messages
		return $result;
	}
}


/**
 * Printer for template data. Passes a result row as anonymous parameters to
 * a given template (which might ignore them or not) and prints the result.
 */
class SMWTemplateResultPrinter extends SMWResultPrinter {

	protected $m_template;

	protected function readParameters($params) {
		SMWResultPrinter::readParameters($params);

		if (array_key_exists('template', $params)) {
			$this->m_template = $params['template'];
		} else {
			$this->m_template = false;
		}
	}

	public function getHTML($res) {
		// handle factbox
		global $smwgStoreActive, $wgTitle;

		// print all result rows
		if ($this->m_template == false) {
			return 'Please provide parameter "template" for query to work.'; // TODO: internationalise, beautify
		}

		$old_smwgStoreActive = $smwgStoreActive;
		$smwgStoreActive = false; // no annotations stored, no factbox printed

		$parserinput = $this->mIntro;

		$parser_options = new ParserOptions();
		$parser_options->setEditSection(false);  // embedded sections should not have edit links
		$parser = new Parser();
		while ( $row = $res->getNext() ) {
			$wikitext = '';
			$firstcol = true;
			foreach ($row as $field) {
				$wikitext .= "|";
				$first = true;
				while ( ($text = $field->getNextWikiText($this->getLinker($firstcol))) !== false ) {
					if ($first) {
						$first = false; 
					} else {
						$wikitext .= ', ';
					}
					$wikitext .= $text;
				}
				$firstcol = false;
			}
			$parserinput .= '{{' . $this->m_template . str_replace(array('=','|'), array('&#x003D;', '&#x007C;'), $wikitext) . '}}'; // encode '=' and '|' for use in templates (templates fail otherwise)
		}
		$parserOutput = $parser->parse($parserinput, $wgTitle, $parser_options);
		$result = $parserOutput->getText();
		// show link to more results
		if ($this->mInline && $res->hasFurtherResults()) {
			$label = $this->mSearchlabel;
			if ($label === NULL) { //apply defaults
				$label = wfMsgForContent('smw_iq_moreresults');
			}
			if ($label != '') {
				$result .= '<a href="' . $res->getQueryURL() . '">' . $label . '</a>';
			}
		}

		$smwgStoreActive = $old_smwgStoreActive;
		$result .= $this->getErrorString($res); // just append error messages
		return $result;
	}
}


/**
 * Printer for embedded data.
 * Embeds in the page output the contents of the pages in the query result set.
 * Only the first column of the query is considered. If it is a page reference then that page's contents is embedded.
 * The optional "titlestyle" formatting parameter can be used to apply a format to the headings for the page titles.
 * If "titlestyle" is not specified, a <h1> tag is used.
 * @author Fernando Correia
 * @author Markus Krötzsch
 */
class SMWEmbeddedResultPrinter extends SMWResultPrinter {

	protected $m_showhead;
	protected $m_embedformat;

	protected function readParameters($params) {
		SMWResultPrinter::readParameters($params);

		if (array_key_exists('embedonly', $params)) {
			$this->m_showhead = false;
		} else {
			$this->m_showhead = true;
		}
		if (array_key_exists('embedformat', $params)) {
			$this->m_embedformat = $params['embedformat'];
		} else {
			$this->m_embedformat = 'h1';
		}
	}

	public function getHTML($res) {
		// handle factbox
		global $smwgStoreActive, $wgTitle;
		$old_smwgStoreActive = $smwgStoreActive;
		$smwgStoreActive = false; // no annotations stored, no factbox printed

		// print header
		$result = $this->mIntro;

		switch ($this->m_embedformat) {
			case 'h1': case 'h2': case 'h3': case 'h4': case 'h5': case 'h6':
				$footer = '';
				$embstart = '';
				$headstart = '<' . $this->m_embedformat . '>';
				$headend = '</' . $this->m_embedformat . ">\n";
				$embend = '';
			break;
			case 'ul': case 'ol':
				$result .= '<' . $this->m_embedformat . '>';
				$footer = '</' . $this->m_embedformat . '>';
				$embstart = '<li>';
				$headstart = '';
				$headend = "<br />\n";
				$embend = "</li>\n";
			break;
		}

		// print all result rows
		$parser_options = new ParserOptions();
		$parser_options->setEditSection(false);  // embedded sections should not have edit links
		$parser = new Parser();

		while (  $row = $res->getNext() ) {
			$first_col = true;
			foreach ($row as $field) {
				if ( $field->getPrintRequest()->getTypeID() == '_wpg' ) { // ensure that we deal with title-likes
					while ( ($object = $field->getNextObject()) !== false ) {
						$result .= $embstart;
						$text= $object->getLongHTMLText($this->getLinker(true));
						if ($this->m_showhead) {
							$result .= $headstart . $text . $headend;
						}
						if ($object->getNamespace() == NS_MAIN) {
							$articlename = ':' . $object->getText();
						} else {
							$articlename = $object->getPrefixedText();
						}
						$parserOutput = $parser->parse('{{' . $articlename . '}}', $wgTitle, $parser_options);
						$result .= $parserOutput->getText();
						$result .= $embend;
					}
				}
				break;  // only use first column for now
			}
		}

		// show link to more results
		if ($this->mInline && $res->hasFurtherResults()) {
			$label = $this->mSearchlabel;
			if ($label === NULL) { //apply defaults
				$label = wfMsgForContent('smw_iq_moreresults');
			}
			if ($label != '') {
				$result .= $embstart . '<a href="' . $res->getQueryURL() . '">' . $label . '</a>' . $embend ;
			}
		}
		$result .= $footer;
		$result .= $this->getErrorString($res); // just append error messages

		$smwgStoreActive = $old_smwgStoreActive;
		return $result;
	}
}

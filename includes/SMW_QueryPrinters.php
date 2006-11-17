<?php
/**
 * This file provides various classes that print query results. 
 */

/**
 * Interface (abstract class) that must be implemented by all printers for inline
 * query results.
 */
interface SMWQueryPrinter {
	/**
	 * Print all results and return an output string. This method needs to call back to
	 * the query object for fetching data.
	 */
	public function printResult();
}

/**
 * Printer for tabular data.
 */
class SMWTablePrinter implements SMWQueryPrinter {
	private $mIQ; // the querying object that called the printer
	private $mQuery; // the query that was executed and whose results are to be printed

	public function SMWTablePrinter($iq, $query) {
		$this->mIQ = $iq;
		$this->mQuery = $query;
	}
	
	public function printResult() {
		global $smwgIQRunningNumber;

		// print header
		if ('broadtable' == $this->mIQ->getFormat())
			$widthpara = ' width="100%"';
		else $widthpara = '';
		$result = $this->mIQ->getIntro() . "<table class=\"smwtable\"$widthpara id=\"querytable" . $smwgIQRunningNumber . "\">\n";
		if ($this->mIQ->showHeaders()) {
			$result .= "\n\t\t<tr>";
			foreach ($this->mQuery->mPrint as $print_data) {
				$result .= "\t\t\t<th>" . $print_data[0] . "</th>\n";
			}
			$result .= "\n\t\t</tr>";
		}

		// print all result rows
		while ( $row = $this->mIQ->getNextRow() ) {
			$result .= "\t\t<tr>\n";
			$firstcol = true;
			foreach ($this->mQuery->mPrint as $print_data) {
				$iterator = $this->mIQ->getIterator($print_data,$row,$firstcol);
				$result .= "<td>";
				$first = true;
				while ($cur = $iterator->getNext()) {
					if ($first) $first = false; else $result .= '<br />';
					$result .= $cur;
				}
				$result .= "</td>";
				$firstcol = false;
			}
			$result .= "\n\t\t</tr>\n";
		}

		if ($this->mIQ->isInline() && $this->mIQ->hasFurtherResults()) {
			$label = $this->mIQ->getSearchLabel();
			if ($label === NULL) { //apply default
				$label = wfMsgForContent('smw_iq_moreresults');
			}
			if ($label != '') {
				$result .= "\n\t\t<tr class=\"smwfooter\"><td class=\"sortbottom\" colspan=\"" . count($this->mQuery->mPrint) . '\"> <a href="' . $this->mIQ->getQueryURL() . '">' . $label . '</a></td></tr>';
			}
		}

		// print footer
		$result .= "\t</table>";

		return $result;
	}
}

/**
 * Printer for list data. Somewhat confusing code, since one has to iterate through lists,
 * inserting texts in between their elements depending on whether the element is the first
 * that is printed, the first that is printed in parentheses, or the last that will be printed.
 * Maybe one could further simplify this.
 */
class SMWListPrinter implements SMWQueryPrinter {
	private $mIQ; // the querying object that called the printer
	private $mQuery; // the query that was executed and whose results are to be printed

	public function SMWListPrinter($iq, $query) {
		$this->mIQ = $iq;
		$this->mQuery = $query;
	}
	
	public function printResult() {
		// print header
		$result = $this->mIQ->getIntro();
		if ( ('ul' == $this->mIQ->getFormat()) || ('ol' == $this->mIQ->getFormat()) ) {
			$result .= '<' . $this->mIQ->getFormat() . '>';
			$footer = '</' . $this->mIQ->getFormat() . '>';
			$rowstart = "\n\t<li>";
			$rowend = '</li>';
			$plainlist = false;
		} else {
			$params = $this->mIQ->getParameters();
			if (array_key_exists('sep', $params)) {
				$listsep = htmlspecialchars(str_replace('_', ' ', $params['sep']));
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

		// print all result rows
		$first_row = true;
		$row = $this->mIQ->getNextRow();
		while ( $row ) {
			$nextrow = $this->mIQ->getNextRow(); // look ahead
			if ( !$first_row && $plainlist )  {
				if ($nextrow) $result .= $listsep; // the comma between "rows" other than the last one
				else $result .= $finallistsep;
			} else $result .= $rowstart;

			$first_col = true;
			$found_values = false; // has anything but the first coolumn been printed?
			foreach ($this->mQuery->mPrint as $print_data) {
				$iterator = $this->mIQ->getIterator($print_data,$row,$first_col);
				$first_value = true;
				while ($cur = $iterator->getNext()) {
					if (!$first_col && !$found_values) { // first values after first column
						$result .= ' (';
						$found_values = true;
					} elseif ($found_values || !$first_value) { 
					  // any value after '(' or non-first values on first column
						$result .= ', ';
					}
					if ($first_value) { // first value in any column, print header
						$first_value = false;
						if ( $this->mIQ->showHeaders() && ('' != $print_data[0]) ) {
							$result .= $print_data[0] . ' ';
						}
					}
					$result .= $cur; // actual output value
				}
				$first_col = false;
			}
			if ($found_values) $result .= ')';
			$result .= $rowend;
			$first_row = false;
			$row = $nextrow;
		}
		
		if ($this->mIQ->isInline() && $this->mIQ->hasFurtherResults()) {
			$label = $this->mIQ->getSearchLabel();
			if ($label === NULL) { //apply defaults
				if ('ol' == $this->mIQ->getFormat()) $label = '';
				else $label = wfMsgForContent('smw_iq_moreresults');
			}
			if (!$first_row) $result .= ' '; // relevant for list, unproblematic for ul/ol
			if ($label != '') {
				$result .= $rowstart . '<a href="' . $this->mIQ->getQueryURL() . '">' . $label . '</a>' . $rowend;
			}
		}

		// print footer
		$result .= $footer;

		return $result;
	}
}

?>
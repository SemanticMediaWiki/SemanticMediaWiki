<?php
/**
 * Print query results in tables.
 * @author Markus Krötzsch
 */

/**
 * New implementation of SMW's printer for result tables.
 *
 * @note AUTOLOADED
 */
class SMWTableResultPrinter extends SMWResultPrinter {

	protected function getResultText($res, $outputmode) {
		global $smwgIQRunningNumber;
		smwfRequireHeadItem(SMW_HEADER_SORTTABLE);

		// print header
		if ('broadtable' == $this->mFormat)
			$widthpara = ' width="100%"';
		else $widthpara = '';
		$result = $this->mIntro .
		          "<table class=\"smwtable\"$widthpara id=\"querytable" . $smwgIQRunningNumber . "\">\n";
		if ($this->mShowHeaders) { // building headers
			$result .= "\t<tr>\n";
			foreach ($res->getPrintRequests() as $pr) {
				$result .= "\t\t<th>" . $pr->getText($outputmode, $this->mLinker) . "</th>\n";
			}
			$result .= "\t</tr>\n";
		}

		// print all result rows
		while ( $row = $res->getNext() ) {
			$result .= "\t<tr>\n";
			$firstcol = true;
			foreach ($row as $field) {
				$result .= "\t\t<td>";
				$first = true;
				while ( ($text = $field->getNextText($outputmode, $this->getLinker($firstcol))) !== false ) {
					if ($first) $first = false; else $result .= '<br />';
					$result .= $text;
				}
				$result .= "</td>\n";
				$firstcol = false;
			}
			$result .= "\t</tr>\n";
		}

		// print further results footer
		if ( $this->mInline && $res->hasFurtherResults() ) {
			$label = $this->mSearchlabel;
			if ($label === NULL) { //apply default
				$label = wfMsgForContent('smw_iq_moreresults');
			}
			if ($label != '') {
				$result .= "\t<tr class=\"smwfooter\"><td class=\"sortbottom\" colspan=\"" . $res->getColumnCount() . '"> ' . $this->getFurtherResultsLink($outputmode,$res,$label) . "</td></tr>\n";
			}
		}
		$result .= "</table>\n"; // print footer
		return $result;
	}

}

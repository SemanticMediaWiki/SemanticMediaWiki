<?php
/**
 * Print query results in tables.
 * @author Markus Krötzsch
 * @file
 * @ingroup SMWQuery
 */

/**
 * New implementation of SMW's printer for result tables.
 *
 * @ingroup SMWQuery
 */
class SMWTableResultPrinter extends SMWResultPrinter {

	public function getName() {
		wfLoadExtensionMessages('SemanticMediaWiki');
		return wfMsg('smw_printername_' . $this->mFormat);
	}

	protected function getResultText($res, $outputmode) {
		global $smwgIQRunningNumber;
		SMWOutputs::requireHeadItem(SMW_HEADER_SORTTABLE);

		$printrequestparameters = array();
		foreach ($res->getPrintRequests() as $pr)
			$printrequestparameters[] = $pr->getParams();
		
		// print header
		$result = '<table class="smwtable"' .
		          ('broadtable' == $this->mFormat?' width="100%"':'') .
				  " id=\"querytable$smwgIQRunningNumber\">\n";
		if ($this->mShowHeaders != SMW_HEADERS_HIDE) { // building headers
			$result .= "\t<tr>\n";
			foreach ($res->getPrintRequests() as $pr) {
				$result .= "\t\t<th>" . $pr->getText($outputmode, ($this->mShowHeaders == SMW_HEADERS_PLAIN?NULL:$this->mLinker) ) . "</th>\n";
			}
			$result .= "\t</tr>\n";
		}

		// print all result rows
		while ( $row = $res->getNext() ) {
			$result .= "\t<tr>\n";
			$firstcol = true;
			$fieldcount = -1;
			foreach ($row as $field) {
				$fieldcount = $fieldcount + 1;
				
				$result .= "\t\t<td";
				if (array_key_exists('align', $printrequestparameters[$fieldcount])) {
					$alignment = $printrequestparameters[$fieldcount]['align'];
					// check the content, otherwise evil people could inject here anything they wanted
					if (($alignment == 'right') || ($alignment == 'left'))   
						$result .= " style=\"text-align:" . $printrequestparameters[$fieldcount]['align'] . ";\"";
				}
				$result .= ">";

				$first = true;
				while ( ($object = $field->getNextObject()) !== false ) {
					if ($first) {
						if ($object->isNumeric()) { // use numeric sortkey
							$result .= '<span class="smwsortkey">' . $object->getNumericValue() . '</span>';
						}
						$first = false;
					} else {
						$result .= '<br />';
					}
					// use shorter "LongText" for wikipage
					$result .= ($object->getTypeID() == '_wpg')?
					           $object->getLongText($outputmode,$this->getLinker($firstcol)):
					           $object->getShortText($outputmode,$this->getLinker($firstcol));
				}
				$result .= "</td>\n";
				$firstcol = false;
			}
			$result .= "\t</tr>\n";
		}

		// print further results footer
		if ( $this->linkFurtherResults($res) ) {
			$link = $res->getQueryLink();
			if ($this->getSearchLabel($outputmode)) {
				$link->setCaption($this->getSearchLabel($outputmode));
			}
			$result .= "\t<tr class=\"smwfooter\"><td class=\"sortbottom\" colspan=\"" . $res->getColumnCount() . '"> ' . $link->getText($outputmode,$this->mLinker) . "</td></tr>\n";
		}
		$result .= "</table>\n"; // print footer
		$this->isHTML = ($outputmode == SMW_OUTPUT_HTML); // yes, our code can be viewed as HTML if requested, no more parsing needed
		return $result;
	}

}

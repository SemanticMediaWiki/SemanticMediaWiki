<?php
/**
 * This file contains a static class for accessing functions to generate and execute
 * semantic queries and to serialise their results.
 * 
 * @author Markus Krötzsch
 */
 
global $smwgIP;
require_once($smwgIP . '/includes/storage/SMW_Store.php');
require_once($smwgIP . '/includes/SMW_QueryPrinters.php');

/**
 * Static class for accessing functions to generate and execute semantic queries 
 * and to serialise their results.
 */
class SMWQueryProcessor {

	/**
	 * Array of enabled formats for formatting queries. Can be redefined in the settings to disallow certain
	 * formats. The formats 'table' and 'list' are defaults that cannot be disabled. The format 'broadtable'
	 * should not be disabled either in order not to break Special:ask.
	 */
	static $formats = array('table', 'list', 'ol', 'ul', 'broadtable', 'embedded', 'timeline', 'eventline', 'template', 'count', 'debug');

	/**
	 * Parse a query string given in SMW's query language to create
	 * an SMWQuery. Parameters are given as key-value-pairs in the
	 * given array. The parameter $inline defines whether the query
	 * is "inline" as opposed to being part of some special search page.
	 *
	 * If an error occurs during parsing, an error-message is returned 
	 * as a string. Otherwise an object of type SMWQuery is returned.
	 */
	static public function createQuery($querystring, $params, $inline = true) {
		// parse query:
		$qp = new SMWQueryParser();
		$desc = $qp->getQueryDescription($querystring);
		/// TODO check for errors

		///TODO do this only when wanted, use given label:
		$desc->addPrintRequest(new SMWPrintrequest(SMW_PRINT_THIS, 'Mainlabel')); 

		$query = new SMWQuery($desc);

		print '### Query:' . $desc->getQueryString() . ' ###';

		// set query parameters:
		global $smwgIQMaxLimit, $smwgIQMaxInlineLimit;
		if ($inline)
			$maxlimit = $smwgIQMaxInlineLimit;
		else $maxlimit = $smwgIQMaxLimit;

		if ( !$inline && (array_key_exists('offset',$params)) && (is_int($params['offset'] + 0)) ) {
			$query->offset = min($maxlimit - 1, max(0,$params['offset'] + 0)); //select integer between 0 and maximal limit -1
		}
		// set limit small enough to stay in range with chosen offset
		// it makes sense to have limit=0 in order to only show the link to the search special
		if ( (array_key_exists('limit',$params)) && (is_int($params['limit'] + 0)) ) {
			$query->limit = min($maxlimit - $query->offset, max(0,$params['limit'] + 0));
		}
		if (array_key_exists('sort', $params)) {
			$query->sort = true;
			$query->sortkey = smwfNormalTitleDBKey($params['sort']);
		}
		if (array_key_exists('order', $params)) {
			if (('descending'==strtolower($params['order']))||('reverse'==strtolower($params['order']))||('desc'==strtolower($params['order']))) {
				$query->ascending = false;
			}
		}
		return $query;
	}

	/**
	 * Process a query string in SMW's query language and return a formatted
	 * result set as HTML text. A parameter array of key-value-pairs constrains
	 * the query and determines the serialisation mode for results. The third
	 * parameter $inline defines whether the query is "inline" as opposed to
	 * being part of some special search page.
	 */
	static public function getResultHTML($querystring, $params, $inline = true) {
		$query = SMWQueryProcessor::createQuery($querystring, $params, $inline);
		if ($query instanceof SMWQuery) { // query parsing successful
			$format = SMWQueryProcessor::getResultFormat($params);
			/// TODO: incorporate the case $format=='debug' and $format=='count' into the following
			$res = smwfGetStore()->getQueryResult($query);
			$printer = SMWQueryProcessor::getResultPrinter($format, $inline, $res);
			return $printer->getResultHTML($res, $params);
		} else { // error string: return escaped version
			return htmlspecialchars($query);
		}
	}

	/**
	 * Determine format label from parameters.
	 */
	static protected function getResultFormat($params) {
		if (array_key_exists('format', $params)) {
			$format = strtolower($params['format']);
			if ( !in_array($format,SMWQueryProcessor::$formats) ) {
				$format = 'auto'; // If it is an unknown format, defaults to list/table again
			}
		}
		return $format;
	}

	/**
	 * Find suitable SMWResultPrinter for the given format.
	 */
	static protected function getResultPrinter($format,$inline,$res) {
		if ( 'auto' == $format ) {
			if ( ($res->getColumnCount()>1) && ($res->getColumnCount()>0) )
				$format = 'table';
			else $format = 'list';
		}
		switch ($format) {
			case 'table': case 'broadtable':
				return new SMWTableResultPrinter($format,$inline);
			case 'ul': case 'ol': case 'list':
				return new SMWListResultPrinter($format,$inline);
			case 'timeline': case 'eventline':
				return new SMWListResultPrinter($format,$inline); //TODO
			case 'embedded':
				return new SMWListResultPrinter($format,$inline); //TODO
			case 'template':
				return new SMWListResultPrinter($format,$inline); //TODO
			default: return new SMWListResultPrinter($format,$inline);
		}
	}

}


/**
 * Objects of this class are in charge of parsing a query string in order
 * to create an SMWDescription. The class and methods are not static in order 
 * to more cleanly store the intermediate state and progress of the parser.
 */
class SMWQueryParser {

	protected $m_sepstack; // list of open blocks ("parentheses") that need closing at current step
	protected $m_curstring; // remaining string to be parsed (parsing eats query string from the front)
	protected $m_error; // false if all went right, string otherwise
	
	protected $m_categoryprefix; // cache label of category namespace . ':'
	
	public function SMWQueryParser() {
		global $wgContLang;
		$this->m_categoryprefix = $wgContLang->getNsText(NS_CATEGORY) . ':';
	}

	/**
	 * Compute an SMWDescription from a query string. Return this description or
	 * false if there were errors.
	 */
	public function getQueryDescription($querystring) {
		$this->m_curstring = $querystring;
		$this->m_sepstack = array();
		return $this->getSubqueryDescription();
	}

	/**
	 * Compute an SMWDescription for current part of a query, which should
	 * be a standalone query (the main query or a subquery enclosed within
	 * "<q>...</q>". Recursively calls similar methods and returns false upon error.
	 */
	protected function getSubqueryDescription() {
		$result = NULL;
		while (($chunk = $this->readChunk()) != '') {
			switch ($chunk) {
				case '[[': // start new link block
					$this->pushDelimiter(']]'); // expected termination symbol
					$result = $this->addDescription($result,$this->getLinkDescription());
				break;
				case '</q>': // exit current subquery
					if ($this->popDelimiter('</q>')) {
						//TODO: return computed  description
					} else {
						$this->m_error = 'There appear to be too many occurences of \'' . $chunk . '\' in the query.';
						return false;
					}
				break;
				default: // error: unexpected $chunk
					$this->m_error = 'The part \'' . $chunk . '\' in the query was not understood. Results might not be as expected.'; // TODO: internationalise
					return false;
			}
		}
		return $result;

		/// TODO implement
		// DEBUG:
// 			$o_desc = new SMWNominalDescription(Title::newFromText("Africa"));
// 			$value = SMWDataValueFactory::newAttributeValue('Population','5853000');
// 			$t_desc = new SMWThingDescription();
// 			$v_desc = new SMWValueDescription($value, SMW_CMP_GEQ);
// 			$a_desc = new SMWSomeAttribute(Title::newFromText('Attribute:Population'), $v_desc);
// 			$r_desc = new SMWSomeRelation(Title::newFromText("Relation:located in"), $o_desc);
// 			$r_desc2 = new SMWSomeRelation(Title::newFromText("Relation:borders"), $r_desc);
// 			$r_desc3 = new SMWSomeRelation(Title::newFromText("Relation:located in"), $t_desc);
// 			$c_desc = new SMWClassDescription(Title::newFromText("Category:Country"));
// 			$desc = new SMWConjunction(array($c_desc, $r_desc));
// 			$desc2 = new SMWConjunction(array($c_desc, $a_desc, $r_desc2, $r_desc));
// 			$pr1 = new SMWPrintrequest(SMW_PRINT_THIS, 'Country');
// 			$desc->addPrintRequest($pr1);
// 			$desc2->addPrintRequest($pr1);
// 			$pr2 = new SMWPrintrequest(SMW_PRINT_RELS, 'Borders', Title::newFromText('Relation:Borders'));
// 			$desc->addPrintRequest($pr2);
// 			$desc2->addPrintRequest($pr2);
// 			$pr3 = new SMWPrintrequest(SMW_PRINT_ATTS, 'Population', Title::newFromText('Attribute:Population'));
// 			$desc->addPrintRequest($pr3);
// 			$desc2->addPrintRequest($pr3);
// 			$pr4 = new SMWPrintrequest(SMW_PRINT_CATS, 'Categories');
// 			$desc->addPrintRequest($pr4);
// 			
// 			return $desc2;
	}
	
	/**
	 * Compute an SMWDescription for current part of a query, which should
	 * be the content of "[[ ... ]]". Recursively calls similar methods and 
	 * returns false upon error.
	 */
	protected function getLinkDescription() {
		$result = NULL;
		// This method is called when we encountered an opening '[['. The following
		// block could be a Category-statement, fixed object, relation or attribute
		// statements, or according print statements.
		$chunk = $this->readChunk();

		if ($chunk == $this->m_categoryprefix) { // category statement
			// note: no subqueries allowed here, inline disjunction allowed, wildcards allowed
			$continue = true;
			while ($continue) {
				$chunk = $this->readChunk();
				switch ($chunk) {
					case '+': //wildcard
					break;
					case '*': //print statement
					break;
					default: //assume category title
						$cat = Title::newFromText($chunk, NS_CATEGORY);
						if ($cat !== NULL) {
							$result = $this->addDescription($result, new SMWClassDescription($cat), false);
						}
				}
				$chunk = $this->readChunk();
				if ($chunk == '||') {
					$continue = true;
				} else {
					$continue = false;
				}
			}
		} else { // fixed subject, property query, or subquery
			
		}

		// terminate link (assuming that next chunk was read already)
		if ($chunk == '|') { // label, TODO
			$chunk = $this->readChunk();
			$label = '';
			///TODO: rather have a mode for readChunk that stops only on ']]'
			/// (otherwise we kill spaces in the label)
			while ( ($chunk != ']]') && ($chunk !== '') ) {
				$label .= $chunk;
				$chunk = $this->readChunk();
			}
		}
		if ($chunk == ']]') { // expected termination
			$this->popDelimiter(']]');
			return $result;
		} else {
			// What happended? We found some chunk that could not be processed as
			// link content (as in [[Category:Test<q>]]) and there was no label to 
			// eat it. Or the closing ]] are just missing entirely.
			if ($chunk != '') { //TODO: internationalise errors
				$this->m_error = 'The symbol \'' . $chunk . '\' was used in a place where it is not useful.'; 
			} else {
				$this->m_error = 'Some use of \'[[\' in your query was not closed by a matching \']]\'.';
			}
			return false;
		}

		return $result;
	}
	
	/**
	 * Get the next unstructured string chunk from the query string.
	 * Chunks are delimited by any of the special strings used in inline queries
	 * (such as [[, ]], <q>, ...). If the string starts with such a delimiter,
	 * this delimiter is returned. Otherwise the first string in front of such a 
	 * delimiter is returned.
	 * Trailing and initial spaces are always ignored and chunks
	 * consisting only of spaces are not returned.
	 * If there is no more qurey string left to process, the empty string is
	 * returned (and in no other case).
	 */
	protected function readChunk() {
		$chunks = preg_split('/[\s]*(\[\[|\]\]|::|:=|<q>|<\/q>|' . $this->m_categoryprefix . '|\|\||\|)[\s]*/', $this->m_curstring, 2, PREG_SPLIT_DELIM_CAPTURE);
		if (count($chunks) == 1) { // no mathces anymore, strip spaces and finish
			$this->m_curstring = '';
			return trim($chunks[0]);
		} elseif (count($chunks) == 3) { // this chould generally happen if count is not 1
			if ($chunks[0] == '') { // string started with delimiter
				$this->m_curstring = $chunks[2];
				return $chunks[1]; // spaces stripped already
			} else {
				$this->m_curstring = $chunks[1] . $chunks[2];
				return $chunks[0]; // spaces stripped already
			}
		} else { return false; }  //should never happen
	}

	/**
	 * Enter a new subblock in the query, which must at some time be terminated by the
	 * given $endstring delimiter calling popDelimiter();
	 */
	protected function pushDelimiter($endstring) {
		array_push($this->m_sepstack, $endstring);
	}

	/**
	 * Exit a subblock in the query ending with the given delimiter.
	 * If the delimiter does not match the top-most open block, false
	 * will be returned. Otherwise return true.
	 */
	protected function popDelimiter($endstring) {
		$topdelim = array_pop($this->m_sepstack);
		return ($topdelim == $endstring);
	}

	/**
	 * Extend a given description by a new one, either by adding the new description
	 * (if the old one is a container description) or by creating a new container.
	 * The parameter $conjunction determines whether the combination of both descriptions
	 * should be a disjunction or conjunction.
	 *
	 * In the special case that the current description is NULL, the new one will just
	 * replace the current one.
	 *
	 * The return value is the expected combined description. The object $curdesc will
	 * also be changed (if it was non-NULL).
	 */
	protected function addDescription($curdesc, $newdesc, $conjunction = true) {
		if ($curdesc === NULL) {
			return $newdesc;
		} else { // we already found descriptions
			if ( (($conjunction)  && ($curdesc instanceof SMWConjunction)) ||
			     ((!$conjunction) && ($curdesc instanceof SMWDisjunction)) ) { // use existing container
				$curdesc->addDescription($newdesc);
			} elseif ($conjunction) { // make new conjunction
				return new SMWConjunction(array($curdesc,$newdesc));
			} else { // make new disjunction
				return new SMWDisjunction(array($curdesc,$newdesc));
			}
		}
	}
}
 
?>
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
 * This hook registers a parser-hook to the current parser.
 * Note that parser hooks are something different than MW hooks
 * in general, which explains the two-level registration.
 */
function smwfRegisterInlineQueries( $semantic, $mediawiki, $rules ) {
	global $wgParser;
	$wgParser->setHook( 'ask', 'smwfProcessInlineQuery' );
	return true; // always return true, in order not to stop MW's hook processing!
}

/**
 * The <ask> parser hook processing part.
 */
function smwfProcessInlineQuery($text, $param) {
	global $smwgIQEnabled;
	if ($smwgIQEnabled) {
		return SMWQueryProcessor::getResultHTML($text,$param);
	} else {
		return wfMsgForContent('smw_iq_disabled');
	}
}

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
	 *
	 * The format string is used to specify the output format if already
	 * known. Otherwise it will be determined from the parameters when 
	 * needed. This parameter is just for optimisation in a common case.
	 */
	static public function createQuery($querystring, $params, $inline = true, $format = '') {
		// This should be the proper way of substituting templates in a safe and comprehensive way:
		global $wgTitle;
		$parser = new Parser();
		$parserOptions = new ParserOptions();
		$parser->startExternalParse( $wgTitle, $parserOptions, OT_HTML );
		$querystring = $parser->transformMsg( $querystring, $parserOptions );

		// parse query:
		$qp = new SMWQueryParser();
		$desc = $qp->getQueryDescription($querystring);
		if ($desc === NULL) { //abort with failure
			return $qp->getError();
		}

		if (array_key_exists('mainlabel', $params)) {
			$mainlabel = $params['mainlabel'] . $qp->getLabel();
		} else {
			$mainlabel = $qp->getLabel();
		}
		///TODO do this only when wanted:
		$desc->prependPrintRequest(new SMWPrintRequest(SMW_PRINT_THIS, $mainlabel)); 

		$query = new SMWQuery($desc);
		if ($format == '') {
			$format = SMWQueryProcessor::getResultFormat($params);
		}
		switch ($format) {
			case 'count': 
				$query->querymode = SMWQuery::MODE_COUNT;
			break;
			case 'debug': 
				$query->querymode = SMWQuery::MODE_DEBUG;
			break;
			default: break;
		}

		//print '### Query:' . htmlspecialchars($desc->getQueryString()) . ' ###'; // DEBUG

		// set query parameters:
		global $smwgIQMaxLimit, $smwgIQMaxInlineLimit;
		if ($inline)
			$maxlimit = $smwgIQMaxInlineLimit;
		else $maxlimit = $smwgIQMaxLimit;

		if ( !$inline && (array_key_exists('offset',$params)) && (is_int($params['offset'] + 0)) ) {
			$query->offset = min($maxlimit - 1, max(0,$params['offset'] + 0)); //select integer between 0 and maximal limit -1
		}
		if ($query->querymode != SMWQuery::MODE_COUNT) {
			// set limit small enough to stay in range with chosen offset
			// it makes sense to have limit=0 in order to only show the link to the search special
			if ( (array_key_exists('limit',$params)) && (is_int($params['limit'] + 0)) ) {
				$query->limit = min($maxlimit - $query->offset, max(0,$params['limit'] + 0));
			} else {
				$query->limit = $maxlimit;
			}
		} else { // largest possible limit for "count"
			$query->limit = $smwgIQMaxLimit;
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
		$format = SMWQueryProcessor::getResultFormat($params);
		$query = SMWQueryProcessor::createQuery($querystring, $params, $inline, $format);
		if ($query instanceof SMWQuery) { // query parsing successful
			$res = smwfGetStore()->getQueryResult($query);
			if ($query->querymode == SMWQuery::MODE_INSTANCES) {
				$printer = SMWQueryProcessor::getResultPrinter($format, $inline, $res);
				return $printer->getResultHTML($res, $params);
			} else { // result for counting or debugging is just a string
				return $res;
			}
		} else { // error string: return escaped version
			return htmlspecialchars($query); ///TODO: improve error reporting format ...
		}
	}

	/**
	 * Determine format label from parameters.
	 */
	static protected function getResultFormat($params) {
		$format = 'auto';
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
				return new SMWTimelineResultPrinter($format,$inline);
			case 'embedded':
				return new SMWEmbeddedResultPrinter($format,$inline);
			case 'template':
				return new SMWTemplateResultPrinter($format,$inline);
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
	protected $m_label; //label of the main query result
	
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
		$this->m_error = false;
		$this->m_label = '';
		$this->m_curstring = $querystring;
		$this->m_sepstack = array();
		return $this->getSubqueryDescription();
	}

	/**
	 * Return error message or false if no error occurred.
	 */
	public function getError() {
		return $this->m_error;
	}

	/**
	 * Return label for the results of this query (which
	 * might be empty if no such information was passed).
	 */
	public function getLabel() {
		return $this->m_label;
	}


	/**
	 * Compute an SMWDescription for current part of a query, which should
	 * be a standalone query (the main query or a subquery enclosed within
	 * "<q>...</q>". Recursively calls similar methods and returns NULL upon error.
	 */
	protected function getSubqueryDescription() {
		$result = NULL;
		$printrequests = array();
		$continue = ($chunk = $this->readChunk()) != '';
		while ($continue) {
			switch ($chunk) {
				case '[[': // start new link block
					$ld = $this->getLinkDescription($printrequests);
					if ($ld === NULL) {
						return NULL;
					} elseif ($ld instanceof SMWPrintRequest) {
						$printrequests[] = $ld;
					} else {
						$result = $this->addDescription($result,$ld);
					}
				break;
				case '</q>': // exit current subquery
					if ($this->popDelimiter('</q>')) {
						$continue = false; // leave the loop
					} else {
						$this->m_error = 'There appear to be too many occurences of \'' . $chunk . '\' in the query.';
						return NULL;
					}
				break;
				default: // error: unexpected $chunk
					$this->m_error = 'The part \'' . $chunk . '\' in the query was not understood. Results might not be as expected.'; // TODO: internationalise
					return NULL;
			}
			$continue = ($continue) && ( ($chunk = $this->readChunk()) != '' );
		}

		if ($result !== NULL) {
			foreach ($printrequests as $pr) {
				$result->addPrintRequest($pr);
			}
		}
		return $result;
	}

	/**
	 * Compute an SMWDescription for current part of a query, which should
	 * be the content of "[[ ... ]]". Alternatively, if the current syntax
	 * specifies a print request, return the print request object.
	 * Returns NULL upon error.
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
					case '*': //print statement
						$chunk = $this->readChunk('\]\]|\|');
						if ($chunk == '|') {
							$label = $this->readChunk('\]\]');
							if ($label != ']]') {
								$chunk = $this->readChunk('\]\]');
							} else {
								$label = '';
								$chunk = ']]';
							}
						} else {
							global $wgContLang;
							$label = $wgContLang->getNSText(NS_CATEGORY);
						}
						if ($chunk == ']]') {
							return new SMWPrintRequest(SMW_PRINT_CATS, $label);
						} else {
							$this->m_error = 'Misshaped print statement.'; //TODO: internationalise
							return NULL;
						}
					break;
					case '+': //wildcard, ignore for categories (semantically meaningless)
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
		} else { // fixed subject, namespace restriction, property query, or subquery
			$sep = $this->readChunk();
			if ($sep == '::') { // relation statement
				$rel = Title::newFromText($chunk, SMW_NS_RELATION);
				$continue = true;
				$innerdesc = NULL;
				while ($continue) {
					$chunk = $this->readChunk();
					switch ($chunk) {
						case '*': // print statement, abort processing
							$chunk = $this->readChunk('\]\]|\|');
							if ($chunk == '|') {
								$label = $this->readChunk('\]\]');
								if ($label != ']]') {
									$chunk = $this->readChunk('\]\]');
								} else {
									$label = '';
									$chunk = ']]';
								}
							} else {
								$label = $rel->getText();
							}
							if ($chunk == ']]') {
								return new SMWPrintRequest(SMW_PRINT_RELS, $label, $rel);
							} else {
								$this->m_error = 'Misshaped print statement.'; //TODO: internationalise
								return NULL;
							}
						break;
						case '+': // wildcard
							$innerdesc = $this->addDescription($innerdesc, new SMWThingDescription(), false);
						break;
						case '<q>': // subquery
							$this->pushDelimiter('</q>');
							$innerdesc = $this->addDescription($innerdesc, $this->getSubqueryDescription(), false);
						break;
						default: //normal object value
							$obj = Title::newFromText($chunk);
							if ($obj !== NULL) {
								$innerdesc = $this->addDescription($innerdesc, new SMWNominalDescription($obj), false);
							}
					}
					$chunk = $this->readChunk();
					$continue = ($chunk == '||');
				}
				if ($innerdesc !== NULL) {
					$result = new SMWSomeRelation($rel,$innerdesc);
				}
			} elseif ($sep == ':=') { // attribute statement
				$att = Title::newFromText($chunk, SMW_NS_ATTRIBUTE);
				///TODO: currently no support for disjunctions in data values (needs extension of query processor)

				// get values, including values with internal [[...]]
				$open = 1;
				$value = '';
				while ( ($open > 0) && ($chunk != '') ) {
					$chunk = $this->readChunk('\[\[|\]\]|\|');
					switch ($chunk) {
						case '[[': // open new [[ ]]
							$open++;
						break;
						case ']]': // close [[ ]]
							$open--;
						break;
						case '|': // terminates only outermost [[ ]]
							if ($open == 1) {
								$open = 0;
							}
						break;
					}
					if ($open != 0) {
						$value .= $chunk;
					}
				}
				// note that at this point, we already read one more chunk behind the value
				$list = preg_split('/^\*/',$value,2);
				if (count($list) == 2) { //hit
					$value = '*';
					$printmodifier = $list[1];
				} else {
					$printmodifier = '';
				}
				switch ($value) {
					case '*': // print statement
						if ($chunk == '|') {
							$label = $this->readChunk('\]\]');
							if ($label != ']]') {
								$chunk = $this->readChunk('\]\]');
							} else {
								$label = '';
								$chunk = ']]';
							}
						} else {
							$label = $att->getText();
						}
						if ($chunk == ']]') {
							return new SMWPrintRequest(SMW_PRINT_ATTS, $label, $att, $printmodifier);
						} else {
							$this->m_error = 'Misshaped print statement.'; //TODO: internationalise
							return NULL;
						}
					break;
					case '+': // wildcard
						$vd = new SMWValueDescription(NULL, SMW_CMP_ANY);
					break;
					default: // fixed value, possibly with comparator addons
						// for now, treat comparators only if placed before whole value:
						$list = preg_split('/^(<|>)/',$value, 2, PREG_SPLIT_DELIM_CAPTURE);
						$comparator = SMW_CMP_EQ;
						if (count($list) == 3) { // initial comparator found ($list[1] should be empty)
							switch ($list[1]) {
								case '<':
									$comparator = SMW_CMP_LEQ;
									$value = $list[2];
								break;
								case '>':
									$comparator = SMW_CMP_GEQ;
									$value = $list[2];
								break;
								//default: not possible
							}
						}
						// TODO: needs extension for n-ary values
						$dv = SMWDataValueFactory::newAttributeValue($att->getText(), $value);
						if (!$dv->isValid()) {
							$this->m_error = $dv->getError();
							$vd = new SMWValueDescription(NULL, SMW_CMP_ANY);
						} else {
							$vd = new SMWValueDescription($dv, $comparator);
						}
				}
				$result = new SMWSomeAttribute($att, $vd);
			} else { // Fixed article/namespace restriction. $sep should be ]] or ||
				$continue = true;
				//$innerdesc = NULL;
				while ($continue) {
					switch ($chunk) {
						case '<q>': // subquery
							$this->pushDelimiter('</q>');
							$result = $this->addDescription($result, $this->getSubqueryDescription(), false);
						break;
						default:
							$list = preg_split('/:/', $chunk, 3); // ":Category:Foo" "User:bar"  ":baz" ":+"
							if ( ($list[0] == '') && (count($list)==3) ) {
								$list = array_slice($list, 1);
							}
							if ( (count($list) == 2) && ($list[1] == '+') ) { // try namespace restriction
								global $wgContLang;
								$idx = $wgContLang->getNsIndex($list[0]);
								if ($idx !== false) {
									$result = $this->addDescription($result, new SMWNamespaceDescription($idx), false);
								}
							} else {
								$title = Title::newFromText($chunk);
								if ($title !== NULL) {
									$result = $this->addDescription($result, new SMWNominalDescription($title), false);
								}
							}
					}

					if ($sep !== false) { // resuse prefetched sep
						$chunk = $sep;
						$sep = false;
					} else {
						$chunk = $this->readChunk();
					}
					if ($chunk == '||') {
						$chunk = $this->readChunk();
						$continue = true;
					} else {
						$continue = false;
					}
				}
			}
		}

		if ($result === NULL) { // no useful information or concrete error found
			$this->m_error = 'Syntax error in query.'; //TODO internationalise
			return NULL;
		}

		// terminate link (assuming that next chunk was read already)
		if ($chunk == '|') { // label, TODO
			$label = $this->readChunk('\]\]');
			if ($label != ']]') {
				$chunk = $this->readChunk('\]\]');
			} else {
				$label = '';
				$chunk = ']]';
			}
		}
		if ($chunk == ']]') { // expected termination
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
			return NULL;
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
	 * 
	 * The stoppattern can be used to customise the matching, especially in order to 
	 * overread certain special symbols.
	 */
	protected function readChunk($stoppattern = '') {
		if ($stoppattern == '') {
			$stoppattern = '\[\[|\]\]|::|:=|<q>|<\/q>|^' . $this->m_categoryprefix . '|\|\||\|';
		}
		$chunks = preg_split('/[\s]*(' . $stoppattern . ')[\s]*/', $this->m_curstring, 2, PREG_SPLIT_DELIM_CAPTURE);
		if (count($chunks) == 1) { // no matches anymore, strip spaces and finish
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
				return $curdesc;
			} elseif ($conjunction) { // make new conjunction
				return new SMWConjunction(array($curdesc,$newdesc));
			} else { // make new disjunction
				return new SMWDisjunction(array($curdesc,$newdesc));
			}
		}
	}
}
 
?>
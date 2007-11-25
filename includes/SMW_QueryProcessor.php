<?php
/**
 * This file contains a static class for accessing functions to generate and execute
 * semantic queries and to serialise their results.
 *
 * @author Markus Krötzsch
 */

global $smwgIP;
require_once($smwgIP . '/includes/storage/SMW_Store.php');

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
	 * An object of type SMWQuery is returned.
	 *
	 * The format string is used to specify the output format if already
	 * known. Otherwise it will be determined from the parameters when 
	 * needed. This parameter is just for optimisation in a common case.
	 */
	static public function createQuery($querystring, $params, $inline = true, $format = '', $extraprintouts = array()) {
		global $smwgQDefaultNamespaces;

		// parse query:
		$qp = new SMWQueryParser();
		$qp->setDefaultNamespaces($smwgQDefaultNamespaces);
		$desc = $qp->getQueryDescription($querystring);

		if (array_key_exists('mainlabel', $params)) {
			$mainlabel = $params['mainlabel'] . $qp->getLabel();
		} else {
			$mainlabel = $qp->getLabel();
		}
		if ( !$desc->isSingleton() || (count($desc->getPrintRequests()) + count($extraprintouts) == 0) ) {
			$desc->prependPrintRequest(new SMWPrintRequest(SMW_PRINT_THIS, $mainlabel));
		}

		$query = new SMWQuery($desc, $inline);
		$query->setQueryString($querystring);
		$query->setExtraPrintouts($extraprintouts);
		$query->addErrors($qp->getErrors()); // keep parsing errors for later output

		// set query parameters:
		if ($format == '') {
			$format = SMWQueryProcessor::getResultFormat($params);
		}
		if ($format == 'count') {
			$query->querymode = SMWQuery::MODE_COUNT;
		} elseif ($format == 'debug') {
			$query->querymode = SMWQuery::MODE_DEBUG;
		}
		if ( (array_key_exists('offset',$params)) && (is_int($params['offset'] + 0)) ) {
			$query->setOffset(max(0,trim($params['offset']) + 0));
		}
		if ($query->querymode != SMWQuery::MODE_COUNT) {
			if ( (array_key_exists('limit',$params)) && (is_int($params['limit'] + 0)) ) {
				$query->setLimit(max(0,trim($params['limit']) + 0));
			} else {
				global $smwgQDefaultLimit;
				$query->setLimit($smwgQDefaultLimit);
			}
		} else { // largest possible limit for "count", even inline
			global $smwgQMaxLimit;
			$query->setOffset(0);
			$query->setLimit($smwgQMaxLimit, false);
		}
		if (array_key_exists('sort', $params)) {
			$query->sort = true;
			$query->sortkey = smwfNormalTitleDBKey($params['sort']);
		}
		if (array_key_exists('order', $params)) {
			$order = strtolower(trim($params['order']));
			if (('descending'==$order)||('reverse'==$order)||('desc'==$order)) {
				$query->ascending = false;
			}
		}
		return $query;
	}

	/**
	 * Prerocess a query as given by an array of parameters as is  typically 
	 * produced by the #ask parser function. The parsing results in a querystring,
	 * an array of additional parameters, and an array of additional SMWPrintRequest
	 * objects, which are filled into call-by-ref parameters.
	 */
	static public function processFunctionParams($rawparams, &$querystring, &$params, &$printouts) {
		global $wgContLang;
		$querystring = '';
		$printouts = array();
		$params = array();
		foreach ($rawparams as $param) {
			if ($param == '') {
			} elseif ($param{0} == '?') { // print statement
				$param = substr($param,1);
				$parts = explode('=',$param,2);
				$propparts = explode('#',$parts[0],2);
				if ($wgContLang->getNsText(NS_CATEGORY) != ucfirst(trim($propparts[0]))) {
					$property = Title::newFromText(trim($propparts[0]), SMW_NS_PROPERTY); // trim needed for \n
					if ($property === NULL) { // too bad, this is no legal property name, ignore
						continue;
					}
					$printmode = SMW_PRINT_PROP;
					if (count($parts) == 1) { // no label found, use property name
						$parts[] = $property->getText();
					}
				} else {
					$property = NULL;
					$printmode = SMW_PRINT_CATS;
					if (count($parts) == 1) { // no label found, use category label
						$parts[] = $wgContLang->getNSText(NS_CATEGORY);
					}
				}
				if (count($propparts) == 1) { // no outputformat found, use property name
					$propparts[] = '';
				}
				$printouts[] = new SMWPrintRequest($printmode, trim($parts[1]), $property, $propparts[1]);
			} else { // parameter or query
				$parts = explode('=',$param,2);
				if (count($parts) >= 2) {
					$params[strtolower(trim($parts[0]))] = $parts[1]; // don't trim here, some params care for " "
				} else {
					$querystring .= $param;
				}
			}
		}
		$querystring = str_replace(array('&lt;','&gt;'), array('<','>'), $querystring);
	}

	/**
	 * Process and answer a query as given by an array of parameters as is 
	 * typically produced by the #ask parser function. The result is formatted
	 * according to the specified $outputformat. The third parameter $inline
	 * defines whether the query is "inline" as opposed to being part of some
	 * special search page.
	 *
	 * The main task of this function is to preprocess the raw parameters to
	 * obtain actual parameters, printout requests, and the query string for
	 * further processing.
	 */
	static public function getResultFromFunctionParams($rawparams, $outputmode, $inline = true) {
		SMWQueryProcessor::processFunctionParams($rawparams,$querystring,$params,$printouts);
		return SMWQueryProcessor::getResultFromQueryString($querystring,$params,$printouts, SMW_OUTPUT_WIKI, $inline);
	}

	/**
	 * Process and answer a query as given by a string and an array of parameters 
	 * as is typically produced by the <ask> parser hook. The result is formatted
	 * according to the specified $outputformat. The fourth parameter $inline
	 * defines whether the query is "inline" as opposed to being part of some
	 * special search page.
	 */
	static public function getResultFromHookParams($querystring, $params, $outputmode, $inline = true) {
		global $wgTitle;
		// Take care at least of some templates -- for better template support use #ask
		$parser = new Parser();
		$parserOptions = new ParserOptions();
		$parser->startExternalParse( $wgTitle, $parserOptions, OT_HTML );
		$querystring = $parser->transformMsg( $querystring, $parserOptions );
		return SMWQueryProcessor::getResultFromQueryString($querystring, $params, array(), $outputmode, $inline);
	}

	/**
	 * Process a query string in SMW's query language and return a formatted
	 * result set as HTML text. A parameter array of key-value-pairs constrains
	 * the query and determines the serialisation mode for results. The third
	 * parameter $inline defines whether the query is "inline" as opposed to
	 * being part of some special search page.
	 * @DEPRECATED use getResult
	 */
	static public function getResultHTML($querystring, $params, $inline = true) {
		return SMWQueryProcessor::getResultFromQueryString($querystring, $params, array(), SMW_OUTPUT_HTML, $inline);
	}

	/**
	 * Process a query string in SMW's query language and return a formatted
	 * result set as specified by $outputmode. A parameter array of key-value-pairs 
	 * constrains the query and determines the serialisation mode for results. 
	 * The fourth parameter $inline defines whether the query is "inline" as opposed 
	 * to being part of some special search page. Finally, $extraprintouts supplies
	 * additional printout requests for the query results.
	 */
	static public function getResultFromQueryString($querystring, $params, $extraprintouts, $outputmode, $inline = true) {
		wfProfileIn('SMWQueryProcessor::getResultFromQueryString (SMW)');
		$format = SMWQueryProcessor::getResultFormat($params);
		$query  = SMWQueryProcessor::createQuery($querystring, $params, $inline, $format, $extraprintouts);
		$result = SMWQueryProcessor::getResultFromQuery($query, $params, $extraprintouts, $outputmode, $inline, $format);
		wfProfileOut('SMWQueryProcessor::getResultFromQueryString (SMW)');
		return $result;
	}

	static public function getResultFromQuery($query, $params, $extraprintouts, $outputmode, $inline = true, $format = '') {
		wfProfileIn('SMWQueryProcessor::getResultFromQuery (SMW)');
		if ($format == '') {
			$format = SMWQueryProcessor::getResultFormat($params);
		}
		$res = smwfGetStore()->getQueryResult($query);
		if ($query->querymode == SMWQuery::MODE_INSTANCES) {
			wfProfileIn('SMWQueryProcessor::getResultFromQuery-printout (SMW)');
			$printer = SMWQueryProcessor::getResultPrinter($format, $inline, $res);
			$result = $printer->getResult($res, $params, $outputmode);
			wfProfileOut('SMWQueryProcessor::getResultFromQuery-printout (SMW)');
			wfProfileOut('SMWQueryProcessor::getResultFromQuery (SMW)');
			return $result;
		} else { // result for counting or debugging is just a string
			wfProfileOut('SMWQueryProcessor::getResultFromQuery (SMW)');
			return $res;
		}
	}

	/**
	 * Determine format label from parameters.
	 */
	static protected function getResultFormat($params) {
		$format = 'auto';
		if (array_key_exists('format', $params)) {
			$format = strtolower(trim($params['format']));
			if ( !in_array($format,SMWQueryProcessor::$formats) ) {
				$format = 'auto'; // If it is an unknown format, defaults to list/table again
			}
		}
		return $format;
	}

	/**
	 * Find suitable SMWResultPrinter for the given format.
	 */
	static public function getResultPrinter($format,$inline,$res) {
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
	protected $m_errors; // empty array if all went right, array of strings otherwise
	protected $m_label; //label of the main query result
	protected $m_defaultns; //description of the default namespace restriction, or NULL if not used
	
	protected $m_categoryprefix; // cache label of category namespace . ':'
	
	public function SMWQueryParser() {
		global $wgContLang;
		$this->m_categoryprefix = $wgContLang->getNsText(NS_CATEGORY) . ':';
		$this->m_defaultns = NULL;
	}

	/**
	 * Provide an array of namespace constants that are used as default restrictions.
	 * If NULL is given, no such default restrictions will be added (faster).
	 */
	public function setDefaultNamespaces($nsarray) {
		$this->m_defaultns = NULL;
		if ($nsarray !== NULL) {
			foreach ($nsarray as $ns) {
				$this->m_defaultns = $this->addDescription($this->m_defaultns, new SMWNamespaceDescription($ns), false);
			}
		}
	}

	/**
	 * Compute an SMWDescription from a query string. Returns whatever descriptions could be
	 * wrestled from the given string (the most general result being SMWThingDescription if
	 * no meaningful condition was extracted).
	 */
	public function getQueryDescription($querystring) {
		wfProfileIn('SMWQueryParser::getQueryDescription (SMW)');
		$this->m_errors = array();
		$this->m_label = '';
		$this->m_curstring = $querystring;
		$this->m_sepstack = array();
		$setNS = false;
		$result = $this->getSubqueryDescription($setNS, $this->m_label);
		if (!$setNS) { // add default namespaces if applicable
			$result = $this->addDescription($this->m_defaultns, $result);
		}
		if ($result === NULL) { // parsing went wrong, no default namespaces
			$result = new SMWThingDescription();
		}
		wfProfileOut('SMWQueryParser::getQueryDescription (SMW)');
		return $result;
	}

	/**
	 * Return array of error messages (possibly empty).
	 */
	public function getErrors() {
		return $this->m_errors;
	}

	/**
	 * Return error message or empty string if no error occurred.
	 */
	public function getErrorString() {
		return smwfEncodeMessages($this->m_errors);
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
	 * 
	 * The call-by-ref parameter $setNS is a boolean. Its input specifies whether
	 * the query should set the current default namespace if no namespace restrictions
	 * were given. If false, the calling super-query is happy to set the required 
	 * NS-restrictions by itself if needed. Otherwise the subquery has to impose the defaults.
	 * This is so, since outermost queries and subqueries of disjunctions will have to set
	 * their own default restrictions.
	 * 
	 * The return value of $setNS specifies whether or not the subquery has a namespace
	 * specification in place. This might happen automatically if the query string imposes
	 * such restrictions. The return value is important for those callers that otherwise
	 * set up their own restrictions.
	 * 
	 * Note that $setNS is no means to switch on or off default namespaces in general,
	 * but just controls query generation. For general effect, the default namespaces 
	 * should be set to NULL.
	 *
	 * The call-by-ref parameter $label is used to append any label strings found.
	 */
	protected function getSubqueryDescription(&$setNS, &$label) {
		global $smwgQPrintoutLimit;
		$conjunction = NULL;      // used for the current inner conjunction
		$disjuncts = array();     // (disjunctive) array of subquery conjunctions
		$printrequests = array(); // the printrequests found for this query level
		$hasNamespaces = false;   // does the current $conjnuction have its own namespace restrictions?
		$mustSetNS = $setNS;      // must ns restrictions be set? (may become true even if $setNS is false)

		$continue = ($chunk = $this->readChunk()) != ''; // skip empty subquery completely, thorwing an error
		while ($continue) {
			$setsubNS = false;
			switch ($chunk) {
				case '[[': // start new link block
					$ld = $this->getLinkDescription($setsubNS, $label);
					if ($ld instanceof SMWPrintRequest) {
						$printrequests[] = $ld;
					} elseif ($ld instanceof SMWDescription) {
						$conjunction = $this->addDescription($conjunction,$ld);
					}
				break;
				case '<q>': // enter new subquery, currently irrelevant but possible
					$this->pushDelimiter('</q>');
					$conjunction = $this->addDescription($conjunction, $this->getSubqueryDescription($setsubNS, $label));
					/// TODO: print requests from subqueries currently are ignored, should be moved down
				break;
				case '||': case '': case '</q>': // finish disjunction and maybe subquery
					if ($this->m_defaultns !== NULL) { // possibly add namespace restrictions
						if ( $hasNamespaces && !$mustSetNS) {
							// add ns restrictions to all earlier conjunctions (all of which did not have them yet)
							$mustSetNS = true; // enforce NS restrictions from now on
							$newdisjuncts = array();
							foreach ($disjuncts as $conj) {
								$newdisjuncts[] = $this->addDescription($conj, $this->m_defaultns);
							}
							$disjuncts = $newdisjuncts;
						} elseif ( !$hasNamespaces && $mustSetNS) { 
							// add ns restriction to current result
							$conjunction = $this->addDescription($conjunction, $this->m_defaultns);
						}
					}
					$disjuncts[] = $conjunction;
					// start anew
					$conjunction = NULL;
					$hasNamespaces = false;
					// finish subquery?
					if ($chunk == '</q>') {
						if ($this->popDelimiter('</q>')) {
							$continue = false; // leave the loop
						} else {
							$this->m_errors[] = wfMsgForContent('smw_toomanyclosing', $chunk);
							return NULL;
						}
					} elseif ($chunk == '') {
						$continue = false;
					}
				break;
				case '+': // "... AND true" (ignore)
				break;
				default: // error: unexpected $chunk
					$this->m_errors[] = wfMsgForContent('smw_unexpectedpart', $chunk);
					//return NULL; // Try to go on, it can only get better ...
			}
			if ($setsubNS) { // namespace restrictions encountered in current conjunct
				$hasNamespaces = true;
			}
			if ($continue) { // read on only if $continue remained true
				$chunk = $this->readChunk();
			}
		}

		if (count($disjuncts) > 0) { // make disjunctive result
			$result = NULL;
			foreach ($disjuncts as $d) {
				if ($d === NULL) {
					$this->m_errors[] = wfMsgForContent('smw_emptysubquery');
					$setNS = false;
					return NULL;
				} else {
					$result = $this->addDescription($result, $d, false);
				}
			}
		} else {
			$this->m_errors[] = wfMsgForContent('smw_emptysubquery');
			$setNS = false;
			return NULL;
		}
		$setNS = $mustSetNS; // NOTE: also false if namespaces were given but no default NS descs are available

		$prcount = 0;
		foreach ($printrequests as $pr) { // add printrequests
			if ($prcount < $smwgQPrintoutLimit) {
				$result->addPrintRequest($pr);
				$prcount++;
			} else {
				$this->m_errors[] = wfMsgForContent('smw_overprintoutlimit');
				break;
			}
		}
		return $result;
	}

	/**
	 * Compute an SMWDescription for current part of a query, which should
	 * be the content of "[[ ... ]]". Alternatively, if the current syntax
	 * specifies a print request, return the print request object.
	 * Returns NULL upon error.
	 *
	 * Parameters $setNS and $label have the same use as in getSubqueryDescription().
	 */
	protected function getLinkDescription(&$setNS, &$label) {
		// This method is called when we encountered an opening '[['. The following
		// block could be a Category-statement, fixed object, property statements, 
		// or according print statements.
		$chunk = $this->readChunk();
		if ($chunk == $this->m_categoryprefix) { // category statement
			return $this->getCategoryDescription($setNS, $label);
		} else { // fixed subject, namespace restriction, property query, or subquery
			$sep = $this->readChunk('',false); //do not consume hit, "look ahead"
			if ($sep == '::') { // relation statement
				return $this->getPropertyDescription($chunk, $setNS, $label);
			} elseif ($sep == ':=') { // attribute statement
				return $this->getPropertyDescription($chunk, $setNS, $label);
			} else { // Fixed article/namespace restriction. $sep should be ]] or ||
				return $this->getArticleDescription($chunk, $setNS, $label);
			}
		}
	}

	/**
	 * Parse a category description (the part of an inline query that
	 * is in between "[[Category:" and the closing "]]" and create a
	 * suitable description.
	 */
	protected function getCategoryDescription(&$setNS, &$label) {
		// note: no subqueries allowed here, inline disjunction allowed, wildcards allowed
		$result = NULL;
		$continue = true;
		while ($continue) {
			$chunk = $this->readChunk();
			switch ($chunk) {
				case '*': //print statement
					$chunk = $this->readChunk('\]\]|\|');
					if ($chunk == '|') {
						$printlabel = $this->readChunk('\]\]');
						if ($printlabel != ']]') {
							$chunk = $this->readChunk('\]\]');
						} else {
							$printlabel = '';
							$chunk = ']]';
						}
					} else {
						global $wgContLang;
						$printlabel = $wgContLang->getNSText(NS_CATEGORY);
					}
					if ($chunk == ']]') {
						return new SMWPrintRequest(SMW_PRINT_CATS, $printlabel);
					} else {
						$this->m_errors[] = wfMsgForContent('smw_badprintout');
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
			$continue = ($chunk == '||');
		}

		return $this->finishLinkDescription($chunk, false, $result, $setNS, $label);
	}

	/**
	 * Parse a property description (the part of an inline query that
	 * is in between "[[Some property:=" and the closing "]]" and create a
	 * suitable description. The ":=" is the first chunk on the current
	 * string.
	 */
	protected function getPropertyDescription($propertyname, &$setNS, &$label) {
		global $smwgIP;
		include_once($smwgIP . '/includes/SMW_DataValueFactory.php');
		$this->readChunk(); // consume seperator ":="
		$property = Title::newFromText($propertyname, SMW_NS_PROPERTY);
		if ($property === NULL) {
			$this->m_errors[] .= wfMsgForContent('smw_badtitle', htmlspecialchars($propertyname));
			return NULL; ///TODO: read some more chunks and try to finish [[ ]]
		}

		$innerdesc = NULL;
		$continue = true;
		$typeid = SMWDataValueFactory::getPropertyObjectTypeID($property);
		while ($continue) {
			$chunk = $this->readChunk();
			switch ($chunk) {
				case '+': // wildcard, add namespaces for page-type properties
					if ( ($this->m_defaultns !== NULL) && ($typeid == '_wpg') ) {
						$innerdesc = $this->addDescription($innerdesc, $this->m_defaultns, false);
					} else {
						$innerdesc = $this->addDescription($innerdesc, new SMWThingDescription(), false);
					}
					$chunk = $this->readChunk();
				break;
				case '<q>': // subquery, set default namespaces
					if ($typeid == '_wpg') {
						$this->pushDelimiter('</q>');
						$setsubNS = true;
						$sublabel = '';
						$innerdesc = $this->addDescription($innerdesc, $this->getSubqueryDescription($setsubNS, $sublabel), false);
					} else { // no subqueries allowed for non-pages
						$this->m_errors[] = wfMsgForContent('smw_valuesubquery', $propertyname);
						$innerdesc = $this->addDescription($innerdesc, new SMWThingDescription(), false);
					}
					$chunk = $this->readChunk();
				break;
				default: //normal object value or print statement
					// read value(s), possibly with inner [[...]]
					$open = 1;
					$value = $chunk;
					$continue2 = true;
					while ( ($open > 0) && ($continue2) ) {
						$chunk = $this->readChunk('\[\[|\]\]|\|\||\|');
						switch ($chunk) {
							case '[[': // open new [[ ]]
								$open++;
							break;
							case ']]': // close [[ ]]
								$open--;
							break;
							case '|': case '||': // terminates only outermost [[ ]]
								if ($open == 1) {
									$open = 0;
								}
							break;
							case '': // this is not good ... TODO:report error
								$continue2 = false;
							break;
						}
						if ($open != 0) {
							$value .= $chunk;
						}
					} // note that at this point, we normally already read one more chunk behind the value

					if ($typeid == '__nry') { // nary value
						$dv = SMWDataValueFactory::newPropertyObjectValue($property);
						$dv->acceptQuerySyntax();
						$dv->setUserValue($value);
						$vl = $dv->getValueList();
						$pm = $dv->getPrintModifier();
						if ($vl !== NULL) { // prefer conditions over print statements (only one possible right now)
							$innerdesc = $this->addDescription($innerdesc, $vl, false);
						} elseif ($pm !== false) {
							if ($chunk == '|') {
								$printlabel = $this->readChunk('\]\]');
								if ($printlabel != ']]') {
									$chunk = $this->readChunk('\]\]');
								} else {
									$printlabel = '';
									$chunk = ']]';
								}
							} else {
								$printlabel = $property->getText();
							}
							if ($chunk == ']]') {
								return new SMWPrintRequest(SMW_PRINT_PROP, $printlabel, $property, $pm);
							} else {
								$this->m_errors[] = wfMsgForContent('smw_badprintout');
								return NULL;
							}
						}
					} else { // unary value
						$comparator = SMW_CMP_EQ;
						$printmodifier = '';
						$this->prepareValue($value, $comparator, $printmodifier);
						if ($value == '*') {
							if ($chunk == '|') {
								$printlabel = $this->readChunk('\]\]');
								if ($printlabel != ']]') {
									$chunk = $this->readChunk('\]\]');
								} else {
									$printlabel = '';
									$chunk = ']]';
								}
							} else {
								$printlabel = $property->getText();
							}
							if ($chunk == ']]') {
								return new SMWPrintRequest(SMW_PRINT_PROP, $printlabel, $property, $printmodifier);
							} else {
								$this->m_errors[] = wfMsgForContent('smw_badprintout');
								return NULL;
							}
						} else {
							$dv = SMWDataValueFactory::newPropertyObjectValue($property, $value);
							if (!$dv->isValid()) {
								$this->m_errors = $this->m_errors + $dv->getErrors();
								$vd = new SMWThingDescription();
							} else {
								$vd = new SMWValueDescription($dv, $comparator);
							}
							$innerdesc = $this->addDescription($innerdesc, $vd, false);
						}
					}
			}
			$continue = ($chunk == '||');
		}

		if ($innerdesc === NULL) { // make a wildcard search
			if ( ($this->m_defaultns !== NULL) && ($typeid == '_wpg') ) {
				$innerdesc = $this->addDescription($innerdesc, $this->m_defaultns, false);
			} else {
				$innerdesc = $this->addDescription($innerdesc, new SMWThingDescription(), false);
			}
			$this->m_errors[] = wfMsgForContent('smw_propvalueproblem', $property->getText());
		}
		$result = new SMWSomeProperty($property,$innerdesc);

		return $this->finishLinkDescription($chunk, false, $result, $setNS, $label);
	}


	/**
	 * Prepare a single value string, possibly extracting comparators and
	 * printmodifier. $value is changed to consist only of the remaining
	 * effective value string, or of "*" for print statements.
	 */
	protected function prepareValue(&$value, &$comparator, &$printmodifier) {
		// get print modifier behind *
		$list = preg_split('/^\*/',$value,2);
		if (count($list) == 2) { //hit
			$value = '*';
			$printmodifier = $list[1];
		} else {
			$printmodifier = '';
		}
		if ($value == '*') { // printout statement
			return;
		}
		$list = preg_split('/^(<|>|!)/',$value, 2, PREG_SPLIT_DELIM_CAPTURE);
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
				case '!':
					$comparator = SMW_CMP_NEQ;
					$value = $list[2];
				break;
				//default: not possible
			}
		}
	}
	
	/**
	 * Parse an article description (the part of an inline query that
	 * is in between "[[" and the closing "]]" if it is not specifying
	 * a category or property) and create a suitable description.
	 * The first chunk behind the "[[" has already been read and is
	 * passed as a parameter.
	 */
	protected function getArticleDescription($firstchunk, &$setNS, &$label) {
		global $smwgIP;
		include_once($smwgIP . '/includes/SMW_DataValueFactory.php');
		$chunk = $firstchunk;
		$result = NULL;
		$continue = true;
		//$innerdesc = NULL;
		while ($continue) {
			if ($chunk == '<q>') { // no subqueries of the form [[<q>...</q>]] (not needed)
				$this->m_errors[] = wfMsgForContent('smw_misplacedsubquery');
				return NULL;
			}
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
				$value = SMWDataValueFactory::newTypeIDValue('_wpg', $chunk);
				if ($value->isValid()) {
					$result = $this->addDescription($result, new SMWValueDescription($value), false);
				}
			}

			$chunk = $this->readChunk();
			if ($chunk == '||') {
				$chunk = $this->readChunk();
				$continue = true;
			} else {
				$continue = false;
			}
		}

		return $this->finishLinkDescription($chunk, true, $result, $setNS, $label);
	}
	
	protected function finishLinkDescription($chunk, $hasNamespaces, $result, &$setNS, &$label) {
		if ($result === NULL) { // no useful information or concrete error found
			$this->m_errors[] = wfMsgForContent('smw_badqueryatom');
		} elseif (!$hasNamespaces && $setNS && ($this->m_defaultns !== NULL) ) {
			$result = $this->addDescription($result, $this->m_defaultns);
			$hasNamespaces = true;
		}
		$setNS = $hasNamespaces;

		// terminate link (assuming that next chunk was read already)
		if ($chunk == '|') {
			$chunk = $this->readChunk('\]\]');
			if ($chunk != ']]') {
				$label .= $chunk;
				$chunk = $this->readChunk('\]\]');
			} else { // empty label does not add to overall label
				$chunk = ']]';
			}
		}
		if ($chunk != ']]') {
			// What happended? We found some chunk that could not be processed as
			// link content (as in [[Category:Test<q>]]) and there was no label to 
			// eat it. Or the closing ]] are just missing entirely.
			if ($chunk != '') {
				$this->m_errors[] = wfMsgForContent('smw_misplacedsymbol', htmlspecialchars($chunk));
				// try to find a later closing ]] to finish this misshaped subpart
				$chunk = $this->readChunk('\]\]');
				if ($chunk != ']]') {
					$chunk = $this->readChunk('\]\]');
				}
			}
			if ($chunk == '') {
				$this->m_errors[] = wfMsgForContent('smw_noclosingbrackets');
			}
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
	 * 
	 * $consume specifies whether the returned chunk should be removed from the
	 * query string.
	 */
	protected function readChunk($stoppattern = '', $consume=true) {
		if ($stoppattern == '') {
			$stoppattern = '\[\[|\]\]|::|:=|<q>|<\/q>|^' . $this->m_categoryprefix . '|\|\||\|';
		}
		$chunks = preg_split('/[\s]*(' . $stoppattern . ')[\s]*/', $this->m_curstring, 2, PREG_SPLIT_DELIM_CAPTURE);
		if (count($chunks) == 1) { // no matches anymore, strip spaces and finish
			if ($consume) {
				$this->m_curstring = '';
			}
			return trim($chunks[0]);
		} elseif (count($chunks) == 3) { // this chould generally happen if count is not 1
			if ($chunks[0] == '') { // string started with delimiter
				if ($consume) {
					$this->m_curstring = $chunks[2];
				}
				return $chunks[1]; // spaces stripped already
			} else {
				if ($consume) {
					$this->m_curstring = $chunks[1] . $chunks[2];
				}
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
		if ($newdesc === NULL) {
			return $curdesc;
		} elseif ($curdesc === NULL) {
			return $newdesc;
		} else { // we already found descriptions
			if ( (($conjunction)  && ($curdesc instanceof SMWConjunction)) ||
			     ((!$conjunction) && ($curdesc instanceof SMWDisjunction)) ) { // use existing container
				$curdesc->addDescription($newdesc);
				return $curdesc;
			} elseif ($conjunction) { // make new conjunction
				return new SMWConjunction(array($curdesc,$newdesc));
			} else { // make new disjunction
				global $smwgQDisjunctionSupport;
				if ($smwgQDisjunctionSupport) {
					return new SMWDisjunction(array($curdesc,$newdesc));
				} else {
					$this->m_errors[] = wfMsgForContent('smw_nodisjunctions', $newdesc->getQueryString());
					return $curdesc;
				}
			}
		}
	}
}
 

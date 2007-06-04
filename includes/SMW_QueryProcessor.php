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
		/// TODO implement
		// DEBUG:
			$o_desc = new SMWNominalDescription(Title::newFromText("Africa"));
			$value = SMWDataValue::newAttributeValue('Population','5853000');
			$t_desc = new SMWThingDescription();
			$v_desc = new SMWValueDescription($value, SMW_CMP_GEQ);
			$a_desc = new SMWSomeAttribute(Title::newFromText('Attribute:Population'), $v_desc);
			$r_desc = new SMWSomeRelation(Title::newFromText("Relation:located in"), $o_desc);
			$r_desc2 = new SMWSomeRelation(Title::newFromText("Relation:borders"), $r_desc);
			$r_desc3 = new SMWSomeRelation(Title::newFromText("Relation:located in"), $t_desc);
			$c_desc = new SMWClassDescription(Title::newFromText("Category:Country"));
			$desc = new SMWConjunction(array($c_desc, $r_desc));
			$desc2 = new SMWConjunction(array($c_desc, $a_desc, $r_desc2, $r_desc));
			$pr1 = new SMWPrintrequest(SMW_PRINT_THIS, 'Country');
			$desc->addPrintRequest($pr1);
			$desc2->addPrintRequest($pr1);
			$pr2 = new SMWPrintrequest(SMW_PRINT_RELS, 'Borders', Title::newFromText('Relation:Borders'));
			$desc->addPrintRequest($pr2);
			$pr3 = new SMWPrintrequest(SMW_PRINT_ATTS, 'Population', Title::newFromText('Attribute:Population'));
			$desc->addPrintRequest($pr3);
			$desc2->addPrintRequest($pr3);
			$pr4 = new SMWPrintrequest(SMW_PRINT_CATS, 'Categories');
			$desc->addPrintRequest($pr4);
			//$query = new SMWQuery($desc);
			$query = new SMWQuery($desc2);

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
 
?>
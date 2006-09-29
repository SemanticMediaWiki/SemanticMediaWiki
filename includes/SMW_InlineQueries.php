<?php
/**
 * This feature implements inline queries, i.e. it offers the possibility
 * to query against MediaWiki's own semantic knowledgebase by putting questions
 * inside articles. In the rendereda article, those questions are replaced with 
 * the respective answers.
 *
 * Scalability is the major challenge for inline queries, so their complexity
 * can be restricted through various parameters. For example, one can adjust
 * them so that every query can be computed by the database in linear space
 * (wrt. the smallest result set obtained by one of the conditions)(1). Features
 * such as nesting of queries and sorting of results can also be switched off.(2)
 *
 * (1) Currently, this is automatic, since basic result sets consist only of subjects
 *     and since queries have a simple form.
 * (2) Nesting depth of subqueries not controlled yet. But you can limit the number
 *     of database tables used overall.
 *
 * TODO: add parameters for determining the maximum depth of subqueries, the
 * maximum number of conditions, the maximum number of disjunctions (?), the 
 * maximum number of real joins (when selecting a condition for printout)
 * TODO: add ordering to aggregated printout
 * TODO: change database layout to have an optional page_id for objects of rels;
 * since subqueries can only select *existting* articles, it is save to join them
 * via this id, which will be much faster
 * TODO: change database layout to include attribute id (maybe instead of its name?);
 * attributes have to exist or are not stored, so this is save; CAREFUL: might 
 * disallow fallback to Type:String for unknown attibutes ... but do we really have to 
 * search for those?
 * TODO: include error reporting for query parsing; make new messages for informing
 * the user about severe problems in understanding the query, and for warning if
 * certain resources where used up (too deep subqueries etc.)
 * TODO: allow combination of subqueries and disjunctions
 * TODO: allow parentheses and disjunctions between query parts
 */

require_once( "$IP/includes/Title.php" );

/* The variables below define the default settings. Changes can be made by
   setting new values in SMW_LocalSettings.php */

/* Configure default behaviour of inline queries */
	// Default linking behaviour. Can be one of "none", "subject", "all"
	$smwgIQDefaultLinking = 'subject';
/* Configure power/performance trade-off for inline queries */
	// Switches on or off all queries.
	$smwgIQEnabled = true;
	// Maximum number of conditions *overall*. Each value restriction is counted as
	// one, i.e. a disjunction || counts for each of its disjuncts. Note that subcategories
	// and resolved redirects currently extend the number of conditions as well!
	$smwgIQMaxConditions = 50;
	// Maximum number of tables to be joined *overall*. Note that the combination of DISTINCT
	// and restricted SELECT statements ensures that further joins do not multiply the number
	// of results. Still it is desirable to restrict this number below $smwgIQMaxConditions.
	$smwgIQMaxTables = 10;
	// Maximum number of additional fields that can be displayed using statements with '*'.
	// This implicitly also limits the number of rows in output tables.
	$smwgIQMaxPrintout = 10;
	// Switches on or off all subqueries.
	$smwgIQSubQueriesEnabled = true;
	// Default number of rows returned in a query.
	$smwgIQMaxLimit = 400;
	// Default number of rows returned in a query.
	$smwgIQDefaultLimit = 50;
	// If true, disjunctive queries are enabled. May cost performance.
	$smwgIQDisjunctiveQueriesEnabled = true;
	// Sets the level of inclusions by subCategory-relations. For example, if Student
	// is a Subcategory of Person, Students will only be returned if querying for Persons
	// if this is set at least to 1 (as it is a direct Subcategory).
	// Disjunctive queries must be enabled for this feature to work. May cost performance.
	$smwgIQSubcategoryInclusions = 10;
	// Normalizes the redirects, basically implements the sameAs semantics
	// as defined by the mapping of redirects to sameAs.
	// Disjunctive queries must be enabled for this feature to work. May cost performance.
	$smwgIQRedirectNormalization = true;
	// If true, sorting is enabled. May cost performance.
	$smwgIQSortingEnabled = true;

// first, we register a hook that will register a hook in the parser
global $wgHooks;
$wgHooks['ParserBeforeStrip'][] = 'smwfRegisterInlineQueries';

// This hook registers a hook in the parser
function smwfRegisterInlineQueries( $semantic, $mediawiki, $rules ) {
	global $wgParser;
	$wgParser->setHook( 'ask', 'smwfProcessInlineQueries' );
}

/**
 * Everything that is between <ask> and </ask> gets processed here
 * $text is the query in the query format described elsewhere
 * $param is an array which might contain values for the following keys
 * (all optional):
 * format  -- Either 'list' (seperated by sep), 'ul' (for unordered bullet list),
 *            'ol' (ordered and numbered list), 'table', or 'auto' (default).
 * sep     -- Customized separator string for use with format 'list'.
 * limit   -- Maximal number of answers. It will be capped by $smwgIQMaxLimit anyway.
 *            Defaults to $smwgIQDefaultLimit.
 * link    -- Either none, subject or all. Makes links out of the results.
 *            Defaults to $smwgIQDefaultLinking.
 * default -- If no result is found, this string will be returned.
 * intro   -- Plain text that is to be displayed before the query, but only if any results are obtained.
 * sort    -- Name of the row by which to sort.
 * order   -- Either 'ascending' (equal to 'asc', default) or 'descending' (equal to 'desc' or 'reverse')
 * headers -- How to display the header properties of columns, can be one of 'show' 
 *            (default) and 'hide'. Maybe 'link' will follow in the future.
 * mainlabel -- Label to use for the column that shows the main subjects. Also used to indicate that
 *              the subject should be displayed in cases where it would normally be hidden.
 */
function smwfProcessInlineQueries( $text, $param ) {
	global $smwgIQEnabled;
	$iq = new SMWInlineQuery($param);
	if ($smwgIQEnabled) {
		return $iq->getHTMLResult($text);
	} else {
		return wfMsgForContent('smw_iq_disabled');
	}
}

// Constants for distinguishing various modes of printout;
// used to record what part of a query should be printed; see mPrint field
// below
define('SMW_IQ_PRINT_CATS', 0);  // print all direct categories
define('SMW_IQ_PRINT_RELS', 1);  // print all relations objects of a certain relation
define('SMW_IQ_PRINT_ATTS', 2);  // print all attribute values of a certain attribute
define('SMW_IQ_PRINT_RSEL', 3);  // print certain relation objects selected by the query
define('SMW_IQ_PRINT_ASEL', 4);  // print certain attribute values selected by the query


/**
 * A simple data container for storing the essential components of SQL queries.
 * Used to have a struct as convenient reutrn value to functions that construct queries.
 */
class SMWSQLQuery {
	public $mConditions; // SQL conditions as a string
	public $mTables; // SQL table names and and possible aliases as comma-separated a string
	public $mSelect; // array of fields to select, no aliases are allowed here
	public $mOrderBy; // NULL or name of a single field by which the result should be ordered
	public $mFixedSubject; // true if the subject of the query has been given explicitly
	public $mPrint; // array of things to print; format: id=>array(label,mode[,namestring[,datavalue]])
	
	public $mDebug; // some field for debugging //DEBUG
	
	function SMWSQLQuery() {
		$this->mConditions = '';
		$this->mTables = '';
		$this->mSelect = array();
		$this->mOrderBy = NULL;
		$this->mFixedSubject = false;
		$this->mPrint = array();
	}
}


class SMWInlineQuery {

	// parameters:
	private $mLimit; // max number of answers, also used when printing values of one particular subject
	private $mSort;  // name of the row by which to sort
	private $mOrder; // string that identifies sort order, 'ASC' (default) or 'DESC'
	private $mFormat;  // a string identifier describing a valid format
	private $mListSep; // for format 'list' this can be a custom separator
	private $mIntro; // text to print before the output in case it is *not* empty
	private $mLinkSubj; // should article names of the (unique) subjects be linked?
	private $mLinkObj; // should article names of the objects be linked?
	private $mDefault; // default return value for empty queries
	private $mShowHeaders; // should the headers (property names) be printed?
	private $mMainLabel; // label used for displaying the subject, or NULL if none was given
	private $mShowDebug; // should debug output be generated?

	// fields used during query processing:
	private $dbr; // pointer to the database used throughout exectution of the query
	private $mRename; // integer counter to rename tables in SQL joins
	private $mSubQueries; // array of subqueries, indexed by placeholder indices
	private $mSQCount; // integer counter to replace subqueries
	private $mConditionCount; // count the number of conditions used so far
	private $mTableCount; // count the number of tables joined so far
	private $mPrintoutCount; // count the number of fields selected for separate printout so far

	// fields used for output formatting:
	private $mHeaderText; // text to be printed before the output of the results
	private $mFooterText; // text to be printed after the output of the results
	private $mRowSep; // string between two rows
	private $mLastRowSep; // string between the last but one and last row
	// Note: the strings before the first and after the last row are printed with the header and footer
	private $mSeparators; // array of separator strings to be printed *before* each column content, format 'column_id' => 'separator_string'
	private $mValueSep; // string between two values for one property

	function SMWInlineQuery($param = array()) {
		global $smwgIQDefaultLimit, $smwgIQDefaultLinking;

		$this->mLimit = $smwgIQDefaultLimit;
		$this->mSort = NULL;
		$this->mOrder = 'ASC';
		$this->mFormat = 'auto';
		$this->mListSep = NULL; //use default list
		$this->mIntro = '';
		$this->mLinkSubj = ($smwgIQDefaultLinking != 'none');
		$this->mLinkObj = ($smwgIQDefaultLinking == 'all');
		$this->mDefault = '';
		$this->mShowHeaders = true;
		$this->mMainLabel = NULL;
		$this->mShowDebug = false;

		$this->setParameters($param);
	}

	/**
	 * Set the internal settings according to an array of parameter values.
	 */
	function setParameters($param) {
		global $smwgIQMaxLimit;
		if ( (array_key_exists('limit',$param)) && (is_int($param['limit'] + 0)) ) {
			$this->mLimit = min($smwgIQMaxLimit, max(1,$param['limit'] + 0)); //integer between 1 and $smwgMaxLimit
		}
		if (array_key_exists('sort', $param)) {
			$this->mSort = smwfNormalTitleDBKey($param['sort']);
		}
		if (array_key_exists('order', $param)) {
			if (('descending'==$param['order'])||('reverse'==$param['order'])||('desc'==$param['order'])) {
				$this->mOrder = "DESC";
			}
		}
		if (array_key_exists('format', $param)) {
			$this->mFormat = $param['format'];
			if (($this->mFormat != 'ul') && ($this->mFormat != 'ol') && ($this->mFormat != 'list') && ($this->mFormat != 'table'))
				$this->mFormat = 'auto'; // If it is an unknown format, default to list again
		}
		if (array_key_exists('sep', $param)) {
			$this->mListSep = htmlspecialchars($param['sep']);
		}
		if (array_key_exists('intro', $param)) {
			$this->mIntro = htmlspecialchars($param['intro']);
		}
		if (array_key_exists('link', $param)) {
			switch ($param['link']) {
			case 'head': case 'subject':
				$this->mLinkSubj = true;
				$this->mLinkObj  = false;
				break;
			case 'all':
				$this->mLinkSubj = true;
				$this->mLinkObj  = true;
				break;
			case 'none':
				$this->mLinkSubj = false;
				$this->mLinkObj  = false;
				break;
			}
		}
		if (array_key_exists('default', $param)) {
			$this->mDefault = htmlspecialchars($param['default']);
		}
		if (array_key_exists('headers', $param)) {
			if ( 'hide' == $param['headers']) {
				$this->mShowHeaders = false;
			} else {
				$this->mShowHeaders = true;
			}
		}
		if (array_key_exists('mainlabel', $param)) {
			$this->mMainLabel = htmlspecialchars($param['mainlabel']);
		}
		if (array_key_exists('debug', $param)) {
			$this->mShowDebug = true;
		}
	}

	/**
	 * Main entry point for parsing, executing, and printing a given query text.
	 */
	function getHTMLResult( $text ) {
		global $smwgIQSortingEnabled, $smwgIQRunningNumber;
		if (!isset($smwgIQRunningNumber)) {
			$smwgIQRunningNumber = 0;
		} else { $smwgIQRunningNumber++; }

		// this should also replace template parameters -- but it does not, and
		// I don't have the slightes clue why. The code afterwards was for testing. Don't
		// delete it until the template / extension - interplay problem is fixed. (denny)
		global $wgParser;
		//FIXME I think it is really really bad to use wgParser here, since we don't know
		//      in which context the inline query was called. $wgParser might even be NULL. -- mak
		$text = $wgParser->replaceVariables($text);
		//	$tempOutputType = $wgParser->mOutputType;
		//	$wgParser->mOutputType = OT_HTML;
		//	$text = $wgParser->replaceVariables($text, end( $wgParser->mArgStack ), false);
		//	$wgParser->mOutputType = $tempOutputType;
		//	$text = $wgParser->replaceVariables($text, end( $wgParser->mArgStack ));
		//	$result .= $text;
		//	return $result;

		$this->dbr =& wfGetDB( DB_SLAVE ); // Note: if this fails, there were worse errors before; don't check it
		$this->mRename = 0;
		$this->mSubQueries = array();
		$this->mSQCount = 0;
		$this->mConditionCount = 0;
		$this->mTableCount = 0;
		$this->mPrintoutCount = 0;
		$sq = $this->parseQuery($text);

		$sq->mSelect[0] .= ' AS page_id';
		$sq->mSelect[1] .= ' AS page_title';
		$sq->mSelect[2] .= ' AS page_namespace';

		$sql_options = array('LIMIT' => $this->mLimit); // additional options (order by, limit)
		if ( $smwgIQSortingEnabled ) {
			if ( NULL == $sq->mOrderBy ) {
				$sql_options['ORDER BY'] = "page_title $this->mOrder "; // default
			} else {
				$sql_options['ORDER BY'] = "$sq->mOrderBy $this->mOrder ";
			}
		}

		if ($this->mShowDebug) {
			return $sq->mDebug; // DEBUG
		}

		//*** Execute the query ***//
		$res = $this->dbr->select( 
		         $sq->mTables,
		         'DISTINCT ' . implode(',', $sq->mSelect),
		         $sq->mConditions,
		         "SMW::InlineQuery" ,
		         $sql_options );

		//*** Create the output ***//

		//No results
		if ( (!$res) || (0 == $this->dbr->numRows( $res )) ) return $this->mDefault;

		// Cases in which to print the subject:
		if ((!$sq->mFixedSubject) || (0 == count($sq->mPrint)) || (NULL != $this->mMainLabel)) { 
			if (NULL == $this->mMainLabel) {
				$sq->mPrint = array('' => array('',SMW_IQ_PRINT_RSEL,'page_')) + $sq->mPrint;
			} else {
				$sq->mPrint = array('' => array($this->mMainLabel,SMW_IQ_PRINT_RSEL,'page_')) + $sq->mPrint;
			}
		}

		//Determine format if 'auto', also for backwards compatibility
		if ( 'auto' == $this->mFormat ) {
			if (count($sq->mPrint)>1)
				$this->mFormat = 'table';
			else $this->mFormat = 'list';
		}

		$this->initOutputStrings($sq->mPrint);
		$result = $this->mHeaderText;

		// Print main content (if any results were returned)
		$firstrow = true;
		$row = $this->dbr->fetchRow( $res );
		while ( $row ) {
				$nextrow = $this->dbr->fetchRow( $res ); // look ahead
				if (!$firstrow) {
					if ($nextrow)
						$result .= $this->mRowSep;
					else $result .= $this->mLastRowSep;
				}
				$result .= $this->makeRow($row, $sq->mPrint);
				$row = $nextrow;
				$firstrow = false;
		}
		$this->dbr->freeResult($res); // Things that should be free: #42 "Possibly large query results"

		$result .= $this->mFooterText;
		return $result;
	}

	/*********************************************************************/
	/* Methods for query parsing and execution                           */
	/*********************************************************************/

	/**
	 * Callback used for extracting sub queries from a query, and replacing
	 * them by some reference for later evaluation.
	 */
	function extractSubQuery($matches) {
		global $smwgIQSubQueriesEnabled;
		if ($smwgIQSubQueriesEnabled) {
			$this->mSubQueries[$this->mSQCount] = $matches[1];
			return '+' . $this->mSQCount++;
		} else { return '+'; } // just do a wildcard search instead of processing the subquery
	}

	/**
	 * Basic function for extracting a query from a user-supplied string.
	 * The result is an object of type SMQSQLQuery.
	 */
	function parseQuery($querytext) {
		global $wgContLang, $smwgIQDisjunctiveQueriesEnabled, $smwgIQSubcategoryInclusions, $smwgIQMaxConditions, $smwgIQMaxTables, $smwgIQMaxPrintout;

		// Extract subqueries:
		$querytext = preg_replace_callback("/<q>(.*)<\/q>/",
		             array(&$this,'extractSubQuery'),$querytext);
		// Extract queryparts: 
		$queryparts = preg_split("/[\s\n]*\[\[|\]\][\s\n]*\[\[|\]\][\s\n]*/",$querytext,-1,PREG_SPLIT_NO_EMPTY);

		$result = new SMWSQLQuery();
		$cat_sep = $wgContLang->getNsText(NS_CATEGORY) . ":";

		$pagetable = 't' . $this->mRename++;
		$result->mSelect = array($pagetable . '.page_id', $pagetable . '.page_title' , $pagetable . '.page_namespace'); // always select subject
		$result->mTables = $this->dbr->tableName('page') . " AS $pagetable";

		foreach ($queryparts as $q) {
			$qparts = preg_split("/(::|:=[><]?|^$cat_sep)/", $q, 2, PREG_SPLIT_DELIM_CAPTURE);
			if (count($qparts)<=2) { // $q was not something like "xxx:=yyy", probably a fixed subject
				$qparts = array('','',$q); // this saves a lot of code below ;-)
			}
			$op = $qparts[1];

			if (mb_substr($qparts[2],0,1) == '*') { // conjunct is a print command
				if ( $this->mPrintoutCount < $smwgIQMaxPrintout ) {
					$altpos = mb_strpos($qparts[2],'|');
					if (false !==  $altpos) {
						$label = htmlspecialchars(mb_substr($qparts[2],$altpos+1));
						$qparts[2] = mb_substr($qparts[2],0,$altpos);
					} else {
						$label = $qparts[0];
					}
					if ($cat_sep == $op) { // eventually print all categories for the selected subjects
						if ('' == $label) $label = $wgContLang->getNSText(NS_CATEGORY);
						$result->mPrint['C'] = array($label,SMW_IQ_PRINT_CATS);
					} elseif ( '::' == $op ) {
						$result->mPrint['R:' . $qparts[0]] = array($label,SMW_IQ_PRINT_RELS,smwfNormalTitleDBKey($qparts[0]));
					} elseif ( ':=' == $op ) {
						$av = SMWDataValue::newAttributeValue($qparts[0]);
						$unit = mb_substr($qparts[2],1);
						if ($unit != '') { // desired unit selected:
							$av->setDesiredUnits(array($unit));
						}
						$result->mPrint['A:' . $qparts[0]] = array($label,SMW_IQ_PRINT_ATTS,smwfNormalTitleDBKey($qparts[0]),$av);
					} // else: operators like :=> are not supported for printing and are silently ignored
					$this->mPrintoutCount++;
				}
			} elseif ( ($this->mConditionCount < $smwgIQMaxConditions) && ($this->mTableCount < $smwgIQMaxTables) ) { // conjunct is a real condition
				$sq_title = '';
				if (mb_substr($qparts[2],0,1) == '+') { // sub-query or wildcard search
					$subq_id = mb_substr($qparts[2],1);
					if ( ('' != $subq_id) && (array_key_exists($subq_id,$this->mSubQueries)) ) {
						$sq = $this->parseQuery($this->mSubQueries[$subq_id]);
						if ( ('' != $sq->mConditions) && ($this->mConditionCount < $smwgIQMaxConditions) && ($this->mTableCount < $smwgIQMaxTables) ) {
							$result->mTables .= ',' . $sq->mTables;
							if ( '' != $result->mConditions ) $result->mConditions .= ' AND ';
							$result->mConditions .= '(' . $sq->mConditions . ')';
							$sq_title = $sq->mSelect[1];
							$sq_namespace = $sq->mSelect[2];
						} else {
							$values = array(); // ignore sub-query and make a wild-card search
						}
					}
					$values = array();
				} elseif ($smwgIQDisjunctiveQueriesEnabled) { // get single values
					$values = explode('||', $qparts[2]);
				} else {
					$values = array($qparts[2]);
				}
				$or_conditions = array(); //store disjunctive SQL conditions; simplifies serialisation below
				$condition = ''; // new sql condition
				$curtable = 't' . $this->mRename++; // alias for the current table

				if ($cat_sep == $op ) { // condition on category membership
					$result->mTables .= ',' . $this->dbr->tableName('categorylinks') . " AS $curtable";
					$condition = "$pagetable.page_id=$curtable.cl_from";
					// TODO: make subcat-inclusion more efficient
					foreach ($values as $idx => $v) {
						$values[$idx] = smwfNormalTitleDBKey($v);
					}
					$this->includeSubcategories($values,$smwgIQSubcategoryInclusions);
					foreach ($values as $v) {
						$or_conditions[] = "$curtable.cl_to=" . $this->dbr->addQuotes($v);
					}
				} elseif ('::' == $op ) { // condition on relations
					$relation = smwfNormalTitleDBKey($qparts[0]);
					$result->mTables .= ',' . $this->dbr->tableName('smw_relations') . " AS $curtable";
					$condition = "$pagetable.page_id=$curtable.subject_id AND $curtable.relation_title=" . $this->dbr->addQuotes($relation);
					if ('' != $sq_title) { // objects supplied by subquery
						$condition .= " AND $curtable.object_title=$sq_title AND $curtable.object_namespace=$sq_namespace";
					} else { // objects given explicitly
						// TODO: including redirects should become more efficient
						// (maybe by not creating a full symmetric transitive closure and
						//  using a simple SQL query instead)
						//  Also, redirects are not taken into account for sub-queries
						//  anymore now.
						foreach ($values as $idx => $v) {
							$values[$idx] = smwfNormalTitleDBKey($v);
						}
						$value = $this->normalizeRedirects($values);
						// search for values
						foreach ($values as $v) {
							$vtitle = Title::newFromText($v);
							if (NULL != $vtitle) {
								$or_conditions[] = "$curtable.object_title=" . $this->dbr->addQuotes($vtitle->getDBKey()) . " AND $curtable.object_namespace=" . $vtitle->getNamespace();
							}
						}
					}
					if ($relation == $this->mSort) {
						$result->mOrderBy = "$curtable.object_title";
					}
				} elseif ('' == $op) { // fixed subject, possibly namespace restriction
					if ('' != $sq_title) { // objects supplied by subquery
						$condition = "$pagetable.page_title=$sq_title AND $pagetable.page_namespace=$sq_namespace";
					} else { // objects given explicitly
						//Note: I do not think we have to include redirects here. Redirects should not 
						//      have annotations, so one can just write up the query correctly! -- mak	
						foreach ($values as $v) {
							$v = smwfNormalTitleDBKey($v);
							if (':' == mb_substr($v,0,1)) $v = mb_substr($v,1); // remove initial ':'
							// TODO: should this be done when normalizing the title???
							$ns_idx = $wgContLang->getNsIndex(mb_substr($v,0,-2)); // assume format "Namespace:+"
							if (false === $ns_idx) {
								$or_conditions[] = "$pagetable.page_title=" . $this->dbr->addQuotes($v);
								$result->mFixedSubject = true; // by default, only this case is a realy "fixed" subject (even though it could still be combined with others); TODO: find a better way for deciding whether to show the first column or not
							} else {
								$or_conditions[] = "$pagetable.page_namespace=$ns_idx";
							}
						}
					}
				} else { // some attribute operator
					$attribute = smwfNormalTitleDBKey($qparts[0]);
					$av = SMWDataValue::newAttributeValue($attribute);
					switch ($op) {
						case ':=>': $comparator = '>='; break;
						case ':=<': $comparator = '<='; break;
						default: $comparator = '=';
					}
					foreach ($values as $v) {
						$av->setUserValue($v);
						if ($av->isValid()) {// TODO: it should be possible to ignore the unit for many types
							if ($av->isNumeric()) {
								$or_conditions[] = "$curtable.value_num$comparator" . $av->getNumericValue() . " AND $curtable.value_unit=" . $this->dbr->addQuotes($av->getUnit()); 
							} else {
								$or_conditions[] = "$curtable.value_xsd$comparator" . $this->dbr->addQuotes($av->getXSDValue()) . " AND $curtable.value_unit=" . $this->dbr->addQuotes($av->getUnit()); 
							}
						}
					}
					$result->mTables .= ',' . $this->dbr->tableName('smw_attributes') . " AS $curtable";
					$condition = "$pagetable.page_id=$curtable.subject_id AND $curtable.attribute_title=" . $this->dbr->addQuotes($attribute);
					if ($attribute == $this->mSort) {
						if ($av->isNumeric()) $result->mOrderBy = $curtable . '.value_num';
						  else $result->mOrderBy = $curtable . '.value_xsd';
					}
				}

				// build query from disjuncts:
				$firstcond = true;
				foreach ($or_conditions as $cond) {
					if ($this->mConditionCount >= $smwgIQMaxConditions) break;
					if ($firstcond) {
						if ('' != $condition) $condition .= ' AND ';
						$condition .= '((';
						$firstcond = false;
					} else {
						$condition .= ') OR (';
						$this->mConditionCount++; // (the first condition is counted with the main part)
					}
					$condition .= $cond;
				}
				if (count($or_conditions)>0) $condition .= '))';
				if ('' != $condition) {
					if ('' != $result->mConditions) $result->mConditions .= ' AND ';
					$this->mConditionCount++;
					$this->mTableCount++;
					$result->mConditions .= $condition;
				}
			}
		}

		$result->mDebug = "\n SELECT " . implode(',',$result->mSelect) . "\n FROM $result->mTables\n WHERE $result->mConditions" . " \n Conds:$this->mConditionCount Tables:$this->mTableCount Printout:$this->mPrintoutCount"; //DEBUG

		return $result;
	}

	/**
	 * Turns an array of articles into an array of all these articles and
	 * the transitive closure of all redirects from and to this articles.
	 * Or, simply said: it gets all aliases of what you put in.
	 *
	 * FIXME: store intermediate result in a temporary DB table on the heap; much faster!
	 * FIXME: include an option to ignore multiple redirects, or, even better, make a 
	 * plugable SQL-query to compute one-step back-and-forth redirects without any 
	 * materialisation.
	 */
	function normalizeRedirects(&$articles) {
		global $smwgIQRedirectNormalization;
		if (!$smwgIQRedirectNormalization) {
			return $articles;
		}
		$stable = FALSE;
		$check_articles = array_diff( $articles , array() ); // Copies the array
		while (!$stable) {
			$new_articles = array();
			foreach ( $check_articles as $article ) {
				// there...
				$res = $this->dbr->select(
					array( 'page' , 'pagelinks' ),
					array( 'pl_title' ),
					array(  'page_title = ' . $this->dbr->addQuotes( $article ),
							'page_is_redirect = 1',
							'page_id = pl_from' ) ,
							"SMW::NormalizeRedirects" );
				if ( $res ) {
					while ( $res && $row = $this->dbr->fetchRow( $res )) {
						if ( array_key_exists( 'pl_title', $row) ) {
							$new_article = $row[ 'pl_title' ];
							if (!in_array( $new_article , $articles)) {
								$new_articles[] = $new_article;
							}
						}
					}
				}
				$this->dbr->freeResult( $res );
	
				// ... and back again
				$res = $this->dbr->select(
					array( 'page' , 'pagelinks' ),
					array( 'page_title' ),
					array(  'pl_title = '. $this->dbr->addQuotes( $article ),
							'page_is_redirect = 1',
							'page_id = pl_from' ) ,
							"SMW::NormalizeRedirects" );
				if ( $res ) {
					while ( $res && $row = $this->dbr->fetchRow( $res )) {
						if ( array_key_exists( 'page_title', $row) ) {
							$new_article = $row[ 'page_title' ];
							if (!in_array( $new_article , $articles)) {
								$new_articles[] = $new_article;
							}
						}
					}
				}
				$this->dbr->freeResult( $res );
			}
			$stable = (count($new_articles)==0);
			$articles = array_merge( $articles, $new_articles );
			$check_articles = array_diff( $new_articles , array() );
		}
		return $articles;
	}
	
	/**
	 * Turns an array of categories to an array of categories and its subcategories.
	 * The number of relations followed is given by $levels.
	 *
	 * FIXME: store intermediate result in a temporary DB table on the heap; much faster!
	 */
	function includeSubcategories( &$categories, $levels ) {
		if (0 == $levels) return $categories;

		$checkcategories = array_diff($categories, array()); // Copies the array
		for ($level=$levels; $level>0; $level--) {
			$newcategories = array();
			foreach ($checkcategories as $category) {
				$res = $this->dbr->select( // make the query
					array( 'categorylinks', 'page' ),
					array( 'page_title' ),
					array(  'cl_from = page_id ',
							'page_namespace = '. NS_CATEGORY,
							'cl_to = '. $this->dbr->addQuotes($category) ) ,
							"SMW::SubCategory" );
				if ( $res ) {
					while ( $res && $row = $this->dbr->fetchRow( $res )) {
						if ( array_key_exists( 'page_title' , $row) ) {
							$new_category = $row[ 'page_title' ];
							if (!in_array($new_category, $categories)) {
								$newcategories[] = smwfNormalTitleDBKey($new_category);
							}
						}
					}
					$this->dbr->freeResult( $res );
				}
			}
			if (count($newcategories) == 0) {
				return $categories;
			} else {
				$categories = array_merge($categories, $newcategories);
			}
			$checkcategories = array_diff($newcategories, array());
		}
		return $categories;
	}

	/*********************************************************************/
	/* Output helper methods                                             */
	/*********************************************************************/	

	/**
	 * This method initialises all kinds of strings used to format the output.
	 * Since many conditions are involved in computing the output format, it is
	 * most efficient to do this once and to reuse the intitialised values for
	 * actually printing the output later on.
	 *
	 * Basically all text generated by a query is defined in this method. The
	 * final output format is composed of individual strings as follows:
	 * mHeaderText . ROW . mRowSep . ROW  . mRowSep . ... . ROW . mLastRowSep  ROW . mFooterText
	 * where ROW has the form
	 * mSeperators['col_id1'] . valuetext_col_1 . mSeperators['col_id2'] . ... . valuetext_col_n
	 * and valuetext_col_i is composed of the output strings for all values in this field,
	 * separated by the string mValueSep.
	 *
	 * The parameter $print contains an array of things to be printed in the format returned
	 * when parsing a query.
	 */
	function initOutputStrings(&$print) {
		$this->mSeparators = array();
		switch ($this->mFormat) {
		case 'table':
			global $smwgIQRunningNumber;
			$this->mHeaderText = $this->mIntro . "<table class=\"smwtable\" id=\"querytable" . $smwgIQRunningNumber . "\">\n\t\t<tr>\n";
			$this->mFooterText = "</td>\n\t\t</tr>\n\t</table>";
			$this->mRowSep = "</td>\n\t\t</tr>\n\t\t<tr>\n";
			$this->mLastRowSep = "</td>\n\t\t</tr>\n\t\t<tr>\n";
			$this->mValueSep = '<br/>';

			// create header cells and determine separators
			$first = true;
			foreach ($print as $column_id => $print_data) {
				if ('' == $print_data[0]) $print_data[0] = '&nbsp;'; // we need something to click on
				if ($this->mShowHeaders) $this->mHeaderText .= "\t\t\t<th>" . $print_data[0] . "</th>\n";
				// FIXME: just not printing headers does not work with the JScript tables nicely
				if ($first) {
					$first = false;
					$this->mSeparators[$column_id] = '';
				} else {
					$this->mSeparators[$column_id] = "</td>\n";
				}
				if ( (SMW_IQ_PRINT_ATTS == $print_data[1]) && ($print_data[3]->isNumeric()) ) {
					$this->mSeparators[$column_id] .= "\t\t\t<td valign=\"top\" align=\"right\">";
				} else {
					$this->mSeparators[$column_id] .= "\t\t\t<td valign=\"top\">";
				}
			}
			if ($this->mShowHeaders) $this->mHeaderText .= "\t\t</tr>\n\t\t<tr>\n"; // start first row
			return;
		case 'ul': case 'ol': default:
			$this->mHeaderText = $this->mIntro;
			$this->mFooterText = '';
			if ( ('ul' == $this->mFormat) || ('ol' == $this->mFormat) ) {
				$this->mHeaderText .= "<$this->mFormat>\n\t\t<li>";
				$this->mFooterText = "</li>\n\t</$this->mFormat>\n";
				$this->mRowSep = "</li>\n\t\t<li>";
				$this->mLastRowSep = "</li>\n\t\t<li>";
			} elseif ( NULL == $this->mListSep ) {
				$this->mRowSep = ', ';
				$this->mLastRowSep = wfMsgForContent('smw_finallistconjunct') . ' ';
			} else {
				$this->mRowSep = $this->mListSep;
				$this->mLastRowSep = $this->mListSep;
			}
			$this->mValueSep = ', ';

			// determine separators
			$i = 0;
			$has_subject = array_key_exists('', $print); // is subject printed?
			foreach ($print as $column_id => $print_data) {
				if ( 0 == $i ) { // no separator at start
					$this->mSeparators[$column_id] = '';
				} elseif ( (1 == $i) && $has_subject ) { //enclose additional values in ( )
					$this->mSeparators[$column_id] = ' (';
					$this->mRowSep = ')' . $this->mRowSep; // add closing ) to the end of each row
					$this->mLastRowSep = ')' . $this->mLastRowSep;
					$this->mFooterText = ')' . $this->mFooterText;
				} else { //if ( $i < count($print)-1 ) { // standard separator within each row
					$this->mSeparators[$column_id] = ', ';
				}
				if ( $this->mShowHeaders && ('' != $print_data[0]) ) {
					$this->mSeparators[$column_id] .= $print_data[0] . ' ';
				}
				$i++;
			}
			return;
		}
	}

	/**
	 * Build and return the output string for one row.
	 */
	function makeRow(&$row, &$print) {
		global $wgContLang, $smwgIQSortingEnabled;

		$result = '';
		$firstcol = true;
		//Print values for all columns
		foreach ($print as $column_id => $print_data) {	
			$sql_params = array('LIMIT' => $this->mLimit);
			$result .= $this->mSeparators[$column_id];

			switch ($print_data[1]) {
				case SMW_IQ_PRINT_CATS:
					if ($smwgIQSortingEnabled)
						$sql_params['ORDER BY'] = "cl_to $this->mOrder";
					$res = $this->dbr->select( $this->dbr->tableName('categorylinks'),
					          'DISTINCT cl_to',
			 		          'cl_from=' . $row['page_id'],
					          'SMW::InlineQuery::Print' , $sql_params);
					if ( ($res) && ($this->dbr->numRows( $res ) > 0) ) {
						$first = true;
						while ( $subrow = $this->dbr->fetchRow($res) ) {
							if ($first) $first = false; else $result .= $this->mValueSep;
							$result .= $this->makeTitleString($wgContLang->getNsText(NS_CATEGORY) . ':' . $subrow['cl_to'],$firstcol);
						}
					}
					$this->dbr->freeResult($res);
					break;
				case SMW_IQ_PRINT_RELS:
					if ($smwgIQSortingEnabled)
						$sql_params['ORDER BY'] = "object_title $this->mOrder";
					$res = $this->dbr->select( $this->dbr->tableName('smw_relations'),
					          'DISTINCT object_title,object_namespace',
			 		          'subject_id=' . $row['page_id'] . ' AND relation_title=' . $this->dbr->addQuotes($print_data[2]),
					          'SMW::InlineQuery::Print' , $sql_params); 
					if ( ($res) && ($this->dbr->numRows( $res ) > 0) ) {
						$first = true;
						while ( $subrow = $this->dbr->fetchRow($res) ) {
							if ($first) $first = false; else $result .= $this->mValueSep;
							$result .= $this->makeTitleString($wgContLang->getNsText($subrow['object_namespace']) . ':' . $subrow['object_title'],$firstcol);
						}
					}
					$this->dbr->freeResult($res);
					break;
				case SMW_IQ_PRINT_ATTS:
					if ($smwgIQSortingEnabled) {
						if ($print_data[3]->isNumeric()) {
							$sql_params['ORDER BY'] = "value_num $this->mOrder";
						} else {
							$sql_params['ORDER BY'] = "value_xsd $this->mOrder";
						}
					}
					$res = $this->dbr->select( $this->dbr->tableName('smw_attributes'),
					          'DISTINCT value_unit,value_xsd',
			 		          'subject_id=' . $row['page_id'] . ' AND attribute_title=' . $this->dbr->addQuotes($print_data[2]),
					          'SMW::InlineQuery::Print' , $sql_params);
					if ( ($res) && ($this->dbr->numRows( $res ) > 0) ) {
						$first = true;
						while ( $subrow = $this->dbr->fetchRow($res) ) {
							if ($first) $first = false; else $result .= $this->mValueSep;
							$print_data[3]->setXSDValue($subrow['value_xsd'],$subrow['value_unit']);
							$result .= $print_data[3]->getStringValue(); //For debugging: . ' (' . $subrow['value_xsd'] . ') ';
							
						}
					}
					$this->dbr->freeResult($res);
					break;
				case SMW_IQ_PRINT_RSEL:
					$result .= $this->makeTitleString($wgContLang->getNsText($row[$print_data[2] . 'namespace']) . ':' . $row[$print_data[2] . 'title'],$firstcol);
					break;
				case SMW_IQ_PRINT_ASEL: // TODO: allow selection of attribute conditionals, and print them here
					$result .= '---';
					break;
			}
		}
		return $result;
	}

	/**
	 * Create output string for an article title (possibly including namespace)
	 * as given by $text. The main task of this method is to link the article
	 * depending on the query parameters.
	 *
	 * $subject states whether the given title is the subject (to which special
	 * settings for linking apply).
	 */
	function makeTitleString($text,$subject) {
		$title = Title::newFromText( $text );
		if ($title == NULL) {
			return $text; // TODO maybe report an error here?
		} elseif ( ($this->mLinkObj) || (($this->mLinkSubj) && ($subject)) ) {
			// TODO links should be created by the skin-object, not manually
			if ($title->exists()) {
				$classnew = '';
			} else {
				$classnew = 'class="new" ';
			}
			return '<a href="'. $title->getLocalURL() .'" '. $classnew .'title="'. $title->getText() .'">'. $title->getText() .'</a>';
		} else {
			return $title->getText();
		}
	}

}

?>
<?php
/**
 * This feature implements inline queries, i.e. it offers the possibility
 * to query against MediaWiki's own semantic knowledgebase by putting questions
 * inside articles. In the rendered article, those questions are replaced with 
 * the respective answers. Moreover, queries might also be executed in special
 * pages, thus displaying results directly without need of editing an article.
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
require_once( "$IP/includes/Linker.php" );
require_once('SMW_QueryPrinters.php');

/* The variables below define the default settings. Changes can be made by
   setting new values in SMW_LocalSettings.php */

/* Configure default behaviour of inline queries */
	// Default linking behaviour. Can be one of "none", "subject", "all"
	$smwgIQDefaultLinking = 'subject';
	// Which namespaces should be searched by default?
	$smwgIQSearchNamespaces = NULL; //can be defined as an array of NS_IDs in LocalSettings
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
	// Maximum number of rows returned in any query.
	$smwgIQMaxLimit = 10000; //practically no limit
	// Maximum number of rows printed in an inline query.
	$smwgIQMaxInlineLimit = 500;
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
	return true; // always return true, in order not to stop MW's hook processing!
}

/**
 * Everything that is between <ask> and </ask> gets processed here
 * $text is the query in the query format described elsewhere
 * $param is an array which might contain values for various parameters
 * (see SMWInlineQuery).
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


/**
 * The main class for processing semantic queries. Its main entry point is 
 * getHTMLResult, which transforms a textual query into a HTML-formatted output
 * text of results. The formatting is controlled through the parameters with which
 * the query object was initialised. It is an array of option => value pairs, 
 * which may contain the following optional keys:
 * limit   -- Maximal number of answers. It will be capped by $smwgIQMaxInlineLimit anyway.
 *            Defaults to $smwgIQDefaultLimit.
 * offset  -- Number of first result to be displayed. Parameter not available for inline queries.
 * link    -- Either none, subject or all. Makes links out of the results.
 *            Defaults to $smwgIQDefaultLinking.
 * default -- If no result is found, this string will be returned.
 * intro   -- Plain text that is to be displayed before the query, but only if any results are obtained.
 * searchlabel -- text to use for link to search forfurther results, or empty if this link should not be shown
 * sort    -- Name of the row by which to sort.
 * order   -- Either 'ascending' (equal to 'asc', default) or 'descending' (equal to 'desc' or 'reverse')
 * headers -- How to display the header properties of columns, can be one of 'show' 
 *            (default) and 'hide'. Maybe 'link' will follow in the future.
 * mainlabel -- Label to use for the column that shows the main subjects. Also used to indicate that
 *              the subject should be displayed in cases where it would normally be hidden.
 * format  -- Either 'list', 'ul' (for unordered bullet list), 'ol' (ordered and numbered list), 
 *            'table', 'broadtable', 'timeline', 'eventline', 'embedded', or 'auto' (default).
 * Some formats have additional parameters:
 *   sep (list only) -- Customized separator string.
 */
class SMWInlineQuery {

	/**
	 * Array of enabled formats for inline queries. Can be redefined in the settings to disallow certain
	 * formats. The formats 'table' and 'list' are defaults that cannot be disabled. The format 'broadtable'
	 * should not be disabled either in order not to break Special:ask.
	 */
	static $formats = array('table','list','ol','ul','broadtable','embedded','timeline','eventline');

	private $mInline; // is this really an inline query, i.e. are results used in an article or not? (bool)

	// parameters:
	private $mParameters; // full parameter list for later reference
	private $mLimit; // max number of answers, also used when printing values of one particular subject
	private $mOffset; // number of first returned answer
	private $mSort;  // supplied name of the row by which to sort
	private $mSortkey;  // db key version of the row name by which to sort
	private $mOrder; // string that identifies sort order, 'ASC' (default) or 'DESC'
	private $mFormat;  // a string identifier describing a valid format
	private $mIntro; // text to print before the output in case it is *not* empty
	private $mSearchlabel; // text to use for link to further results, or empty if link should not be shown
	private $mLinkSubj; // should article names of the (unique) subjects be linked?
	private $mLinkObj; // should article names of the objects be linked?
	private $mDefault; // default return value for empty queries
	private $mShowHeaders; // should the headers (property names) be printed?
	private $mMainLabel; // label used for displaying the subject, or NULL if none was given
	private $mShowDebug; // should debug output be generated?

	// fields used during query processing:
	private $mQueryText; // the original query text for future reference
	private $dbr; // pointer to the database used throughout exectution of the query
	private $mRename; // integer counter to rename tables in SQL joins
	private $mSubQueries; // array of subqueries, indexed by placeholder indices
	private $mSQCount; // integer counter to replace subqueries
	private $mConditionCount; // count the number of conditions used so far
	private $mTableCount; // count the number of tables joined so far
	private $mPrintoutCount; // count the number of fields selected for separate printout so far
	private $mFurtherResults=false; // true if not all results to the query were shown
	private $mDisplayCount=0; // number of results that are displayed
	private $mCurDisplayCount=0; // number of results that were already displayed, used for the iteration
	private $mQueryResult; // retrieved query result

	// other stuff
	private $mLinker; // we make our own linker for creating the links -- TODO: is this bad?

	public function SMWInlineQuery($param = array(), $inline = true) {
		global $smwgIQDefaultLimit, $smwgIQDefaultLinking;

		$this->mInline = $inline;

		$this->mLimit = $smwgIQDefaultLimit;
		$this->mOffset = 0;
		$this->mSort = NULL;
		$this->mSortkey = NULL;
		$this->mOrder = 'ASC';
		$this->mFormat = 'auto';
		$this->mIntro = '';
		$this->mSearchlabel = NULL; //indicates that printer default should be used
		$this->mLinkSubj = ($smwgIQDefaultLinking != 'none');
		$this->mLinkObj = ($smwgIQDefaultLinking == 'all');
		$this->mDefault = '';
		$this->mShowHeaders = true;
		$this->mMainLabel = NULL;
		$this->mShowDebug = false;

		$this->mLinker = new Linker();

		$this->setParameters($param);
	}

	/**
	 * Set the internal settings according to an array of parameter values.
	 */
	private function setParameters($param) {
		global $smwgIQMaxLimit, $smwgIQMaxInlineLimit;
		$this->mParameters = $param;
		
		if ($this->mInline) 
			$maxlimit = $smwgIQMaxInlineLimit;
		else $maxlimit = $smwgIQMaxLimit;

		if ( !$this->mInline && (array_key_exists('offset',$param)) && (is_int($param['offset'] + 0)) ) {
			$this->mOffset = min($maxlimit - 1, max(0,$param['offset'] + 0)); //select integer between 0 and maximal limit -1
		}
		// set limit small enough to stay in range with chosen offset
		// it makes sense to have limit=0 in order to only show the link to the search special
		if ( (array_key_exists('limit',$param)) && (is_int($param['limit'] + 0)) ) {
			$this->mLimit = min($maxlimit - $this->mOffset, max(0,$param['limit'] + 0));
		}
		if (array_key_exists('sort', $param)) {
			$this->mSort = $param['sort'];
			$this->mSortkey = smwfNormalTitleDBKey($param['sort']);
		}
		if (array_key_exists('order', $param)) {
			if (('descending'==strtolower($param['order']))||('reverse'==strtolower($param['order']))||('desc'==strtolower($param['order']))) {
				$this->mOrder = "DESC";
			}
		}
		if (array_key_exists('format', $param)) {
			$this->mFormat = strtolower($param['format']);
			if ( !in_array($this->mFormat,SMWInlineQuery::$formats) ) 
				$this->mFormat = 'auto'; // If it is an unknown format, default to list again
		}
		if (array_key_exists('intro', $param)) {
			$this->mIntro = htmlspecialchars(str_replace('_', ' ', $param['intro']));
		}
		if (array_key_exists('searchlabel', $param)) {
			$this->mSearchlabel = htmlspecialchars($param['searchlabel']);
		}
		if (array_key_exists('link', $param)) {
			switch (strtolower($param['link'])) {
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
			$this->mDefault = htmlspecialchars(str_replace('_', ' ', $param['default']));
		}
		if (array_key_exists('headers', $param)) {
			if ( 'hide' == strtolower($param['headers'])) {
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
	 * Returns true if a query was executed and the chosen limit did not
	 * allow all results to be displayed
	 */
	public function hasFurtherResults() {
		return $this->mFurtherResults;
	}

	/**
	 * After a query was executed, this function returns the number of results that have been
	 * displayed (which is different from the overall number of results that exist).
	 */
	public function getDisplayCount() {
		return $this->mDisplayCount;
	}

	/**
	 * Returns all parameters in their original array form. Useful for printer objects that
	 * want to introduce their own parameters.
	 */
	public function getParameters() {
		return $this->mParameters;
	}

	/**
	 * Return the name of the format that is to be used for printing the query.
	 */
	public function getFormat() {
		return $this->mFormat;
	}
	
	/**
	 * Return the introduction string for printing the query.
	 */
	public function getIntro() {
		return $this->mIntro;
	}

	/**
	 * Return the default string for printing of the query returns no results.
	 */
	public function getDefault() {
		return $this->mDefault;
	}

	/**
	 * Return the label that should be used for the link to the search for
	 * more results. If '' (empty string) is returned, the link should not be
	 * shown. If NULL is returned, the default of the printer should apply.
	 */
	public function getSearchlabel() {
		return $this->mSearchlabel;
	}	

	/**
	 * Returns a boolean stating whether or not headers should be displayed
	 */
	public function showHeaders() {
		return $this->mShowHeaders;
	}

	/**
	 * Returns a boolean stating whether the query was executed inline.
	 */
	public function isInline() {
		return $this->mInline;
	}

	/**
	 * Returns the URL of the ask special page, with parameters corresponding to the
	 * current query.
	 */
	public function getQueryURL() {
		$title = Title::makeTitle(NS_SPECIAL, 'ask');
		return $title->getLocalURL('query=' . urlencode($this->mQueryText) . '&sort=' . urlencode($this->mSort) . '&order=' . urlencode($this->mOrder));
	}

	/**
	 * During execution of a query, this method is used to fetch result rows one by
	 * one. FALSE will be returned if no more rows that should be printed remain.
	 */
	public function getNextRow() {
		$this->mCurDisplayCount++;
		$row = $this->dbr->fetchRow( $this->mQueryResult );
		if ( (!$row) || ($this->mCurDisplayCount > $this->mLimit) ) {
			if ($row) $this->mFurtherResults = true; // there are more result than displayed
			return false;
		} else return $row;
	}

	/**
	 * During execution of a query, this method is used create an appropriate iterator
	 * object that encapsulates the values that are to be printed in a certain row and
	 * column.
	 */
	public function getIterator($print_data, $row, $subject) {
		global $smwgIQSortingEnabled;
		$sql_params = array('LIMIT' => $this->mLimit);
		$linked = ( ($this->mLinkObj) || (($this->mLinkSubj) && ($subject)) );

		switch ($print_data[1]) {
			case SMW_IQ_PRINT_CATS:
				if ($smwgIQSortingEnabled)
					$sql_params['ORDER BY'] = "cl_to $this->mOrder";
				$res = $this->dbr->select( $this->dbr->tableName('categorylinks'),
							'DISTINCT cl_to',
							'cl_from=' . $row['page_id'],
							'SMW::InlineQuery::Print' , $sql_params);
				return new SMWCategoryIterator($this,$this->dbr,$res,$linked);
			case SMW_IQ_PRINT_RELS:
				if ($smwgIQSortingEnabled)
					$sql_params['ORDER BY'] = "object_title $this->mOrder";
				$res = $this->dbr->select( $this->dbr->tableName('smw_relations'),
							'DISTINCT object_title,object_namespace',
							'subject_id=' . $row['page_id'] . ' AND relation_title=' . $this->dbr->addQuotes($print_data[2]),
							'SMW::InlineQuery::Print' , $sql_params); 
				return new SMWRelationIterator($this,$this->dbr,$res,$linked);
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
				return new SMWAttributeIterator($this->dbr, $res, $print_data[3]);
			case SMW_IQ_PRINT_RSEL:
				global $wgContLang;
				return new SMWSingleArticleIterator($this, $wgContLang->getNsText($row[$print_data[2] . 'namespace']), $row[$print_data[2] . 'title'],$linked,$subject);
			case SMW_IQ_PRINT_ASEL: // TODO: allow selection of attribute conditionals, and print them here
				return new SMWFixedIterator('---');
		}
	}

	/**
	 * Main entry point for parsing, executing, and printing a given query text.
	 */
	public function getHTMLResult( $text ) {
		global $smwgIQSortingEnabled, $smwgIQRunningNumber,$wgTitle;
		$this->mQueryText = $text;

		if (!isset($smwgIQRunningNumber)) {
			$smwgIQRunningNumber = 0;
		} else { $smwgIQRunningNumber++; }

		// This should be the proper way of substituting templates in a safe and comprehensive way	
		$parser = new Parser();
		$parserOptions = new ParserOptions();
		//$parserOptions->setInterfaceMessage( true );
		$parser->startExternalParse( $wgTitle, $parserOptions, OT_MSG );
		$text = $parser->transformMsg( $text, $parserOptions );

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

		$sql_options = array('LIMIT' => $this->mLimit + 1, 'OFFSET' => $this->mOffset); // additional options (order by, limit)
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
		$this->mQueryResult = $this->dbr->select( 
		         $sq->mTables,
		         'DISTINCT ' . implode(',', $sq->mSelect),
		         $sq->mConditions,
		         "SMW::InlineQuery" ,
		         $sql_options );

		//*** Create the output ***//

		//No results, TODO: is there a better way than calling numRows (which counts all results)?
		if ( (!$this->mQueryResult) || (0 == $this->dbr->numRows( $this->mQueryResult )) ) return $this->mDefault;

		$this->mDisplayCount = min($this->mLimit, $this->dbr->numRows( $this->mQueryResult ));

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
			if ( (count($sq->mPrint)>1) && ($this->mLimit > 0) )
				$this->mFormat = 'table';
			else $this->mFormat = 'list';
		}

		switch ($this->mFormat) {
			case 'table': case 'broadtable':
				$printer = new SMWTablePrinter($this,$sq);
				break;
			case 'ul': case 'ol': case 'list':
				$printer = new SMWListPrinter($this,$sq);
				break;
			case 'timeline': case 'eventline':
				$printer = new SMWTimelinePrinter($this,$sq);
				break;
			case 'embedded':
				$printer = new SMWEmbeddedPrinter($this,$sq);
				break;
			default: $printer = new SMWListPrinter($this,$sq);
		}
		$result = $printer->printResult();
		$this->dbr->freeResult($this->mQueryResult); // Things that should be free: #42 "Possibly large query results"

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
	 * The result is an object of type SMWSQLQuery.
	 */
	private function parseQuery($querytext) {
		global $wgContLang, $smwgIQDisjunctiveQueriesEnabled, $smwgIQSubcategoryInclusions, $smwgIQMaxConditions, $smwgIQMaxTables, $smwgIQMaxPrintout;

		// Extract subqueries:
		$querytext = preg_replace_callback("/<q>(.*)<\/q>/",
		             array(&$this,'extractSubQuery'),$querytext);
		// Extract queryparts: 
		$queryparts = preg_split("/[\s\n]*\[\[|\]\][\s\n]*\[\[|\]\][\s\n]*/",$querytext,-1,PREG_SPLIT_NO_EMPTY);

		$result = new SMWSQLQuery();
		$cat_sep = $wgContLang->getNsText(NS_CATEGORY) . ":";
		$has_namespace_conditions = false; //flag for deciding on including default namespace restrictions

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
						$label = ucfirst($qparts[0]);
						if ( '' === $label) $label = NULL;
					}
					if ($cat_sep == $op) { // eventually print all categories for the selected subjects
						if (NULL === $label) $label = $wgContLang->getNSText(NS_CATEGORY);
						$result->mPrint['C'] = array($label,SMW_IQ_PRINT_CATS);
					} elseif ( '::' == $op ) {
						$result->mPrint['R:' . $qparts[0]] = array($this->makeTitleString($wgContLang->getNsText(SMW_NS_RELATION) . ':' . $qparts[0],$label,true),
						SMW_IQ_PRINT_RELS,smwfNormalTitleDBKey($qparts[0]));
					} elseif ( ':=' == $op ) {
						$av = SMWDataValue::newAttributeValue($qparts[0]);
						$unit = mb_substr($qparts[2],1);
						if ($unit != '') { // desired unit selected:
							$av->setDesiredUnits(array($unit));
						}
						$result->mPrint['A:' . $qparts[0]] = array($this->makeTitleString($wgContLang->getNsText(SMW_NS_ATTRIBUTE) . ':' . $qparts[0],$label,true),SMW_IQ_PRINT_ATTS,smwfNormalTitleDBKey($qparts[0]),$av);
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
							$values = array(); // ignore sub-query and make a wildcard search
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
						$vtitles = array();
						foreach ($values as $v) {
							$vtitle = Title::newFromText($v);
							if (NULL != $vtitle) { 
								$id = $vtitle->getArticleID(); // create index for title
								if (0 == $id) $id = $vtitle->getPrefixedText();
								$vtitles[$vtitle->getArticleID()] = $vtitle; //convert values to titles
							}
						}
						$vtitles = $this->normalizeRedirects($vtitles);
						
						// search for values
						foreach ($vtitles as $vtitle) {
							//if (NULL != $vtitle) {
								$or_conditions[] = "$curtable.object_title=" . $this->dbr->addQuotes($vtitle->getDBKey()) . " AND $curtable.object_namespace=" . $vtitle->getNamespace();
							//}
						}
					}
					if ($relation == $this->mSortkey) {
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
							if ((mb_strlen($v)>2) && (':' == mb_substr($v,0,1))) $v = mb_substr($v,1); // remove initial ':'
							// TODO: should this be done when normalizing the title???
							$ns_idx = $wgContLang->getNsIndex(mb_substr($v,0,-2)); // assume format "Namespace:+"
							if ((false === $ns_idx)||(mb_substr($v,-2,2) !== ':+')) {
								$vtitle = Title::newFromText($v);
								if (NULL != $vtitle) {
									$or_conditions[] = "$pagetable.page_title=" . $this->dbr->addQuotes($vtitle->getDBKey()) . " AND $pagetable.page_namespace=" . $vtitle->getNamespace();
									$result->mFixedSubject = true; // by default, only this case is a really "fixed" subject (even though it could still be combined with others); TODO: find a better way for deciding whether to show the first column or not
									$has_namespace_conditions = true; // fixed subjects might have namespaces, so we must discard any overall namespace restrictions to retrieve results
								}
							} else {
								$or_conditions[] = "$pagetable.page_namespace=$ns_idx";
								$has_namespace_conditions = true;
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
					if ($attribute == $this->mSortkey) {
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

		if (!$has_namespace_conditions) { // restrict namespaces to default setting
			global $smwgIQSearchNamespaces;
			if ($smwgIQSearchNamespaces !== NULL) {
				$condition = '';
				foreach ($smwgIQSearchNamespaces as $nsid) {
					if ($condition == '') {
						$condition .= '((';
					} else {
						$condition .= ') OR (';
					}
					$condition .= "$pagetable.page_namespace=$nsid";
					$this->mConditionCount++; // we do not check whether this exceeds the max, since it is somehow crucial and controlled by the site admins anyway
				}
				if ($condition != '') $condition .= '))';
				if ('' != $result->mConditions) $result->mConditions .= ' AND ';
				$result->mConditions .= $condition;
			}
		}

		$result->mDebug = "\n SELECT " . implode(',',$result->mSelect) . "\n FROM $result->mTables\n WHERE $result->mConditions" . " \n Conds:$this->mConditionCount Tables:$this->mTableCount Printout:$this->mPrintoutCount"; //DEBUG

		return $result;
	}

	/**
	 * Turns an array of article titles into an array of all these articles and
	 * the transitive closure of all redirects from and to this articles.
	 * Or, simply said: it gets all aliases of what you put in.
	 *
	 * FIXME: store intermediate result in a temporary DB table on the heap; much faster!
	 * FIXME: include an option to ignore multiple redirects, or, even better, make a 
	 * plugable SQL-query to compute one-step back-and-forth redirects without any 
	 * materialisation.
	 */
	private function normalizeRedirects(&$titles) {
		global $smwgIQRedirectNormalization;
		if (!$smwgIQRedirectNormalization) {
			return $titles;
		}

		$stable = 0;
		$check_titles = array_diff( $titles , array() ); // Copies the array
		while ($stable<30) { // emergency stop after 30 iterations
			$stable++;
			$new_titles = array();
			foreach ( $check_titles as $title ) {
				// there...
				if ( 0 != $title->getArticleID() ) {
					$res = $this->dbr->select(
						array( 'page' , 'pagelinks' ),
						array( 'pl_title', 'pl_namespace'),
						array( 'page_id = ' . $title->getArticleID(),
							'page_is_redirect = 1',
							'page_id = pl_from' ) ,
							'SMW::InlineQuery::NormalizeRedirects', array('LIMIT' => '1') );
					while ( $res && $row = $this->dbr->fetchRow( $res )) {
						$new_title = Title::newFromText($row['pl_title'], $row['pl_namespace']);
						if (NULL != $new_title) {
							$id = $new_title->getArticleID();
							if (0 == $id) $id = $new_title->getPrefixedText();
							if (!array_key_exists( $id , $titles)) {
								$titles[$id] = $new_title;
								$new_titles[] = $new_title;
							}
						}
					}
					$this->dbr->freeResult( $res );
				}

				// ... and back again
				$res = $this->dbr->select(
					array( 'page' , 'pagelinks' ),
					array( 'page_id' ),
					array( 'pl_title = ' . $this->dbr->addQuotes( $title->getDBkey() ),
					       'pl_namespace = ' . $this->dbr->addQuotes( $title->getNamespace() ), 
					       'page_is_redirect = 1',
					       'page_id = pl_from' ) ,
					       'SMW::InlineQuery::NormalizeRedirects', array('LIMIT' => '1'));
				while ( $res && $row = $this->dbr->fetchRow( $res )) {
					$new_title = Title::newFromID( $row['page_id'] );
					if (!array_key_exists( $row['page_id'] , $titles)) {
						$titles[$row['page_id']] = $new_title;
						$new_titles[] = $new_title;
					}
				}
				$this->dbr->freeResult( $res );
			}
			if (count($new_titles)==0)
				$stable= 500; // stop
			else
				$check_titles = array_diff( $new_titles , array() );
		}
		return $titles;
	}
	
	/**
	 * Turns an array of categories to an array of categories and its subcategories.
	 * The number of relations followed is given by $levels.
	 *
	 * FIXME: store intermediate result in a temporary DB table on the heap; much faster!
	 */
	private function includeSubcategories( &$categories, $levels ) {
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

	/**
	 * Create output string for an article title (possibly including namespace)
	 * as given by $text. 
	 *
	 * $subject states whether the given title is the subject (to which special
	 * settings for linking apply).
	 * If $label is null the standard label of the given article will be used.
	 * If $label is the empty string, an empty string is returned.
	 * $linked states whether the result should be a hyperlink
	 * $exists states whether $text is known to be anexisting article, in which 
	 *     case we can save a DB lookup when creating links.
	 */
	public function makeTitleString($text,$label,$linked,$exists=false) {
		if ( '' === $label) return ''; // no link desired
		$title = Title::newFromText( $text );
		if ($title === NULL) {
			return $text; // TODO maybe report an error here?
		} elseif ( $linked ) {
			if ( NULL === $label ) $label = $title->getText();
			if ($exists)
				return $this->mLinker->makeKnownLinkObj($title, $label);
			else return $this->mLinker->makeLinkObj($title, $label);
		} else {
			return $title->getText(); // TODO: shouldn't this default to $label?
		}
	}

}

/*********************************************************************/
/* Iterators for hiding SQL details from printers                    */
/*********************************************************************/

/**
 * "Empty" iterator. Used as return value in case of errors.
 */
class SMWEmptyIterator {
	public function getNext() {
		return false;
	}
}

/**
 * Iterator for category article titles returned as SQL result.
 */
class SMWCategoryIterator {
	private $mRes;
	private $mDB;
	private $mIQ;
	private $mLinked;

	public function SMWCategoryIterator($iq, $db, $res, $linked) {
		$this->mIQ = $iq;
		$this->mDB = $db;
		$this->mRes = $res;
		$this->mLinked = $linked;
	}

	public function getNext() {
		global $wgContLang;
		$row = $this->mDB->fetchRow($this->mRes);
		if ($row) 
			return array($this->mIQ->makeTitleString($wgContLang->getNsText(NS_CATEGORY) . ':' . $row['cl_to'],NULL,$this->mLinked));
		else return false;
	}
}

/**
 * Iterator for attribute values, returned by some SQL query.
 */
class SMWAttributeIterator {
	private $mRes;
	private $mDB;
	private $mDatavalue;

	public function SMWAttributeIterator($db, $res, $datavalue) {
		$this->mDB = $db;
		$this->mRes = $res;
		$this->mDatavalue = $datavalue;
	}

	public function getNext() {
		$row = $this->mDB->fetchRow($this->mRes);
		if ($row) {
			$this->mDatavalue->setXSDValue($row['value_xsd'],$row['value_unit']);
			return array($this->mDatavalue->getStringValue(), $this->mDatavalue);
		} else return false;
	}
}

/**
 * Iterator for relation objects, returned by some SQL query.
 */
class SMWRelationIterator {
	private $mRes;
	private $mDB;
	private $mIQ;
	private $mLinked;

	public function SMWRelationIterator($iq, $db, $res, $linked) {
		$this->mIQ = $iq;
		$this->mDB = $db;
		$this->mRes = $res;
		$this->mLinked = $linked;
	}

	public function getNext() {
		global $wgContLang;
		$row = $this->mDB->fetchRow($this->mRes);
		if ($row) {
			$namespace = $wgContLang->getNsText($row['object_namespace']);
			return array($this->mIQ->makeTitleString($namespace . ':' . $row['object_title'],NULL,$this->mLinked), $namespace, $row['object_title']);
		} else return false;
	}
}

/**
 * Iterator wrapping a single article
 */
class SMWSingleArticleIterator {
	private $mTitle;
	private $mNamespace;
	private $mHasNext=true;
	private $mIQ;
	private $mLinked;
	private $mSubject;

	public function SMWSingleArticleIterator($iq, $namespace, $title, $linked, $subject=false) {
		$this->mIQ = $iq;
		$this->mTitle = $title;
		$this->mNamespace = $namespace;
		$this->mLinked = $linked;
		$this->mSubject = $subject;
	}

	public function getNext() {
		if ($this->mHasNext) {
			$this->mHasNext = false;
			return array($this->mIQ->makeTitleString($this->mNamespace . ':' . $this->mTitle,NULL,$this->mLinked,$this->mSubject),$this->mNamespace,$this->mTitle);
		} else return false;
	}
}

?>
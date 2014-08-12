<?php
/**
 * Constants relevant to Semantic MediaWiki
 *
 */

/**
 * @ingroup Constants
 * @ingroup SMW
 */

/**@{
 * SMW\ResultPrinter related constants that define
 * how/if headers should be displayed
 */
define( 'SMW_HEADERS_SHOW', 2 );
define( 'SMW_HEADERS_PLAIN', 1 );
define( 'SMW_HEADERS_HIDE', 0 ); // Used to be "false" hence use "0" to support extensions that still assume this.
/**@}*/

/**@{
 * Constants for denoting output modes in many functions: HTML or Wiki?
 * "File" is for printing results into stand-alone files (e.g. building RSS)
 * and should be treated like HTML when building single strings. Only query
 * printers tend to have special handling for that.
 */
define( 'SMW_OUTPUT_HTML', 1 );
define( 'SMW_OUTPUT_WIKI', 2 );
define( 'SMW_OUTPUT_FILE', 3 );
/**@}*/

/**@{
 * Constants for displaying the factbox
 */
define( 'SMW_FACTBOX_HIDDEN', 1 );
define( 'SMW_FACTBOX_SPECIAL', 2 );
define( 'SMW_FACTBOX_NONEMPTY',  3 );
define( 'SMW_FACTBOX_SHOWN',  5 );
/**@}*/

/**@{
 * Constants for regulating equality reasoning
 */
define( 'SMW_EQ_NONE', 0 );
define( 'SMW_EQ_SOME', 1 );
define( 'SMW_EQ_FULL', 2 );
/**@}*/

/**@{
 * Flags to classify available query descriptions,
 * used to enable/disable certain features
 */
define( 'SMW_PROPERTY_QUERY', 1 );     // [[some property::...]]
define( 'SMW_CATEGORY_QUERY', 2 );     // [[Category:...]]
define( 'SMW_CONCEPT_QUERY', 4 );      // [[Concept:...]]
define( 'SMW_NAMESPACE_QUERY', 8 );    // [[User:+]] etc.
define( 'SMW_CONJUNCTION_QUERY', 16 ); // any conjunctions
define( 'SMW_DISJUNCTION_QUERY', 32 ); // any disjunctions (OR, ||)
define( 'SMW_ANY_QUERY', 0xFFFFFFFF );  // subsumes all other options
/**@}*/

/**@{
 * Constants for defining which concepts to show only if cached
 */
define( 'CONCEPT_CACHE_ALL', 4 ); // show concept elements anywhere only if cached
define( 'CONCEPT_CACHE_HARD', 1 ); // show without cache if concept is not harder than permitted inline queries
define( 'CONCEPT_CACHE_NONE', 0 ); // show all concepts even without any cache
/**@}*/

/**@{
 * Constants for identifying javascripts as used in SMWOutputs
 */
/// @deprecated Use module 'ext.smw.tooltips', see SMW_Ouptuts.php. Vanishes in SMW 1.7 at the latest.
define( 'SMW_HEADER_TOOLTIP', 2 );
/// @deprecated Module removed. Vanishes in SMW 1.7 at the latest.
define( 'SMW_HEADER_SORTTABLE', 3 );
/// @deprecated Use module 'ext.smw.style', see SMW_Ouptuts.php. Vanishes in SMW 1.7 at the latest.
define( 'SMW_HEADER_STYLE', 4 );
/**@}*/

/**@{
 *  Comparators for datavalues
 */
define( 'SMW_CMP_EQ', 1 ); // Matches only datavalues that are equal to the given value.
define( 'SMW_CMP_LEQ', 2 ); // Matches only datavalues that are less or equal than the given value.
define( 'SMW_CMP_GEQ', 3 ); // Matches only datavalues that are greater or equal to the given value.
define( 'SMW_CMP_NEQ', 4 ); // Matches only datavalues that are unequal to the given value.
define( 'SMW_CMP_LIKE', 5 ); // Matches only datavalues that are LIKE the given value.
define( 'SMW_CMP_NLKE', 6 ); // Matches only datavalues that are not LIKE the given value.
define( 'SMW_CMP_LESS', 7 ); // Matches only datavalues that are less than the given value.
define( 'SMW_CMP_GRTR', 8 ); // Matches only datavalues that are greater than the given value.
/**@}*/

/**@{
 * Constants for date formats (using binary encoding of nine bits:
 * 3 positions x 3 interpretations)
 */
define( 'SMW_MDY', 785 );  // Month-Day-Year
define( 'SMW_DMY', 673 );  // Day-Month-Year
define( 'SMW_YMD', 610 );  // Year-Month-Day
define( 'SMW_YDM', 596 );  // Year-Day-Month
define( 'SMW_MY', 97 );    // Month-Year
define( 'SMW_YM', 76 );    // Year-Month
define( 'SMW_Y', 9 );      // Year
define( 'SMW_YEAR', 1 );   // an entered digit can be a year
define( 'SMW_DAY', 2 );   // an entered digit can be a year
define( 'SMW_MONTH', 4 );  // an entered digit can be a month
define( 'SMW_DAY_MONTH_YEAR', 7 ); // an entered digit can be a day, month or year
define( 'SMW_DAY_YEAR', 3 ); // an entered digit can be either a month or a year
/**@}*/
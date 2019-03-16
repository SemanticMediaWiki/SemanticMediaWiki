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
define( 'SMW_OUTPUT_RAW', 4 );
/**@}*/

/**@{
 * Constants for displaying the factbox
 */
define( 'SMW_FACTBOX_HIDDEN', 1 );
define( 'SMW_FACTBOX_SPECIAL', 2 );
define( 'SMW_FACTBOX_NONEMPTY', 3 );
define( 'SMW_FACTBOX_SHOWN', 5 );

define( 'SMW_FACTBOX_CACHE', 16 );
define( 'SMW_FACTBOX_PURGE_REFRESH', 32 );
define( 'SMW_FACTBOX_DISPLAY_SUBOBJECT', 64 );
define( 'SMW_FACTBOX_DISPLAY_ATTACHMENT', 128 );

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
define( 'SMW_CMP_PRIM_LIKE', 20 ); // Native LIKE matches (in disregards of an existing full-text index)
define( 'SMW_CMP_PRIM_NLKE', 21 ); // Native NLIKE matches (in disregards of an existing full-text index)
define( 'SMW_CMP_IN', 22 ); // Short-cut for ~* ... *
define( 'SMW_CMP_PHRASE', 23 ); // Short-cut for a phrase match ~" ... " mostly for a full-text context
define( 'SMW_CMP_NOT', 24 ); // Short-cut for ~! ... * ostly for a full-text context
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

/**@{
 * Constants for date/time precision
 */
define( 'SMW_PREC_Y', 0 );
define( 'SMW_PREC_YM', 1 );
define( 'SMW_PREC_YMD', 2 );
define( 'SMW_PREC_YMDT', 3 );
define( 'SMW_PREC_YMDTZ', 4 ); // with time zone
/**@}*/

/**@{
 * Constants for SPARQL supported features (mostly SPARQL 1.1) because we are unable
 * to verify against the REST API whether a feature is supported or not
 */
define( 'SMW_SPARQL_QF_NONE', 0 ); // does not support any features
define( 'SMW_SPARQL_QF_REDI', 2 ); // support for inverse property paths to find redirects
define( 'SMW_SPARQL_QF_SUBP', 4 ); // support for rdfs:subPropertyOf*
define( 'SMW_SPARQL_QF_SUBC', 8 ); // support for rdfs:subClassOf*
define( 'SMW_SPARQL_QF_COLLATION', 16 ); // support for use of $smwgEntityCollation
define( 'SMW_SPARQL_QF_NOCASE', 32 ); // support case insensitive pattern matches
/**@}*/

/**@{
  * Deprecated since 3.0, remove options after complete removal in 3.1
  */
define( 'SMW_HTTP_DEFERRED_ASYNC', true );
define( 'SMW_HTTP_DEFERRED_SYNC_JOB', 4 );
define( 'SMW_HTTP_DEFERRED_LAZY_JOB', 8 );
/**@}*/

/**@{
  * Constants DV features
  */
define( 'SMW_DV_NONE', 0 );
define( 'SMW_DV_PROV_REDI', 2 );  // PropertyValue to follow a property redirect target
define( 'SMW_DV_MLTV_LCODE', 4 );  // MonolingualTextValue requires language code
define( 'SMW_DV_NUMV_USPACE', 8 );  // Preserve spaces in unit labels
define( 'SMW_DV_PVAP', 16 );  // Allows pattern
define( 'SMW_DV_WPV_DTITLE', 32 );  // WikiPageValue to use an explicit display title
define( 'SMW_DV_PROV_DTITLE', 64 );  // PropertyValue allow to find a property using the display title
define( 'SMW_DV_PVUC', 128 );  // Declares a uniqueness constraint
define( 'SMW_DV_TIMEV_CM', 256 );  // TimeValue to indicate calendar model
define( 'SMW_DV_PPLB', 512 );  // Preferred property label
define( 'SMW_DV_PROV_LHNT', 1024 );  // PropertyValue to output a hint in case of a preferred label usage
/**@}*/

/**@{
  * Constants for Fulltext types
  */
define( 'SMW_FT_NONE', 0 );
define( 'SMW_FT_BLOB', 2 ); // DataItem::TYPE_BLOB
define( 'SMW_FT_URI', 4 ); // DataItem::TYPE_URI
define( 'SMW_FT_WIKIPAGE', 8 ); // DataItem::TYPE_WIKIPAGE
/**@}*/

/**@{
  * Constants for admin features
  */
define( 'SMW_ADM_NONE', 0 );
define( 'SMW_ADM_REFRESH', 2 ); // RefreshStore
define( 'SMW_ADM_DISPOSAL', 4 ); // IDDisposal
define( 'SMW_ADM_SETUP', 8 ); // SetupStore
define( 'SMW_ADM_PSTATS', 16 ); // Property statistics update
define( 'SMW_ADM_FULLT', 32 ); // Fulltext update
/**@}*/

/**@{
  * Constants for ResultPrinter
  */
define( 'SMW_RF_NONE', 0 );
define( 'SMW_RF_TEMPLATE_OUTSEP', 2 ); // #2022 Enable 2.5 behaviour for template handling
/**@}*/

/**@{
  * Constants for $smwgExperimentalFeatures
  */
/**@}*/

/**@{
  * Constants for $smwgFieldTypeFeatures
  */
define( 'SMW_FIELDT_NONE', 0 );
define( 'SMW_FIELDT_CHAR_NOCASE', 2 ); // Using FieldType::TYPE_CHAR_NOCASE
define( 'SMW_FIELDT_CHAR_LONG', 4 ); // Using FieldType::TYPE_CHAR_LONG
/**@}*/

/**@{
  * Constants for $smwgQueryProfiler
  */
define( 'SMW_QPRFL_NONE', 0 );
define( 'SMW_QPRFL_PARAMS', 2 ); // Support for Query parameters
define( 'SMW_QPRFL_DUR', 4 ); // Support for Query duration
/**@}*/

/**@{
  * Constants for $smwgBrowseFeatures
  */
define( 'SMW_BROWSE_NONE', 0 );
define( 'SMW_BROWSE_TLINK', 2 ); // Support for the toolbox link
define( 'SMW_BROWSE_SHOW_INVERSE', 4 ); // Support inverse direction
define( 'SMW_BROWSE_SHOW_INCOMING', 8 ); // Support for incoming links
define( 'SMW_BROWSE_SHOW_GROUP', 16 ); // Support for grouping properties
define( 'SMW_BROWSE_SHOW_SORTKEY', 32 ); // Support for the sortkey display
define( 'SMW_BROWSE_USE_API', 64 ); // Support for using the API as request backend
/**@}*/

/**@{
  * Constants for $smwgParserFeatures
  */
define( 'SMW_PARSER_NONE', 0 );
define( 'SMW_PARSER_STRICT', 2 ); // Support for strict mode
define( 'SMW_PARSER_UNSTRIP', 4 ); // Support for using the StripMarkerDecoder
define( 'SMW_PARSER_INL_ERROR', 8 ); // Support for display of inline errors
define( 'SMW_PARSER_HID_CATS', 16 ); // Support for parsing hidden categories
define( 'SMW_PARSER_LINV', 32 ); // Support for links in value
define( 'SMW_PARSER_LINKS_IN_VALUES', 32 ); // Support for links in value
/**@}*/

/**@{
  * Constants for LinksInValue features
  */
define( 'SMW_LINV_PCRE', 2 ); // Using the PCRE approach
define( 'SMW_LINV_OBFU', 4 ); // Using the Obfuscator approach
/**@}*/

/**@{
  * Constants for $smwgCategoryFeatures
  */
define( 'SMW_CAT_NONE', 0 );
define( 'SMW_CAT_REDIRECT', 2 ); // Support resolving category redirects
define( 'SMW_CAT_INSTANCE', 4 ); // Support using a category as instantiatable object
define( 'SMW_CAT_HIERARCHY', 8 ); // Support for category hierarchies
/**@}*/

/**@{
  * Constants for $smwgQSortFeatures
  */
define( 'SMW_QSORT_NONE', 0 );
define( 'SMW_QSORT', 2 ); // General sort support
define( 'SMW_QSORT_RANDOM', 4 ); // Random sort support
define( 'SMW_QSORT_UNCONDITIONAL', 8 ); // Unconditional sort support
/**@}*/

/**@{
  * Constants for $smwgRemoteReqFeatures
  */
define( 'SMW_REMOTE_REQ_SEND_RESPONSE', 2 ); // Remote responses are enabled
define( 'SMW_REMOTE_REQ_SHOW_NOTE', 4 ); // Shows a note
/**@}*/

/**@{
  * Constants for Schema groups
  */
define( 'SMW_SCHEMA_GROUP_FORMAT', 'schema.group.format' );
define( 'SMW_SCHEMA_GROUP_SEARCH_FORM', 'schema.group.search.form' );
define( 'SMW_SCHEMA_GROUP_PROPERTY', 'schema.group.property' );

/**@{
  * Constants for Special:Ask submit method
  */
define( 'SMW_SASK_SUBMIT_GET', 'get' );
define( 'SMW_SASK_SUBMIT_GET_REDIRECT', 'get.redirect' );
define( 'SMW_SASK_SUBMIT_POST', 'post' );
/**@}*/

/**@{
  * Constants for content types
  */
define( 'CONTENT_MODEL_SMW_SCHEMA', 'smw/schema' );
/**@}*/

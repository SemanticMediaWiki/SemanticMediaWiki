<?php

/**
 * Interface for SMW result printers.
 *
 * @file
 * @since 1.8
 *
 * @ingroup SMW
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
interface SMWIResultPrinter {

	/**
	 * Main entry point: takes an SMWQueryResult and parameters given as key-value-pairs in an array,
	 * and returns the serialised version of the results, formatted as HTML or Wiki or whatever is
	 * specified. Normally this is not overwritten by subclasses.
	 *
	 * If the outputmode is SMW_OUTPUT_WIKI, then the function will return something that is suitable
	 * for being used in a MediaWiki parser function, i.e. a wikitext strong *or* an array with flags
	 * and the string as entry 0. See Parser::setFunctionHook() for documentation on this. In all other
	 * cases, the function returns just a string.
	 *
	 * For outputs SMW_OUTPUT_WIKI and SMW_OUTPUT_HTML, error messages or standard "further results" links
	 * are directly generated and appended. For SMW_OUTPUT_FILE, only the plain generated text is returned.
	 *
	 * @note A note on recursion: some query printers may return wiki code that comes from other pages,
	 * e.g. from templates that are used in formatting or from embedded result pages. Both kinds of pages
	 * may contain \#ask queries that do again use new pages, so we must care about recursion. We do so
	 * by simply counting how often this method starts a subparse and stopping at depth 2. There is one
	 * special case: if this method is called outside parsing, and the concrete printer returns wiki text,
	 * and wiki text is requested, then we may return wiki text with sub-queries to the caller. If the
	 * caller parses this (which is likely) then this will again call us in parse-context and all recursion
	 * checks catch. Only the first level of parsing is done outside and thus not counted. Thus you
	 * effectively can get down to level 3. The basic maximal depth of 2 can be changed by setting the
	 * variable SMWResultPrinter::$maxRecursionDepth (in LocalSettings.php, after enableSemantics()).
	 * Do this at your own risk.
	 *
	 * @param $results SMWQueryResult
	 * @param $fullParams array
	 * @param $outputMode integer
	 *
	 * @return string
	 */
	public function getResult( SMWQueryResult $results, array $fullParams, $outputMode );

	/**
	 * Some printers do not mainly produce embeddable HTML or Wikitext, but
	 * produce stand-alone files. An example is RSS or iCalendar. This function
	 * returns the mimetype string that this file would have, or FALSE if no
	 * standalone files are produced.
	 *
	 * If this function returns something other than FALSE, then the printer will
	 * not be regarded as a printer that displays in-line results. This is used to
	 * determine if a file output should be generated in Special:Ask.
	 *
	 * @param $res
	 *
	 * @return string|boolean
	 */
	public function getMimeType( $res );

	/**
	 * This function determines the query mode that is to be used for this printer in
	 * various contexts. The query mode influences how queries to that printer should
	 * be processed to obtain a result. Possible values are SMWQuery::MODE_INSTANCES
	 * (retrieve instances), SMWQuery::MODE_NONE (do nothing), SMWQuery::MODE_COUNT
	 * (get number of results), SMWQuery::MODE_DEBUG (return debugging text).
	 * Possible values for context are SMWQueryProcessor::SPECIAL_PAGE,
	 * SMWQueryProcessor::INLINE_QUERY, SMWQueryProcessor::CONCEPT_DESC.
	 *
	 * The default implementation always returns SMWQuery::MODE_INSTANCES. File exports
	 * like RSS will use MODE_INSTANCES on special pages (so that instances are
	 * retrieved for the export) and MODE_NONE otherwise (displaying just a download link).
	 *
	 * @param $context
	 *
	 * @return integer
	 */
	public function getQueryMode( $context );

	/**
	 * Some printers can produce not only embeddable HTML or Wikitext, but
	 * can also produce stand-alone files. An example is RSS or iCalendar.
	 * This function returns a filename that is to be sent to the caller
	 * in such a case (the default filename is created by browsers from the
	 * URL, and it is often not pretty).
	 *
	 * @param $res
	 *
	 * @return string|boolean
	 */
	public function getFileName( $res );

	/**
	 * Get a human readable label for this printer. The default is to
	 * return just the format identifier. Concrete implementations may
	 * refer to messages here. The format name is normally not used in
	 * wiki text but only in forms etc. hence the user language should be
	 * used when retrieving messages.
	 *
	 * @return string
	 */
	public function getName();

	/**
	 * Set whether errors should be shown. By default they are.
	 *
	 * @param boolean $show
	 */
	public function setShowErrors( $show );

	/**
	 * Takes a list of parameter definitions and adds those supported by this
	 * result printer. Most result printers should override this method.
	 *
	 * @since 1.8
	 *
	 * @param $definitions array of IParamDefinition
	 *
	 * @return array of IParamDefinition|array
	 */
	public function getParamDefinitions( array $definitions );

}
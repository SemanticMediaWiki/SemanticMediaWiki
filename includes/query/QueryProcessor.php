<?php

namespace SMW;

use SMWQueryProcessor;
use SMWQuery;
use MWException;

/**
 * Interface for the SMW\QueryProcessor
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @since 1.9
 *
 * @file
 * @ingroup SMWQuery
 *
 * @author Markus Krötzsch
 * @author mwjames
 */
interface IQueryProcessor {

	/**
	 * The constructor requires a $outputMode (SMW_OUTPUT_WIKI, SMW_OUTPUT_HTML)
	 * and a $queryContext (SPECIAL_PAGE, INLINE_QUERY, CONCEPT_DESC)
	 */

	/**
	 * Returns query object
	 *
	 * @since 1.9
	 *
	 * @return SMWQuery
	 */
	public function getQuery();

	/**
	 * Returns results to processed query object
	 *
	 * @since 1.9
	 *
	 * @return string
	 */
	public function getResult();

}

/**
 * Encapsulates the SMWQueryProcessor class and avoid static binding
 * introduced by SMWQueryProcessor
 *
 * FIXME Converted static SMWQueryProcessor methods, unless the static methods
 * are gone and SMWQueryProcessor is converted both classes are needed in
 * parallel
 *
 * @ingroup SMWQuery
 */
class QueryProcessor implements IQueryProcessor {

	/**
	 * "query contexts" define restrictions during query parsing and
	 * are used to preconfigure query (e.g. special pages show no further
	 * results link):
	 */
	const SPECIAL_PAGE = 0; // Query for special page
	const INLINE_QUERY = 1; // Query for inline use
	const CONCEPT_DESC = 2; // Query for concept definition

	/**
	 * SMW_OUTPUT_WIKI, SMW_OUTPUT_HTML
	 * @var $outputMode
	 */
	protected $outputMode;

	/**
	 * Defines in what context the query is used, which affects certain general
	 * settings. (SPECIAL_PAGE, INLINE_QUERY, CONCEPT_DESC)
	 * @var $queryContext
	 */
	protected $queryContext;

	/**
	 * Indicates #show parser function
	 * @var $showMode
	 */
	protected $showMode;

	/**
	 * Represents a IParam array
	 * @var $parameters
	 */
	protected $parameters;

	/**
	 * Represents a SMW\Query object
	 * @var $query
	 */
	protected $query;

	/**
	 * Represents a query string
	 * @var $queryString
	 */
	protected $queryString;

	/**
	 * Constructor
	 *
	 * @since 1.9
	 *
	 * @param integer $outputMode SMW_OUTPUT_WIKI, SMW_OUTPUT_HTML, ...
	 * @param integer $queryContext INLINE_QUERY, SPECIAL_PAGE, CONCEPT_DESC
	 * @param boolean $showMode process like #show parser function?
	 */
	public function __construct( $outputMode, $queryContext, $showMode = false ) {
		$this->outputMode = $outputMode;
		$this->queryContext = $queryContext;
		$this->showMode = $showMode;
	}

	/**
	 * Returns re-mapped parameters
	 *
	 * @since 1.9
	 *
	 * @return IParam
	 */
	public function getParameters(){
		return $this->parameters;
	}

	/**
	 * Returns query object
	 *
	 * @since 1.9
	 *
	 * @return SMWQuery
	 */
	public function getQuery(){
		return $this->query;
	}

	/**
	 * Returns printouts
	 *
	 * @since 1.9
	 *
	 * @return array
	 */
	public function getPrintOuts(){
		return $this->printouts;
	}

	/**
	 * Returns query string
	 *
	 * @since 1.9
	 *
	 * @return string
	 */
	public function getQueryString(){
		return $this->queryString;
	}

	/**
	 * Map and pre-process raw parameters (produced by #ask parser function)
	 * to obtain actual parameters, printout requests, and the query string
	 * for further processing
	 *
	 * @since 1.9
	 *
	 * @param array $rawParams user-provided list of unparsed parameters
	 */
	public function map( array $rawParams ) {
		//wfProfileIn( __METHOD__ );

		list( $queryString, $params, $printouts ) = SMWQueryProcessor::getComponentsFromFunctionParams( $rawParams, $this->showMode );

		if ( !$this->showMode ) {
			SMWQueryProcessor::addThisPrintout( $printouts, $params );
		}

		$this->queryString = $queryString;
		$this->printRequests = $printouts;

		$this->parameters = $this->getProcessedParams( $params, $this->printRequests );
		$this->query = $this->createQuery( $this->queryString, $this->parameters, '', $this->printRequests );

		//wfProfileOut( __METHOD__ );
	}

	/**
	 * Takes an array of unprocessed parameters, processes them using
	 * Validator, and returns them.
	 *
	 * Both input and output arrays are
	 * param name (string) => param value (mixed)
	 *
	 * @since 1.6.2
	 * The return value changed in SMW 1.8 from an array with result values
	 * to an array with IParam objects.
	 *
	 * @param array $params
	 * @param array $printRequests
	 * @param boolean $unknownInvalid
	 *
	 * @return array of IParam
	 */
	public function getProcessedParams( array $params, array $printRequests = array(), $unknownInvalid = true ) {
		return SMWQueryProcessor::getProcessedParams( $params, $printRequests );
	}

	/**
	 * Parse a query string given in SMW's query language to create
	 * an SMWQuery. Parameters are given as key-value-pairs in the
	 * given array. The parameter $context defines in what context the
	 * query is used, which affects certain general settings.
	 * An object of type SMWQuery is returned.
	 *
	 * The format string is used to specify the output format if already
	 * known. Otherwise it will be determined from the parameters when
	 * needed. This parameter is just for optimisation in a common case.
	 *
	 * @param string $queryString
	 * @param array $parameters These need to be the result of a list fed to getProcessedParams
	 * @param string $format
	 * @param array $extraPrintouts
	 *
	 * @return SMWQuery
	 */
	public function createQuery( $queryString, array $parameters, $format = '', array $extraPrintouts = array() ) {
		return SMWQueryProcessor::createQuery( $queryString, $parameters, $this->queryContext, $format, $extraPrintouts );
	}

	/**
	 * Return formatted results from an generated query object
	 *
	 * @since 1.9
	 *
	 * @param array $rawParams user-provided list of unparsed parameters
	 */
	public function getResult() {
		if ( !( $this->query instanceof SMWQuery ) ) {
			throw new MWException( 'The query is not initialized' );
		}

		return SMWQueryProcessor::getResultFromQuery( $this->query, $this->parameters, $this->outputMode, $this->queryContext );
	}

}
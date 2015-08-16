<?php

namespace SMW\SPARQLStore\RepositoryConnector;

use Onoi\HttpRequest\HttpRequest;
use SMW\SPARQLStore\RepositoryConnection;
use SMW\SPARQLStore\BadHttpResponseMapper;
use SMW\SPARQLStore\RepositoryClient;
use SMW\SPARQLStore\Exception\BadHttpDatabaseResponseException;
use SMW\SPARQLStore\QueryEngine\RepositoryResult;
use SMW\SPARQLStore\QueryEngine\XmlResponseParser;
use SMWExporter as Exporter;

/**
 * Basic database connector for exchanging data via SPARQL.
 *
 * @license GNU GPL v2+
 * @since 1.6
 *
 * @author Markus Krötzsch
 */
class GenericHttpRepositoryConnector implements RepositoryConnection {

	/// Flag denoting endpoints being capable of querying
	const EP_TYPE_QUERY = 1;
	/// Flag denoting endpoints being capable of updating
	const EP_TYPE_UPDATE = 2;
	/// Flag denoting endpoints being capable of SPARQL HTTP graph management
	const EP_TYPE_DATA   = 4;

	/**
	 * @var RepositoryClient
	 */
	protected $repositoryClient;

	/**
	 * @note Handles the curl handle and is reused throughout the instance to
	 * safe some initialization effort
	 *
	 * @var HttpRequest
	 */
	protected $httpRequest;

	/**
	 * @var BadHttpResponseMapper
	 */
	private $badHttpResponseMapper;

	/**
	 * @note It is suggested to use the RepositoryConnectionProvider to create
	 * a valid instance
	 *
	 * @since 2.2
	 *
	 * @param RepositoryClient $repositoryClient
	 * @param HttpRequest $httpRequest
	 */
	public function __construct( RepositoryClient $repositoryClient, HttpRequest $httpRequest ) {
		$this->repositoryClient = $repositoryClient;
		$this->httpRequest = $httpRequest;

		$this->httpRequest->setOption( CURLOPT_FORBID_REUSE, false );
		$this->httpRequest->setOption( CURLOPT_FRESH_CONNECT, false );
		$this->httpRequest->setOption( CURLOPT_RETURNTRANSFER, true ); // put result into variable
		$this->httpRequest->setOption( CURLOPT_FAILONERROR, true );

		$this->setConnectionTimeoutInSeconds( 10 );
	}

	/**
	 * Get the URI of the default graph that this database connector is
	 * using, or the empty string if none is used (no graph related
	 * statements in queries/updates).
	 *
	 * @return string graph UIR or empty
	 */
	public function getDefaultGraph() {
		return $this->repositoryClient->getDefaultGraph();
	}

	/**
	 * Check if the database can be contacted.
	 *
	 * @todo SPARQL endpoints sometimes return errors if no (valid) query
	 * is posted. The current implementation tries to catch this, but this
	 * might not be entirely correct. Especially, the SPARQL 1.1 HTTP error
	 * codes for Update are not defined yet (April 15 2011).
	 *
	 * @param $pingQueryEndpoint boolean true if the query endpoint should be
	 * pinged, false if the update endpoint should be pinged
	 *
	 * @return boolean to indicate success
	 */
	public function ping( $endpointType = self::EP_TYPE_QUERY ) {
		if ( $endpointType == self::EP_TYPE_QUERY ) {
			$this->httpRequest->setOption( CURLOPT_URL, $this->repositoryClient->getQueryEndpoint() );
			$this->httpRequest->setOption( CURLOPT_NOBODY, true );
			$this->httpRequest->setOption( CURLOPT_POST, true );
		} elseif ( $endpointType == self::EP_TYPE_UPDATE ) {

			if ( $this->repositoryClient->getUpdateEndpoint() === '' ) {
				return false;
			}

			$this->httpRequest->setOption( CURLOPT_URL, $this->repositoryClient->getUpdateEndpoint() );
			$this->httpRequest->setOption( CURLOPT_NOBODY, false ); // 4Store gives 404 instead of 500 with CURLOPT_NOBODY

		} else { // ( $endpointType == self::EP_TYPE_DATA )

			if ( $this->repositoryClient->getDataEndpoint() === '' ) {
				return false;
			}

			// try an empty POST
			return $this->doHttpPost( '' );
		}

		$this->httpRequest->execute();

		if ( $this->httpRequest->getLastErrorCode() == 0 ) {
			return true;
		}

		// valid HTTP responses from a complaining SPARQL endpoint that is alive and kicking
		$httpCode = $this->httpRequest->getLastTransferInfo( CURLINFO_HTTP_CODE );
		return ( ( $httpCode == 500 ) || ( $httpCode == 400 ) );
	}

	/**
	 * SELECT wrapper.
	 * The function declares the standard namespaces wiki, swivt, rdf, owl,
	 * rdfs, property, xsd, so these do not have to be included in
	 * $extraNamespaces.
	 *
	 * @param $vars mixed array or string, field name(s) to be retrieved, can be '*'
	 * @param $where string WHERE part of the query, without surrounding { }
	 * @param $options array (associative) of options, e.g. array( 'LIMIT' => '10' )
	 * @param $extraNamespaces array (associative) of namespaceId => namespaceUri
	 *
	 * @return RepositoryResult
	 */
	public function select( $vars, $where, $options = array(), $extraNamespaces = array() ) {
		return $this->doQuery( $this->getSparqlForSelect( $vars, $where, $options, $extraNamespaces ) );
	}

	/**
	 * Build the SPARQL query that is used by GenericHttpDatabaseConnector::select().
	 * The function declares the standard namespaces wiki, swivt, rdf, owl,
	 * rdfs, property, xsd, so these do not have to be included in
	 * $extraNamespaces.
	 *
	 * @param $where string WHERE part of the query, without surrounding { }
	 * @param $extraNamespaces array (associative) of namespaceId => namespaceUri
	 *
	 * @return string SPARQL query
	 */
	public function getSparqlForSelect( $vars, $where, $options = array(), $extraNamespaces = array() ) {

		$sparql = self::getPrefixString( $extraNamespaces ) . 'SELECT ';

		if ( array_key_exists( 'DISTINCT', $options ) ) {
			$sparql .= 'DISTINCT ';
		}

		if ( is_array( $vars ) ) {
			$sparql .= implode( ',', $vars );
		} else {
			$sparql .= $vars;
		}

		$sparql .= " WHERE {\n" . $where . "\n}";

		if ( array_key_exists( 'ORDER BY', $options ) ) {
			$sparql .= "\nORDER BY " . $options['ORDER BY'];
		}

		if ( array_key_exists( 'OFFSET', $options ) ) {
			$sparql .= "\nOFFSET " . $options['OFFSET'];
		}

		if ( array_key_exists( 'LIMIT', $options ) ) {
			$sparql .= "\nLIMIT " . $options['LIMIT'];
		}

		return $sparql;
	}

	/**
	 * ASK wrapper.
	 * The function declares the standard namespaces wiki, swivt, rdf, owl,
	 * rdfs, property, xsd, so these do not have to be included in
	 * $extraNamespaces.
	 *
	 * @param $where string WHERE part of the query, without surrounding { }
	 * @param $extraNamespaces array (associative) of namespaceId => namespaceUri
	 *
	 * @return RepositoryResult
	 */
	public function ask( $where, $extraNamespaces = array() ) {
		return $this->doQuery( $this->getSparqlForAsk( $where, $extraNamespaces ) );
	}

	/**
	 * Build the SPARQL query that is used by GenericHttpDatabaseConnector::ask().
	 * The function declares the standard namespaces wiki, swivt, rdf, owl,
	 * rdfs, property, xsd, so these do not have to be included in
	 * $extraNamespaces.
	 *
	 * @param $where string WHERE part of the query, without surrounding { }
	 * @param $extraNamespaces array (associative) of namespaceId => namespaceUri
	 *
	 * @return string SPARQL query
	 */
	public function getSparqlForAsk( $where, $extraNamespaces = array() ) {
		return self::getPrefixString( $extraNamespaces ) . "ASK {\n" . $where . "\n}";
	}

	/**
	 * SELECT wrapper for counting results.
	 * The function declares the standard namespaces wiki, swivt, rdf, owl,
	 * rdfs, property, xsd, so these do not have to be included in
	 * $extraNamespaces.
	 *
	 * @param $variable string variable name or '*'
	 * @param $where string WHERE part of the query, without surrounding { }
	 * @param $options array (associative) of options, e.g. array('LIMIT' => '10')
	 * @param $extraNamespaces array (associative) of namespaceId => namespaceUri
	 *
	 * @return RepositoryResult
	 */
	public function selectCount( $variable, $where, $options = array(), $extraNamespaces = array() ) {

		$sparql = self::getPrefixString( $extraNamespaces ) . 'SELECT (COUNT(';

		if ( array_key_exists( 'DISTINCT', $options ) ) {
			$sparql .= 'DISTINCT ';
		}

		$sparql .= $variable . ") AS ?count) WHERE {\n" . $where . "\n}";

		if ( array_key_exists( 'OFFSET', $options ) ) {
			$sparql .= "\nOFFSET " . $options['OFFSET'];
		}

		if ( array_key_exists( 'LIMIT', $options ) ) {
			$sparql .= "\nLIMIT " . $options['LIMIT'];
		}

		return $this->doQuery( $sparql );
	}

	/**
	 * DELETE wrapper.
	 * The function declares the standard namespaces wiki, swivt, rdf, owl,
	 * rdfs, property, xsd, so these do not have to be included in
	 * $extraNamespaces.
	 *
	 * @param $deletePattern string CONSTRUCT pattern of tripples to delete
	 * @param $where string condition for data to delete
	 * @param $extraNamespaces array (associative) of namespaceId => namespaceUri
	 *
	 * @return boolean stating whether the operations succeeded
	 */
	public function delete( $deletePattern, $where, $extraNamespaces = array() ) {

		$defaultGraph = $this->repositoryClient->getDefaultGraph();

		$sparql = self::getPrefixString( $extraNamespaces ) .
			( ( $defaultGraph !== '' )? "WITH <{$defaultGraph}> " : '' ) .
			"DELETE { $deletePattern } WHERE { $where }";

		return $this->doUpdate( $sparql );
	}

	/**
	 * Convenience method for deleting all triples that have a subject that
	 * occurs in a triple with the given property and object. This is used
	 * in SMW to delete subobjects with all their data. Some RDF stores fail
	 * on complex delete queries, hence a wrapper function is provided to
	 * allow more pedestrian implementations.
	 *
	 * The function declares the standard namespaces wiki, swivt, rdf, owl,
	 * rdfs, property, xsd, so these do not have to be included in
	 * $extraNamespaces.
	 *
	 * @param $propertyName string Turtle name of marking property
	 * @param $objectName string Turtle name of marking object/value
	 * @param $extraNamespaces array (associative) of namespaceId => namespaceUri
	 *
	 * @return boolean stating whether the operations succeeded
	 */
	public function deleteContentByValue( $propertyName, $objectName, $extraNamespaces = array() ) {
		return $this->delete( "?s ?p ?o", "?s $propertyName $objectName . ?s ?p ?o", $extraNamespaces );
	}

	/**
	 * Convenience method for deleting all triples of the entire store
	 *
	 * @return boolean
	 */
	public function deleteAll() {
		return $this->delete( "?s ?p ?o", "?s ?p ?o" );
	}

	/**
	 * INSERT DELETE wrapper.
	 * The function declares the standard namespaces wiki, swivt, rdf, owl,
	 * rdfs, property, xsd, so these do not have to be included in
	 * $extraNamespaces.
	 *
	 * @param $insertPattern string CONSTRUCT pattern of tripples to insert
	 * @param $deletePattern string CONSTRUCT pattern of tripples to delete
	 * @param $where string condition for data to delete
	 * @param $extraNamespaces array (associative) of namespaceId => namespaceUri
	 *
	 * @return boolean stating whether the operations succeeded
	 */
	public function insertDelete( $insertPattern, $deletePattern, $where, $extraNamespaces = array() ) {

		$defaultGraph = $this->repositoryClient->getDefaultGraph();

		$sparql = self::getPrefixString( $extraNamespaces ) .
			( ( $defaultGraph !== '' )? "WITH <{$defaultGraph}> " : '' ) .
			"DELETE { $deletePattern } INSERT { $insertPattern } WHERE { $where }";

		return $this->doUpdate( $sparql );
	}

	/**
	 * INSERT DATA wrapper.
	 * The function declares the standard namespaces wiki, swivt, rdf, owl,
	 * rdfs, property, xsd, so these do not have to be included in
	 * $extraNamespaces.
	 *
	 * @param $triples string of triples to insert
	 * @param $extraNamespaces array (associative) of namespaceId => namespaceUri
	 *
	 * @return boolean stating whether the operations succeeded
	 */
	public function insertData( $triples, $extraNamespaces = array() ) {

		if ( $this->repositoryClient->getDataEndpoint() !== '' ) {
			$turtle = self::getPrefixString( $extraNamespaces, false ) . $triples;
			return $this->doHttpPost( $turtle );
		}

		$defaultGraph = $this->repositoryClient->getDefaultGraph();

		$sparql = self::getPrefixString( $extraNamespaces, true ) .
			"INSERT DATA  " .
			( ( $defaultGraph !== '' )? " { GRAPH <{$defaultGraph}> " : '' ) .
			"{ $triples } " .
			( ( $defaultGraph !== '' )? " } " : '' );

		return $this->doUpdate( $sparql );
	}

	/**
	 * DELETE DATA wrapper.
	 * The function declares the standard namespaces wiki, swivt, rdf, owl,
	 * rdfs, property, xsd, so these do not have to be included in
	 * $extraNamespaces.
	 *
	 * @param $triples string of triples to delete
	 * @param $extraNamespaces array (associative) of namespaceId => namespaceUri
	 *
	 * @return boolean stating whether the operations succeeded
	 */
	public function deleteData( $triples, $extraNamespaces = array() ) {

		$defaultGraph = $this->repositoryClient->getDefaultGraph();

		$sparql = self::getPrefixString( $extraNamespaces ) .
			"DELETE DATA { " .
			( ( $defaultGraph !== '' )? "GRAPH <{$defaultGraph}> " : '' ) .
			"{ $triples } }";

		return $this->doUpdate( $sparql );
	}


	/**
	 * Execute a SPARQL query and return an RepositoryResult object
	 * that contains the results. The method throws exceptions based on
	 * GenericHttpDatabaseConnector::mapHttpRequestError(). If errors occur and this
	 * method does not throw anything, then an empty result with an error
	 * code is returned.
	 *
	 * @note This function sets the graph that is to be used as part of the
	 * request. Queries should not include additional graph information.
	 *
	 * @param $sparql string with the complete SPARQL query (SELECT or ASK)
	 *
	 * @return RepositoryResult
	 */
	public function doQuery( $sparql ) {

		if ( $this->repositoryClient->getQueryEndpoint() === '' ) {
			throw new BadHttpDatabaseResponseException( BadHttpDatabaseResponseException::ERROR_NOSERVICE, $sparql, 'not specified' );
		}

		$this->httpRequest->setOption( CURLOPT_URL, $this->repositoryClient->getQueryEndpoint() );

		$this->httpRequest->setOption( CURLOPT_HTTPHEADER, array(
			'Accept: application/sparql-results+xml,application/xml;q=0.8',
			'Content-Type: application/x-www-form-urlencoded;charset=UTF-8'
		) );

		$this->httpRequest->setOption( CURLOPT_POST, true );

		$defaultGraph = $this->repositoryClient->getDefaultGraph();

		$parameterString = "query=" . urlencode( $sparql ) .
			( ( $defaultGraph !== '' )? '&default-graph-uri=' . urlencode( $defaultGraph ) : '' );

		$this->httpRequest->setOption( CURLOPT_POSTFIELDS, $parameterString );

		$httpResponse = $this->httpRequest->execute();

		if ( $this->httpRequest->getLastErrorCode() == 0 ) {
			$xmlResponseParser = new XmlResponseParser();
			return $xmlResponseParser->parse( $httpResponse );
		}

		$this->mapHttpRequestError( $this->repositoryClient->getQueryEndpoint(), $sparql );

		$repositoryResult = new RepositoryResult();
		$repositoryResult->setErrorCode( RepositoryResult::ERROR_UNREACHABLE );

		return $repositoryResult;
	}

	/**
	 * Execute a SPARQL update and return a boolean to indicate if the
	 * operations was successful. The method throws exceptions based on
	 * GenericHttpDatabaseConnector::mapHttpRequestError(). If errors occur and this
	 * method does not throw anything, then false is returned.
	 *
	 * @note When this is written, it is not clear if the update protocol
	 * supports a default-graph-uri parameter. Hence the target graph for
	 * all updates is generally encoded in the query string and not fixed
	 * when sending the query. Direct callers to this function must include
	 * the graph information in the queries that they build.
	 *
	 * @param $sparql string with the complete SPARQL update query (INSERT or DELETE)
	 *
	 * @return boolean
	 */
	public function doUpdate( $sparql ) {

		if ( $this->repositoryClient->getUpdateEndpoint() === '' ) {
			throw new BadHttpDatabaseResponseException( BadHttpDatabaseResponseException::ERROR_NOSERVICE, $sparql, 'not specified' );
		}

		$this->httpRequest->setOption( CURLOPT_URL, $this->repositoryClient->getUpdateEndpoint() );
		$this->httpRequest->setOption( CURLOPT_POST, true );

		$parameterString = "update=" . urlencode( $sparql );

		$this->httpRequest->setOption( CURLOPT_POSTFIELDS, $parameterString );
		$this->httpRequest->setOption( CURLOPT_HTTPHEADER, array( 'Content-Type: application/x-www-form-urlencoded;charset=UTF-8' ) );

		$this->httpRequest->execute();

		if ( $this->httpRequest->getLastErrorCode() == 0 ) {
			return true;
		}

		$this->mapHttpRequestError( $this->repositoryClient->getUpdateEndpoint(), $sparql );
		return false;
	}

	/**
	 * Execute a HTTP-based SPARQL POST request according to
	 * http://www.w3.org/2009/sparql/docs/http-rdf-update/.
	 * The method throws exceptions based on
	 * GenericHttpDatabaseConnector::mapHttpRequestError(). If errors occur and this
	 * method does not throw anything, then an empty result with an error
	 * code is returned.
	 *
	 * @note This protocol is not part of the SPARQL standard and may not
	 * be supported by all stores. To avoid using it, simply do not provide
	 * a data endpoint URL when configuring the SPARQL database. If used,
	 * the protocol might lead to a better performance since there is less
	 * parsing required to fetch the data from the request.
	 * @note Some stores (e.g. 4Store) support another mode of posting data
	 * that may be implemented in a special database handler.
	 *
	 * @param $payload string Turtle serialization of data to send
	 *
	 * @return boolean
	 */
	public function doHttpPost( $payload ) {

		if ( $this->repositoryClient->getDataEndpoint() === '' ) {
			throw new BadHttpDatabaseResponseException( BadHttpDatabaseResponseException::ERROR_NOSERVICE, "SPARQL POST with data: $payload", 'not specified' );
		}

		$defaultGraph = $this->repositoryClient->getDefaultGraph();

		$this->httpRequest->setOption( CURLOPT_URL, $this->repositoryClient->getDataEndpoint() .
			( ( $defaultGraph !== '' )? '?graph=' . urlencode( $defaultGraph ) : '?default' ) );
		$this->httpRequest->setOption( CURLOPT_POST, true );

		// POST as file (fails in 4Store)
		$payloadFile = tmpfile();
		fwrite( $payloadFile, $payload );
		fseek( $payloadFile, 0 );

		$this->httpRequest->setOption( CURLOPT_INFILE, $payloadFile );
		$this->httpRequest->setOption( CURLOPT_INFILESIZE, strlen( $payload ) );
		$this->httpRequest->setOption( CURLOPT_HTTPHEADER, array( 'Content-Type: application/x-turtle' ) );

		$this->httpRequest->execute();

		if ( $this->httpRequest->getLastErrorCode() == 0 ) {
			return true;
		}

		// TODO The error reporting based on SPARQL (Update) is not adequate for the HTTP POST protocol
		$this->mapHttpRequestError( $this->repositoryClient->getDataEndpoint(), $payload );
		return false;
	}

	/**
	 * Create the standard PREFIX declarations for SPARQL or Turtle,
	 * possibly with additional namespaces involved.
	 *
	 * @param $extraNamespaces array (associative) of namespaceId => namespaceUri
	 * @param $forSparql boolean true to use SPARQL prefix syntax, false to use Turtle prefix syntax
	 *
	 * @return string
	 */
	public static function getPrefixString( $extraNamespaces = array(), $forSparql = true ) {
		$prefixString = '';
		$prefixIntro = $forSparql ? 'PREFIX ' : '@prefix ';
		$prefixOutro = $forSparql ? "\n" : " .\n";

		foreach ( array( 'wiki', 'rdf', 'rdfs', 'owl', 'swivt', 'property', 'xsd' ) as $shortname ) {
			$prefixString .= "{$prefixIntro}{$shortname}: <" . Exporter::getInstance()->getNamespaceUri( $shortname ) . ">$prefixOutro";
			unset( $extraNamespaces[$shortname] ); // avoid double declaration
		}

		foreach ( $extraNamespaces as $shortname => $uri ) {
			$prefixString .= "{$prefixIntro}{$shortname}: <$uri>$prefixOutro";
		}

		return $prefixString;
	}

	/**
	 * @param $endpoint string URL of endpoint that was used
	 * @param $sparql string query that caused the problem
	 */
	protected function mapHttpRequestError( $endpoint, $sparql ) {

		if ( $this->badHttpResponseMapper === null ) {
			$this->badHttpResponseMapper = new BadHttpResponseMapper( $this->httpRequest );
		}

		$this->badHttpResponseMapper->mapResponseToHttpRequest( $endpoint, $sparql );
	}

	/**
	 * @since  2.0
	 *
	 * @param integer $timeout
	 *
	 * @return SparqlDatabase
	 */
	public function setConnectionTimeoutInSeconds( $timeout = 10 ) {
		$this->httpRequest->setOption( CURLOPT_CONNECTTIMEOUT, $timeout );
		return $this;
	}

}

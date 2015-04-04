<?php

namespace SMW\SPARQLStore;

/**
 * @license GNU GPL v2+
 * @since 2.2
 *
 * @author mwjames
 * @author Markus Krötzsch
 */
interface RepositoryConnection {

	/**
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
	public function select( $vars, $where, $options = array(), $extraNamespaces = array() );

	/**
	 * The function declares the standard namespaces wiki, swivt, rdf, owl,
	 * rdfs, property, xsd, so these do not have to be included in
	 * $extraNamespaces.
	 *
	 * @param $where string WHERE part of the query, without surrounding { }
	 * @param $extraNamespaces array (associative) of namespaceId => namespaceUri
	 *
	 * @return RepositoryResult
	 */
	public function ask( $where, $extraNamespaces = array() );

	/**
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
	public function delete( $deletePattern, $where, $extraNamespaces = array() );

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
	 * @param string $sparql complete SPARQL query (SELECT or ASK)
	 *
	 * @return RepositoryResult
	 */
	public function doQuery( $sparql );

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
	 * @param string $sparql complete SPARQL update query (INSERT or DELETE)
	 *
	 * @return boolean
	 */
	public function doUpdate( $sparql );

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
	 * @param string $payload Turtle serialization of data to send
	 *
	 * @return boolean
	 */
	public function doHttpPost( $payload );

}

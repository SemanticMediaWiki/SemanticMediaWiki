# SPARQLStore

The `SPARQLStore` is the name for the component that can establish a connection between a [RDF triple store][tdb] and Semantic MediaWiki (a more general introduction can be found [here](https://www.semantic-mediawiki.org/wiki/Help:Using SPARQL and RDF stores)).

The `SPARQLStore` is composed of a base store (by default using the existing `SQLStore`), a `QueryEngine`, and a connector to the RDF back-end. Currently, the base store takes the position of accumulating information about properties, value annotations, and statistics.

## Overview

![image](https://cloud.githubusercontent.com/assets/1245473/9708428/e1e2bf1a-551b-11e5-920c-dd97d66d2ec7.png)

## Repository connector

A repository connector is responsible for establishing a communication between Semantic MediaWiki and an external [TDB][tdb] with the main objective to transfer/update triples from SMW to the back-end and to return result matches for a query request.

The following client repositories have been tested:

- [Jena Fuseki][fuseki]
- [Virtuoso][virtuoso]
- [Blazegraph][blazegraph]
- [Sesame][sesame]
- [4Store][4store]

### Create a connection
<pre>
$connectionManager = new ConnectionManager();

$connectionManager->registerConnectionProvider(
	'sparql',
	new RepositoryConnectionProvider( 'fuseki' )
);

$connection = $connectionManager->getConnection( 'sparql' )
</pre>

## QueryEngine

The `QueryEngine` is responsible for transforming an `#ask` description object into a qualified
[`SPARQL` query][sparql-query] expression.

- The `CompoundConditionBuilder` builds a SPARQL condition from an `#ask` query artefact (aka [`Description`][ask query] object)
- The condition is transformed into a qualified `SPARQL` statement for which the [repository connector][connector] is making a http request to the back-end while awaiting an expected list of subjects that matched the condition in form of a `XML` or `JSON` response
- The raw results are being parsed by a `HttpResponseParser` to provide a unified `RepositoryResult` object
- During the final step, the `QueryResultFactory` converts the `RepositoryResult` into a SMW specific `QueryResult` object which will fetch the remaining data (those selected as printrequests) from the base store and make them available to a [`QueryResultPrinter`][resultprinter]

### Create a query request
<pre>
/**
 * Equivalent to [[Foo::+]]
 *
 * SELECT DISTINCT ?result WHERE {
 * ?result swivt:wikiPageSortKey ?resultsk .
 * ?result property:Foo ?v1 .
 * }
 * ORDER BY ASC(?resultsk)
 */
$description = new SomeProperty(
    new DIProperty( 'Foo' ),
    new ThingDescription()
);

$query = new Query( $description );

$sparqlStoreFactory = new SPARQLStoreFactory(
  new SPARQLStore()
);

$queryEngine = $sparqlStoreFactory->newMasterQueryEngine();
$queryResult = $queryEngine->getQueryResult( $query );
</pre>

[fuseki]: https://jena.apache.org/
[fuseki-dataset]: https://jena.apache.org/documentation/tdb/dynamic_datasets.html
[sparql-query]:http://www.w3.org/TR/sparql11-query/
[sparql-dataset]: https://www.w3.org/TR/sparql11-query/#specifyingDataset
[virtuoso]: https://github.com/openlink/virtuoso-opensource
[4store]: https://github.com/garlik/4store
[tdb]: http://en.wikipedia.org/wiki/Triplestore
[sesame]: http://rdf4j.org/
[blazegraph]: https://wiki.blazegraph.com/wiki/index.php/Main_Page
[ask query]: https://www.semantic-mediawiki.org/wiki/Query_language
[connector]: https://www.semantic-mediawiki.org/wiki/Help:SPARQLStore/RepositoryConnector
[resultprinter]: https://www.semantic-mediawiki.org/wiki/Help:SPARQLStore/RepositoryConnector

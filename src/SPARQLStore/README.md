# SPARQLStore

The `SPARQLStore` consists of two components, a base store (by default using the existing `SQLStore`) and a client database connector. Currently, the base store is responsible for accumulating information about properties, value annotations, and statistics.

## Repository connector

A repository connector is responsible for updating triples to external [TDB][tdb] and return results from it for query requests made by the `QueryEngine`.

The following client repositories are currently supported:

- [Jena Fuseki][fuseki]
- [Virtuoso][virtuoso]
- [4Store][4store]
- [Sesame][sesame]

```php
$connectionManager = new ConnectionManager();

$connectionManager->registerConnectionProvider(
	'sparql',
	new RepositoryConnectionProvider( 'fuseki' )
);

$connectionManager->getConnection( 'sparql' )
```

## QueryEngine

The `QueryEngine` is responsible for transforming a `ask` description object into a qualified
[`SPARQL` query language][sparql-query] string.

- The `CompoundConditionBuilder` builds a SPARQL condition from a `#ask` query artefact (`Description` object)
- The condition is being transformed into a qualified `SPARQL` query with the client connector making a request to the database to return a list of raw results
- The list with raw results is being parsed by a `HttpResponseParser` to provide a unified `RepositoryResult`
- During the final step, the `QueryResultFactory` converts the `RepositoryResult` into a SMW specific `QueryResult` object which will fetch all remaining data (those selected as printrequests) from the base store to make them available to a `QueryResultPrinter`

### Examples
```php
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
```
```php
$query = new Query( $description );

$sparqlStorefactory = new SPARQLStoreFactory(
  new SPARQLStore()
);

$queryEngine = $sparqlStorefactory->newMasterQueryEngine();
$queryResult = $queryEngine->getQueryResult( $query );
```

## Integration testing

Information about integration testing and installation can be found in the [build environment documentation](../../../build/travis/README.md).

## Miscellaneous

- [Large Triple Stores](http://www.w3.org/wiki/LargeTripleStores)

[fuseki]: https://jena.apache.org/
[fuseki-dataset]: https://jena.apache.org/documentation/tdb/dynamic_datasets.html
[sparql-query]:http://www.w3.org/TR/sparql11-query/
[sparql-dataset]: https://www.w3.org/TR/sparql11-query/#specifyingDataset
[virtuoso]: https://github.com/openlink/virtuoso-opensource
[4store]: https://github.com/garlik/4store
[tdb]: http://en.wikipedia.org/wiki/Triplestore
[sesame]: http://rdf4j.org/

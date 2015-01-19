# SPARQLStore

The `SPARQLStore` consists of two components, a base store (by default using the existing `SQLStore`) and a client database connector. Currently, the base store is responsible for accumulating information about properties, value annotations, and statistics.

## Database connector 

The database connector is responsible for updating triples to the store and return results from the [TDB][tdb] for query requests made by the `QueryEngine`.

The following client database connectors are currently available:

- [Jena Fuseki][fuseki] is supported by `FusekiHttpDatabaseConnector`
- [Virtuoso][virtuoso] is supported by `VirtuosoHttpDatabaseConnector`
- [4Store][4store] is supported by `FourstoreHttpDatabaseConnector`
- `GenericHttpDatabaseConnector` is used as default connector

## QueryEngine

The `QueryEngine` is responsible for transforming a `#ask` object into a qualified query using the [`SPARQL` query language][sparql-query].

- The `CompoundConditionBuilder` builds a SPARQL condition from a `#ask` query artefact (`Description` object)
- The condition is being transformed into a qualified `SPARQL` query with the client connector making a request to the database to return a list of raw results
- The list with raw results is being parsed by the `RawResultParser` to provide a unified `FederateResultSet`
- For the final step, the `QueryResultFactory` converts the `FederateResultSet` into a SMW specific `QueryResult` object which will fetch remaining data (those selected as printrequests) from the base store to make them available to a `QueryResultPrinter`

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

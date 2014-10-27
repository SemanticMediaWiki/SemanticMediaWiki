# SPARQLStore

The `SPARQLStore` uses two components a base store (by default using the existing `SQLStore`) and a client database connector. The base store accumulates information about properties and value annotations as well as statistics while the database connector is responsible for transforming a `#ask` into a `SPARQL` query and requesting data from the [TDB][tdb].

- The `CompoundConditionBuilder` builds a SPARQL condition from a `#ask` query artefact (`Description` object)
- The condition is being transformed into a qualified `SPARQL` query with the client connector making a request to the database to return a list of raw results
- The list with raw results is being parsed by the `RawResultParser` to provide a unified `FederateResultSet`
- For the final step, the `QueryResultFactory` converts the `FederateResultSet` into a SMW specific `QueryResult` object which will fetch remaining data (those selected as printrequests) from the base store to make them available to a `QueryResultPrinter`

The following client database connectors are currently available:

- [Jena Fuseki][fuseki] is supported by `FusekiHttpDatabaseConnector`
- [Virtuoso][virtuoso] is supported by `VirtuosoHttpDatabaseConnector`
- [4Store][4store] is supported by `FourstoreHttpDatabaseConnector`
- `GenericHttpDatabaseConnector` is used as default connector

## Integration testing

Information about the testing environment and installation details can be found in the `build/travis/install-services.sh`.

### Jena Fuseki integration

When running integration tests with [Jena Fuseki][fuseki] it is suggested that the `in-memory` option is used to avoid potential loss of production data during test execution.

```sh
fuseki-server --update --port=3030 --mem /db
```

```php
$smwgSparqlDatabaseConnector = 'Fuseki';
$smwgSparqlQueryEndpoint = 'http://localhost:3030/db/query';
$smwgSparqlUpdateEndpoint = 'http://localhost:3030/db/update';
$smwgSparqlDataEndpoint = '';
```

Fuseki supports [TDB Dynamic Datasets][fuseki-dataset] (in SPARQL known as [RDF dataset][sparql-dataset]) which can enbled using the following settings for testing.

```sh
fuseki-server --update --port=3030 --memTDB --set tdb:unionDefaultGraph=true /db
```
```php
$smwgSparqlDatabaseConnector = 'Fuseki';
$smwgSparqlQueryEndpoint = 'http://localhost:3030/db/query';
$smwgSparqlUpdateEndpoint = 'http://localhost:3030/db/update';
$smwgSparqlDataEndpoint = '';
$smwgSparqlDefaultGraph = 'http://example.org/myFusekiGraph';
```
### Virtuoso integration

Virtuoso-opensource 6.1

```sh
sudo apt-get install virtuoso-opensource
```

```php
$smwgSparqlDatabaseConnector = 'Virtuoso';
$smwgSparqlQueryEndpoint = 'http://localhost:8890/sparql';
$smwgSparqlUpdateEndpoint = 'http://localhost:8890/sparql';
$smwgSparqlDataEndpoint = '';
$smwgSparqlDefaultGraph = 'http://example.org/myVirtuosoGraph';
```

### 4Store integration

Currently, Travis-CI doesn't support `4Store` (1.1.4-2) as service but the following configuration has been sucessfully tested with the available test suite.

```sh
apt-get install 4store
```

```php
$smwgSparqlDatabaseConnector = '4store';
$smwgSparqlQueryEndpoint = 'http://localhost:8088/sparql/';
$smwgSparqlUpdateEndpoint = 'http://localhost:8088/update/';
$smwgSparqlDataEndpoint = 'http://localhost:8088/data/';
$smwgSparqlDefaultGraph = 'http://example.org/myFourstoreGraph';
```

## Miscellaneous

- [Large Triple Stores](http://www.w3.org/wiki/LargeTripleStores)

[fuseki]: https://jena.apache.org/
[fuseki-dataset]: https://jena.apache.org/documentation/tdb/dynamic_datasets.html
[sparql-dataset]: https://www.w3.org/TR/sparql11-query/#specifyingDataset
[virtuoso]: https://github.com/openlink/virtuoso-opensource
[4store]: https://github.com/garlik/4store
[tdb]: http://en.wikipedia.org/wiki/Triplestore
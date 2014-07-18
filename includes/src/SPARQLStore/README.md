# SPARQLStore

The following client database connectors are currently available:

- Default using `GenericHttpDatabaseConnector`
- [Jena Fuseki][fuseki] using `FusekiHttpDatabaseConnector`
- [Virtuoso][virtuoso] using `VirtuosoHttpDatabaseConnector`
- [4Store][4store] using `FourstoreHttpDatabaseConnector`

## SPARQLStore integration

The `SPARQLStore` uses two components a base store (by default using the existing `SQLStore`) and a client database connector. The base store accumulates information about properties and value annotations as well as statistics while the database connector is responsible for transforming a `#ask` query into a `SPARQL` query and requesting data from the [TDB][tdb].

- `#ask` query is transformed into an equivalent SPARQL condition using the `QueryConditionBuilder`
- The client database connector resolves the condition into a `SPARQL` statement and makes a query request to the database
- The client database (Fuseki etc. ) is expected to execute the query and return a list of results which are parsed using the `RawResultParser` and made available as `FederateResultList`
- The `ResultListConverter` is to convert a `FederateResultList` into a `QueryResult` object
- The `QueryResult` object will fetch all remaining data (for each printrequest ) from the base store which are to be used by a `ResultPrinter`

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
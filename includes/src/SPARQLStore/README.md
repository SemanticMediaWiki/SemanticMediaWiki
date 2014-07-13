# SPARQLStore

The following client database connectors are currently available:

- [Jena Fuseki][fuseki] (`FusekiHttpDatabaseConnector`)
- [Virtuoso][virtuoso] (`VirtuosoHttpDatabaseConnector`)
- [4Store][4store] (`FourstoreHttpDatabaseConnector`)

## SPARQLStore integration

The SPARQLStore uses two components a base store (by default the existing `SQLStore`) that stores information about properties and value assignments as well as statistics and a client database connector. The client database connector is responsible for transforming a `ask` query into a `SPARQL` query and requesting the TDB to resolved the query by returning a list of subjects that meet the requirements.

- A `ask` query is transformed into a SPARQL condition using the `QueryConditionBuilder`
- The client database connector is to resolve the condition into a `SPARQL` statement and send it to the client database
- The client database (Fuseki etc. ) is expected to execute the query and return a list of results which are parsed using the `RawResultParser` and made available as `FederateResultList`
- The `ResultListConverter` is to create a `QueryResult` object from the `FederateResultList`
- In connection with a `ResultPrinter`, the `QueryResult` object will fetch all remaining data (for each printrequest ) from the base store

## Integration testing

Information about the testing environment and installation details can be found [here](../../build/travis/install-services.sh).

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

[fuseki]: https://jena.apache.org/
[fuseki-dataset]: https://jena.apache.org/documentation/tdb/dynamic_datasets.html
[sparql-dataset]: https://www.w3.org/TR/sparql11-query/#specifyingDataset
[virtuoso]: https://github.com/openlink/virtuoso-opensource
[4store]: https://github.com/garlik/4store
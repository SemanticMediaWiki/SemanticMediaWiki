- `install-mediawiki.sh` to handle the install of MediaWiki
- `install-semantic-mediawiki.sh` to handle the install of Semantic MediaWiki
- `install-services.sh` to handle the install of additional services

## SPARQL endpoints and services

- Fuseki (mem)
  - Connector: Fuseki (When running integration tests with [Jena Fuseki][fuseki] it is suggested that the `in-memory` option is used to avoid potential loss of production data during test execution.)
  - QueryEndPoint: http://localhost:3030/db/query
  - UpdateEndPoint: http://localhost:3030/db/update
  - DataEndpoint:
  - DefaultGraph:
  - Comments: fuseki-server --update --port=3030 --mem /db

- Fuseki (memTDB)
  - Connector: Fuseki
  - QueryEndPoint: http://localhost:3030/db/query
  - UpdateEndPoint: http://localhost:3030/db/update
  - DataEndpoint:
  - DefaultGraph: http://example.org/myFusekiGraph
  - Comments: fuseki-server --update --port=3030 --memTDB --set tdb:unionDefaultGraph=true /db

- Virtuoso opensource
  - Connector: Virtuoso
  - QueryEndPoint: http://localhost:8890/sparql
  - UpdateEndPoint: http://localhost:8890/sparql
  - DataEndpoint:
  - DefaultGraph: http://example.org/myVirtuosoGraph
  - Comments: sudo apt-get install virtuoso-opensource

- 4store
  - Connector: 4store (Currently, Travis-CI doesn't support `4Store` (1.1.4-2) as service but the following configuration has been successfully tested with the available test suite. ([issue #110](https://github.com/garlik/4store/issues/110))
  - QueryEndPoint: http://localhost:8088/sparql/
  - UpdateEndPoint: http://localhost:8088/update/
  - DataEndpoint:
  - DefaultGraph: http://example.org/myFourGraph
  - Comments: apt-get install 4store

- Sesame
  - Connector: Sesame
  - QueryEndPoint: http://localhost:8080/openrdf-sesame/repositories/test-smw
  - UpdateEndPoint: http://localhost:8080/openrdf-sesame/repositories/test-smw/statements
  - DataEndpoint:
  - DefaultGraph:
  - Comments: `test-smw` is specified as native in-memory store

- Blazegraph
  - Connector: Blazegraph
  - QueryEndPoint: http://localhost:9999/bigdata/namespace/kb/sparql
  - UpdateEndPoint: http://localhost:9999/bigdata/namespace/kb/sparql
  - DataEndpoint:
  - DefaultGraph:
  - Comments: java -server -Xmx4g -Dbigdata.propertyFile=$BASE_PATH/tests/travis/blazegraph-store.properties -jar bigdata-bundled.jar

[fuseki]: https://jena.apache.org/
[virtuoso]: https://github.com/openlink/virtuoso-opensource
[4store]: https://github.com/garlik/4store

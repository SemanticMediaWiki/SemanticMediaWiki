# ElasticStore

[Requirements](#requirements) | [Features](#features) | [Usage](#usage) | [Settings](#settings) | [Technical notes](#technical-notes) | [FAQ](#faq)

The `ElasticStore` provides a framework to replicate Semantic MediaWiki related data to an Elasticsearch cluster and enable its `QueryEngine` to send `#ask` requests and retrieve information from Elasticsearch (aka ES) instead of the default `SQLStore`.

The objective is to:

- improve structured and allow unstructured content searches
- extend and improve full-text query support (including sorting of results by [relevancy][es:relevance] or score)
- provide means for a scalability strategy by relying on the ES infrastructure

## Requirements

- Elasticsearch: Recommended 6.1+, Tested with 5.6.6
- Semantic MediaWiki: 3.0+
- [`elasticsearch/elasticsearch`][packagist:es] (PHP ^7.0 `~6.0` or PHP ^5.6.6 `~5.3`)

We rely on the [elasticsearch php-api][es:php-api] to communicate with Elasticsearch and are therefore independent from any other vendor or MediaWiki extension that may use ES as search backend (e.g. `CirrusSearch`).

It is recommended to use:

- ES 6+ due to improvements to its [sparse field][es:6] handling
- ES hardware with "... machine with 64 GB of RAM is the ideal sweet spot, but 32 GB and 16 GB machines are also common ..." as noted in [elasticsearch guide][es:hardware]

### Why Elasticsearch?

- It it is relatively easy to install and run an ES instance (also on not recommended hardware).
- ES allows to scale its cluster horizontally without requiring changes to Semantic MediaWiki or its query engine.
- It is more likely that a user in a MediaWiki environment can provided access to an ES instance than to a `SPARQL` triple store (or a SORL/Lucence backend).

## Features

- Handle property type changes without the need to rebuild the entire index itself after it is ensured that all `ChangePropagation` jobs have been processed
- Inverse queries are supported (e.g. `[[-Foo::Bar]]`)
- Property chains and paths queries are supported (e.g. `[[Foo.Bar::Foobar]]`)
- Category and property hierarchies are supported

ES is not expected to be used as data store and therefore it is not assumed that ES returns any `_source` fields or any other data object (exception is the highlighting) besides those document IDs that match a query condition.

The `ElasticStore` provides a customized serialization format to transform and transfer data, an interpreter (see  [domain language][es:dsl]) allows `#ask` queries to be answered by an ES instance.

## Usage

The objective is to use Elasticsearch as drop-in replacement for the existing `SQLStore` based query answering but before it can provide this functionality, some settings and user actions are required:

- Set `$GLOBALS['smwgDefaultStore'] = 'SMWElasticStore';`
- Set `$GLOBALS['smwgElasticsearchEndpoints'] = [ ... ];`
- Run `php setupStore.php` or `php update.php`
- Rebuild the index using `php rebuildElasticIndex.php`

For ES specific settings, please consult the [elasticsearch][es:conf] manual.

### Indexing, updates, and refresh intervals

Updates to an ES index happens instantaneously during a page action to guarantee that queries can use the latest available data set.

The [ES index][es:create:index] page has details about the ES index creation process with Semantic MediaWiki providing two index types:

- the `data` index that hosts all user-facing queryable data (structured and unstructured content) and
- the `lookup` index to store queries used for concept, property path, and inverse match computations

#### Indexing

The `rebuildElasticIndex.php` script is provided as method to replicate existing data from the `SQLStore` (fetches information directly from the property tables) to the ES backend instead of reparsing all content using the MW parser. The script operates in a [rollover mode][es:alias-zero] which is if there is already an existing index, a new index with a different version is created, leaving the current active index untouched and allowing queries to continue to operate while the re-index process is ongoing. Once completed, the new index switches places with the old index and is removed from the ES cluster at this point.

It should be noted that __active replication__ is paused for the duration of the rebuild in order for changes to be processed after the re-index has been completed. It is __obligatory__ to run the job scheduler after the completion of the task to process any outstanding jobs.

#### Safe replication

The `ElasticStore` by default is set to a safe replication mode which entails that if during a page storage __no__ connection could be established to an ES cluster, a `smw.elasticIndexerRecovery` job is planned for changes that were not replicated. These jobs should be executed on a regular basis to ensure that data are kept in sync with the backend.

The `job.recovery.retries` setting is set to a maximum of retry attempts in case the job itself cannot establish a connection after which the job is canceled even though it could __not__ recover.

#### Refresh interval

The [`refresh_interval`][es:indexing:speed] dictates how often Elasticsearch creates new [segments][stack:segments] and it set to `1s` as default. During the rebuild process the setting is changed to `-1` as recommended by the [documentation][es:indexing:speed]. If for some reason (aborted rebuild, exception etc.) the `refresh_interval` remained at `-1` then changes to an index will not be visible until a refresh has been commanded and to fix the situation it is suggested to run:

- `php rebuildElasticIndex.php --update-settings`
- `php rebuildElasticIndex.php --force-refresh`

### Querying and searching

`#ask` queries are system agnostic which means queries that worked with the `SQLStore` (or `SPARQLStore`) should equally work with ES without having to modify the query or its syntax.

The `ElasticStore` has set its query execution to a `compat.mode` where queries are expected to return the same results as when one would use the `SQLStore`. In some instances ES could provide different result set especially in connection with boolean query operators but the `compat.mode` allows us to use the `SQLStore` and the `ElasticStore` interchangeably.

#### Filter and query context

Most searches will be classified as [structure searches][es:structured:search] that operate within a [filter context][es:filter:context] while full-text or proximity searches use a [query context][es:query:context] that attributes to the overall relevancy score.

* `[[Has page::Foo]]` (filter context) to match entities with value `Foo` and the property `Has page`

#### Relevancy and scores

[Relevancy][es:relevance] sorting is a topic on its own (and only provided by ES and the `ElasticStore`) and will only be noted briefly. In order to sort results by a score, the `#ask` query needs to signal that a different context is required during the execution and will only be used when the `es.score` sortkey (see `score.sortfield`) is declared within a non-filtered context.

<pre>
{{#ask: [[Has text::~*some text in a document*]]
 |sort=es.score
 |order=desc
}}

or

{{#ask: [[Has text::in:some text in a document]]
 |sort=es.score
 |order=desc
}}

or

{{#ask: [[Has text::phrase:some text in a document]]
 |sort=es.score
 |order=desc
}}
</pre>

Sorting results by relevancy makes only sense for query constructs that use a non-filtered context (`~/!~`) otherwise scores for matching documents will not be distinguishable and not contribute to a meaningful overall score.

#### Property chains, paths, and subqueries

ES doesn't support [subqueries][es:subqueries] or [joins][es:joins] but in order to execute a path or chain of properties it is necessary to create a set of results that match a path condition (e.g. `Foo.bar.foobar`) with each element holding a restricted list of results from the previous execution to create a path traversal process.

To match the `SQLStore` behaviour in terms of path queries, the `QueryEnfine`splits the path and executes each part individually to compute a list of elements as input for the next iteration. To avoid issues with a possible too large result set, SMW needs to "park" those results and the `subquery.terms.lookup.index.write.threshold` setting (default is 100) defines as to when to the ES [terms lookup][es:terms-lookup] feature by moving results into a separate `lookup` index.

## Settings

To help tune and customize various configuration aspects two settings are provided:

- `$smwgElasticsearchEndpoints`
- `$smwgElasticsearchConfig`
- `$smwgElasticsearchProfile`

### Endpoints

This setting contains a list of available endpoints used by the ES cluster and is __required__ to be set in order to establish a connection with ES.

<pre>
$GLOBALS['smwgElasticsearchEndpoints'] = [
	[ 'host' => '192.168.1.126', 'port' => 9200, 'scheme' => 'http' ],
	'localhost:9200'
];
</pre>

Please consult the [reference material][es:conf:hosts] for details about the correct notation form.

### Config

The `$smwgElasticsearchConfig` setting is a compound that collects various settings related to the ES connection, index, and query details.

<pre>
$GLOBALS['smwgElasticsearchConfig'] = [

	// Points to index and mapping definition files
	'index_def'       => [ ... ],

	// Defines connection details for ES endpoints
	'connection'  => [ ... ],

	// Holds replication details
	'indexer' => [ ... ],

	// Used to modify ES specific settings
	'settings'    => [ ... ],

	// Section to optimization the query execution
	'query'       => [ ... ]
];
</pre>

A detailed list of settings and their explanations are available in the `DefaultSettings.php`. Please make sure that after changing any setting, `php rebuildElasticIndex.php --update-settings` is executed.

When modifying a particular setting, use an appropriate key to change the value of a parameter otherwise it is possible that the entire configuration is replaced.

<pre>
// Uses a specific key and therefore replaces only the specific parameter
$GLOBALS['smwgElasticsearchConfig']['query']['uri.field.case.insensitive'] = true;

// Replaces the entire configuration
$GLOBALS['smwgElasticsearchConfig'] = [
	'query' => [
		'uri.field.case.insensitive' => true
	]
];
</pre>

#### Shards and replicas

The default shards and replica configuration is specified with:

- The `data` index has two primary shards and two replicas
- The `lookup` index has one primary shard and no replica with the documentation noting that "... consider using an index with a single shard ... lookup terms filter will prefer to execute the get request on a local node if possible ..."

If it is required to change the numbers of [shards][es:shards] and replicas then use the `$smwgElasticsearchConfig` setting.

<pre>
$GLOBALS['smwgElasticsearchConfig']['settings']['data'] = [
	'number_of_shards' => 3,
	'number_of_replicas' => 3
]
</pre>

ES comes with a precondition that any change to the `number_of_shards` requires to rebuild the entire index, so changes to that setting should be considered carefully.

Read-heavy wikis might want to add (without the need re-index the data) replica shards at the time ES performance is in decline but those replicas should be put on an extra hardware.

#### Text, languages, and analyzers

The `data` index uses the `smw-data-standard.json` to define settings and mappings that influence how ES analyzes and index documents including fields identified as text and strings. Those text fields use the [standard analyzer][es:standard:analyzer] and should work for most applications.

Yet, for certain languages the `icu` (or any other language specific configuration) might provide better results therefore it possible to assign a different definition file that allows custom settings such as language [analyzer][es:lang:analyzer] to help increase the matching precision.

`smw-data-icu.json` is provided as example on how to alter those settings. Please note that query results on text fields may differ compared to when one would use the standard analyzer and it should be evaluated what settings are the most favorable for a user environment.

For a non-latin language environments it is recommended to add the [analysis-icu plugin][es:icu:tokenizer] and select `smw-data-icu.json` as index definition (see also the [unicode normalization][es:unicode:normalization] guide) to provide better unicode normalization and [case folding][es:unicode:case:folding].

Please note that any change to the index or analyzer settings __requires__ to rebuild the entire index.

### Profile

`$smwgElasticsearchProfile` is provided to simplify the maintenance of configuration parameters by linking to a JSON file that hosts and hereby alters individual settings.

<pre>
{
	"indexer": {
		"raw.text": true
	},
	"query": {
		"uri.field.case.insensitive": true
	}
}
</pre>

The profile is loaded last and will override any default or individual settings made in `$smwgElasticsearchConfig`.

## Technical notes

Classes and objects related to the Elasticsearch interface and implementation are placed under the `SMW\Elastic` namespace.

<pre>
SMW\Elastic
┃	┠━ Admin         # Classes used to extend `Special:SemanticMediaWiki`
┃	┠━ Connection    # Responsible for building a connection to ES
┃	┠━ Indexer       # Contains all necessary classes for updating the ES index
┃	┕━ QueryEngine   # Hosts the query builder and `#ask` language interpreter classes
┠━ ElasticFactory
┕━ ElasticStore
</pre>

### Field mapping and serialization

![image](https://user-images.githubusercontent.com/1245473/36046618-e32e7a78-0e1c-11e8-90bb-5bee5650789f.png)

It should remembered that besides specific available types in ES, text fields are generally divided into analyzed and not_analyzed fields.

Semantic MediaWiki is [mapping][es:mapping] its internal structure using [`dynamic_templates`][es:dynamic:templates] to define expected data types, their attributes, and possibly add extra index fields (see [multi-fields][es:multi-fields]) to make use of certain query constructs.

The naming convention follows a very pragmatic naming scheme, `P:<ID>.<type>Field` with each new field (aka property) being mapped dynamically to a corresponding field type.

- `P:<ID>` identifies the property with a number which is the same as the internal ID in the `SQLStore` (`smw_id`)
- `<type>Field` declares a typed field (e.g. `txtField` which is important in case the type changes from `wpg` to `txt` and vice versa) and holds the actual indexable data.
- Dates are indexed using the julian day number (JDN) to allow for historic dates being applicable

The `SemanticData` object is always serialized in its entirety to avoid the interface to keep delta information. Furthermore, ES itself creates always a new index document for each update therefore keeping deltas wouldn't make much difference for the update process. A complete object has the advantage to use the [bulk][es:bulk] updater making the update faster and more resilient while avoiding document comparison during an update process.

To allow for exact matches as well as full-text searches on the same field most mapped fields will have at least two or three additional [multi-field][es:multi-fields] elements to store text as `not_analyzed` (or keyword) and as sortable entity.

* The `text_copy` mapping (see [copy-to][es:copy-to]) is used to enable wide proximity searches on textual annotated elements. For example, `[[in:foo bar]]` (eq. `[[~~foo bar]]`) translates into "Find all entities that have `foo bar` in one of its assigned `_uri`, `_txt`, or `_wpg` properties. The `text_copy` field is a compound field for all strings to be searched when a specific property is unknown.
* The `text_raw` (requires `indexer.raw.text` to be set `true`) contains unstructured and unprocessed raw text from an article so that it can be used in combination with the proximity operators `[[in:lorem ipsum]]` and `[[phrase:lorem ipsum]]`.

### Monitoring

To make it easier for administrators to monitor the interface between Semantic MediaWiki and ES, several service links are provided for a convenient access to selected information.

The main access point is defined with `Special:SemanticMediaWiki/elastic` but only users with the `smw-admin` right (which is required for the `Special:SemanticMediaWiki` page) can access the information and only when an ES cluster is available.

### Logging

The enable connector specific logging, please use the `smw-elastic` identifier in your LocalSettings.

<pre>
$wgDebugLogGroups  = [
	'smw-elastic' => ".../logs/smw-elastic-{$wgDBname}.log",
];
</pre>

## FAQ

> Why not combine the `SQLStore` and ES search where ES only handles the text search?

The need to support ordering of results requires that the ordering happens over the entire set of conditions and matches it is not possible to split a search between two systems while retaining consistency for the offset (from where result starts and end) pointer.

> Why not use ES as a replacement?

Because ES is a search engine and not a storage backend therefore the data storage and management remains part of the `SQLStore`. The `SQLStore` is responsible for creating IDs, storing data objects, and provide answers to requests that doesn't involve the `QueryEngine`.

> Limit of total fields [3000] in index [...] has been exceeded

If the rebuilder or ES returns with a similar message then the preconfigured limit needs to be changed which is most likely caused by an excessive use of property declarations. The user should question such usage patterns and analyze why so many properties are used and whether or not some can
be merged or properties are in fact misused as fact statements.

The limit is set to prevent [mapping explosion][es:map:explosion] but can be readjusted using the [index.mapping.total_fields.limit][es:mapping] (maximum number of fields in an index) setting.

<pre>
$GLOBALS['smwgElasticsearchConfig']['settings']['data'] = [
	'index.mapping.total_fields.limit' => 6000
];
</pre>

After changing any setting, ensure to run `php rebuildElasticIndex.php --update-settings`.

> Your version of PHP / json-ext does not support the constant 'JSON_PRESERVE_ZERO_FRACTION', which is important for proper type mapping in Elasticsearch. Please upgrade your PHP or json-ext.

[elasticsearch-php#534](https://github.com/elastic/elasticsearch-php/issues/534) has some details about the issue. Please check the [version matrix][es:version:matrix] to see which version is compatible with your PHP environment.

> "Connection.php: {"error":{"root_cause":[{"type":"parse_exception","reason":"No processor type exists with name [attachment]","header":{"processor_type":"attachment"}}] ..."

The file indexer (`experimental.file.ingest`) was enabled but the required ES [ingest-plugin][es:ingest] was not installed.

> I use CirrusSearch, can I search SMW (or its data) via CirrusSearch?

No, because first of all SMW doesn't rely on CirrusSearch at all and even if a user has CirrusSearch installed bot extensions have different requirements and different indices and are not designed to share content with each other.

> Can I use `Special:Search` with SMW and CirrusSearch?

Yes, by adding `$wgSearchType = 'SMWSearch';` one can use the `#ask` syntax (e.g. `[[Has date::>1970]]`) and execute structured searchs while any free input gets redirected to CirrusSearch. The input is an either/or not a conjunctive one which means only one of the both can be used at once through the `Special:Search` interface.

### Glossary

- `Document` is called in ES a content container to holds indexable content and is equivalent to an entity (subject) in Semantic MediaWiki
- `Index` holds all documents within a collection of types and contains inverted indices to search across everything within those documents at once
- `Node` is a running instance of Elasticsearch
- `Cluster` is a group of nodes

### Other recommendations

- Analysis ICU ( tokenizer and token filters from the Unicode ICU library), see `bin/elasticsearch-plugin install analysis-icu`
- A [curated list](https://github.com/dzharii/awesome-elasticsearch) of useful resources about elasticsearch including articles, videos, blogs, tips and tricks, use cases
- [Elasticsearch: The Definitive Guide](http://oreilly.com/catalog/errata.csp?isbn=9781449358549) by Clinton Gormley and Zachary Tonge should provide insights in how to run and use Elasticsearch
- [10 Elasticsearch metrics to watch][oreilly:es-metrics-to-watch] describes key metrics to keep Elasticsearch running smoothly

[es:conf]: https://www.elastic.co/guide/en/elasticsearch/reference/6.1/system-config.html
[es:conf:hosts]: https://www.elastic.co/guide/en/elasticsearch/client/php-api/6.0/_configuration.html#_extended_host_configuration
[es:php-api]: https://www.elastic.co/guide/en/elasticsearch/client/php-api/6.0/_installation_2.html
[es:joins]: https://github.com/elastic/elasticsearch/issues/6769
[es:subqueries]: https://discuss.elastic.co/t/question-about-subqueries/20767/2
[es:terms-lookup]: https://www.elastic.co/blog/terms-filter-lookup
[es:dsl]: https://www.elastic.co/guide/en/elasticsearch/reference/6.1/query-dsl.html
[es:mapping]: https://www.elastic.co/guide/en/elasticsearch/reference/6.1/mapping.html
[es:multi-fields]: https://www.elastic.co/guide/en/elasticsearch/reference/current/multi-fields.html
[es:map:explosion]: https://www.elastic.co/blog/found-crash-elasticsearch#mapping-explosion
[es:indexing:speed]: https://www.elastic.co/guide/en/elasticsearch/reference/current/tune-for-indexing-speed.html
[es:create:index]: https://www.elastic.co/guide/en/elasticsearch/reference/current/indices-create-index.html
[es:dynamic:templates]: https://www.elastic.co/guide/en/elasticsearch/reference/6.1/dynamic-templates.html
[es:version:matrix]: https://www.elastic.co/guide/en/elasticsearch/client/php-api/6.0/_installation_2.html#_version_matrix
[es:hardware]: https://www.elastic.co/guide/en/elasticsearch/guide/2.x/hardware.html#_memory
[es:standard:analyzer]: https://www.elastic.co/guide/en/elasticsearch/reference/current/analysis-standard-analyzer.html
[es:lang:analyzer]: https://www.elastic.co/guide/en/elasticsearch/reference/current/analysis-lang-analyzer.html
[es:icu:tokenizer]: https://www.elastic.co/guide/en/elasticsearch/plugins/6.1/analysis-icu-tokenizer.html
[es:unicode:normalization]: https://www.elastic.co/guide/en/elasticsearch/guide/current/unicode-normalization.html
[es:unicode:case:folding]: https://www.elastic.co/guide/en/elasticsearch/guide/current/case-folding.html
[es:shards]: https://www.elastic.co/guide/en/elasticsearch/reference/current/_basic_concepts.html#getting-started-shards-and-replicas
[es:alias-zero]: https://www.elastic.co/guide/en/elasticsearch/guide/master/index-aliases.html
[es:bulk]: https://www.elastic.co/guide/en/elasticsearch/reference/6.2/docs-bulk.html
[es:structured:search]: https://www.elastic.co/guide/en/elasticsearch/guide/current/structured-search.html
[es:filter:context]: https://www.elastic.co/guide/en/elasticsearch/reference/6.2/query-filter-context.html
[es:relevance]: https://www.elastic.co/guide/en/elasticsearch/guide/master/relevance-intro.html
[es:copy-to]: https://www.elastic.co/guide/en/elasticsearch/reference/master/copy-to.html
[oreilly:es-metrics-to-watch]: https://www.oreilly.com/ideas/10-elasticsearch-metrics-to-watch
[stack:segments]: https://stackoverflow.com/questions/15426441/understanding-segments-in-elasticsearch
[es:6]: https://www.elastic.co/blog/minimize-index-storage-size-elasticsearch-6-0
[packagist:es]:https://packagist.org/packages/elasticsearch/elasticsearch
[es:ingest]:https://www.elastic.co/guide/en/elasticsearch/plugins/master/ingest-attachment.html

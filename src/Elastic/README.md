# ElasticStore

[Requirements](#requirements) | [Features](#features) | [Usage](#usage) | [Settings](#settings) | [Technical notes](#technical-notes) | [FAQ](#faq)

The `ElasticStore` provides a framework to replicate Semantic MediaWiki related data to an Elasticsearch cluster and enable its `QueryEngine` to send `#ask` requests and retrieve information from Elasticsearch (aka ES) instead of the default `SQLStore`.

The objective is to:

- improve structured and allow unstructured content searches
- extend and improve full-text query support (including sorting of results by [relevancy][es:relevance])
- provide means for a scalability strategy by relying on the ES infrastructure

## Requirements

- Elasticsearch: Recommended 6.1+, Tested with 5.6.6
- Semantic MediaWiki: 3.0+
- [`elasticsearch/elasticsearch`][packagist:es] (PHP ^7.0 `~6.0` or PHP ^5.6.6 `~5.3`)

We rely on the [elasticsearch php-api][es:php-api] to communicate with Elasticsearch and are therefore independent from any other vendor or MediaWiki extension that may use ES as search backend (e.g. `CirrusSearch`).

It is recommended to use:

- ES 6+ due to improvements to its [sparse field][es:6] handling
- ES hardware with "... machine with 64 GB of RAM is the ideal sweet spot, but 32 GB and 16 GB machines are also common ..." as noted in the [elasticsearch guide][es:hardware]

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

This [page][es:create:index] decribes the index creation process where Semantic MediaWiki provides two index types:

- the `data` index that hosts all user-facing queryable data (structured and unstructured content) and
- the `lookup` index to store queries used for concept, property path, and inverse match computations

#### Indexing

The `rebuildElasticIndex.php` script is provided as method to replicate existing data from the `SQLStore` (fetches information directly from the property tables) to the ES backend instead of reparsing all content using the MW parser. The script operates in a [rollover mode][es:alias-zero] which is if there is already an existing index, a new index with a different version is created, leaving the current active index untouched and allowing queries to continue to operate while the index process is ongoing. Once completed, the new index switches places with the old index and is removed from the ES cluster at this point.

It should be noted that __active replication__ is paused for the duration of the rebuild in order for changes to be processed after the re-index has been completed. It is __obligatory__ to run the job scheduler after the completion of the task to process any outstanding jobs.

#### Safe replication

The `ElasticStore` by default is set to a safe replication mode which entails that if during a page storage __no__ connection could be established to an ES cluster, a `smw.elasticIndexerRecovery` job is planned for changes that were not replicated. These jobs should be executed on a regular basis to ensure that data are kept in sync with the backend.

The `job.recovery.retries` setting is set to a maximum of retry attempts in case the job itself cannot establish a connection after which the job is canceled even though it could __not__ recover.

#### Refresh interval

The [`refresh_interval`][es:indexing:speed] dictates how often Elasticsearch creates new [segments][stack:segments] and it set to `1s` as default. During the rebuild process the setting is changed to `-1` as recommended by the [documentation][es:indexing:speed]. If for some reason (aborted rebuild, exception etc.) the `refresh_interval` remained at `-1` then changes to an index will not be visible until a refresh has been commanded and to fix the situation it is suggested to run:

- `php rebuildElasticIndex.php --update-settings`
- `php rebuildElasticIndex.php --force-refresh`

### Querying and searching

`#ask` queries are system agnostic meaning that queries that worked with the `SQLStore` (or `SPARQLStore`) are expected to work equally with `ElasticStore` and not requiring any modifications to a query or its syntax.

The `ElasticStore` has set its query execution to a `compat.mode` where queries are expected to return the same results as the `SQLStore`. In some instances ES could provide a different result set especially in connection with boolean query operators but the `compat.mode` warrants consistency among results retrieved from the `ElasticStore` in comparison to the `SQLStore` especially when running the same set of integration tests against each store.

#### Filter and query context

Most searches with a discrete value in Semantic MediaWiki will be classified as [structured search][es:structured:search] that operates with a [filter context][es:filter:context] while full-text or proximity searches use a [query context][es:query:context] that attributes to a relevancy score. A filter context will always yield a `1` relevancy score as it is translated into on a boolean operation which either matches or neglects a result as part of a set.

* `[[Has page::Foo]]` (filter context) to match entities with value `Foo` for the property `Has page`
* `[[Has page::~*Foo*]]` (query context) to match entities with any value that contains `Foo` (and `FOO`,`foo` etc. ) for the `Has page` property

To improve the handling of proximity searches the following expression can be used.

Expression | Interpret as | Description | Note
------------ | ------------- | ------------- | -------------
`in: ...`  |  `~~* ... *` or `~* ... *` | Find anything that contains `...` | The `in:` expression can also be combined with a property and depending on the type context is interpret differently.
`phrase: ...` |  `~~" ... "` or `~" ... "` | Find anything that contains `...` in the exact same order | The `phrase:` expression is only relevant for literal components such as text or page titles as well as unstructured text.
`not: ...` | `!~~...` or `!~...` | Do not match any entity that matches `...` | The `not:` expression is intended to only match the exact entered term. It can be extended using `*` if necessary (e.g. `[[Has text::not:foo*]]`)

A wide proximity is expressed with `~~` and the intent to search where a specific property is unknown (in case of ES it can expand the search radius to fields that have not been annotated or processed by Semantic MediaWiki prior a query request, see `indexer.raw.text` and `experimental.file.ingest`)

Type |  #ask | Interpret as
------------ | ------------ | -------------
\- | `[[in:some foo]]` | `[[~~*some foo*]]`
Text | `[[Has text::in:some foo]]` | `[[Has text::~*some foo*]]`
Page | `[[Has page::in:foo]]` | `[[Has text::~*foo*]]`
Number | `[[Has number::in:99]]` | `[[Has number:: [[≥0]] [[≤99]] ]]`
&nbsp; | `[[Has number::in:-100]]` | `[[Has number:: [[≥-100]] [[≤0]] ]]`
Time | `[[Has date::in:2000]]` | `[[Has date:: <q>[[≥2000]] [[<<1 January 2001 00:00:00]]</q> ]]`

#### Relevancy and scores

[Relevancy][es:relevance] sorting is a topic of its own (and is only provided by ES and the `ElasticStore`). In order to sort results by a score, the `#ask` query needs to signal that a different context is required during the query execution. The `es.score` sortkey (see `score.sortfield` and is used as convention key) signals to the `QueryEngine` that for a non-filtered context score tracking is to be enabled.

Only query constructs that use a non-filtered context (`~/!~/in/phrase/not`) provide meaningful scores that are expressive enough for sorting results otherwise results will not be distinguishable and not contribute to a meaningful overall sorting experience.

<pre>
// Find entities that contains "some text" in the property `Has text` and sort
// by its score returned from each matched document

{{#ask: [[Has text::in:some text]]
 |sort=es.score
 |order=desc
}}
</pre>

#### Property chains, paths, and subqueries

ES doesn't support [subqueries][es:subqueries] or [joins][es:joins] natively but the `ElasticStore` facilitates the [terms lookup][es:terms-lookup] to execute path or chain of properties and hereby builds an iterative process allowing to create a set of results that match a path condition (e.g. `Foo.bar.foobar`) with each element holding a restricted list of results from the previous execution to traverse the property path.

The introduced process allows to match the `SQLStore` behaviour in terms of path queries where the `QueryEngine` is splitting each path and computes a list of elements. To avoid issues with a possible output of a vast list of matches, Semantic MediaWiki will "park" those results in the `lookup` index with the `subquery.terms.lookup.index.write.threshold` setting (default is 100) directing as to when the results are move into a separate `lookup` index.

#### Hierarchies

Property and category hierarchies are supported by relying on a conjunctive boolean expression for hierarchy members that are computed outside of the ES framework (the ES [parent join][es:parent-join] type is not used for this).

#### Unstructured text

Two experimental settings allow to handle unstructured text (content that does not provide any explicit property value annotations) using a separate field in ES.

##### Raw text

The `indexer.raw.text` setting enables to replicate the entire raw text of a page together with existing annotations so that unprocessed text can be searched in tandem with structured queries.

##### File content

This requires the ES [ingest-attachment plugin][es:ingest] and the ``indexer.experimental.file.ingest` setting.

The [ingest][es:ingest] process provides a method to retrieve content from files and make them available to ES and Semantic MediaWiki without requiring the actual content to be stored within a wiki page.

In case where the ingestions and extraction was successful, a `File attachment` annotation will appear on the specific `File` entity and depending on the extraction quality of ES and Tika additional annotations will be added such as:

- `Content type`,
- `Content author`,
- `Content length`,
- `Content language`,
- `Content title`,
- `Content date`, and
- `Content keyword`

Due to size and memory consumption by ES/Tika, file content ingestions exclusively happens in background using the `smw.elasticFileIngest` job. Only after the job has been executed successfully, aforementioned annotations and file content will be accessible during a query request.

An "unstructured search" (i.e. searching without a property assignment) requires the wide proximity expression which conveniently are available as shortcut using `in:`, `phrase:`, or `not:`.

#### Query debugging

`format=debug` will output a detailed description of the `#ask` and ES DSL used for a query answering making it possible to analyze and retrieve explanations from ES about a query request.

### Special:Search integration

In case [SMWSearch][smw:search] was enabled, it is possible to retrieve [highlighted][es:highlighting] text snippets for matched entities from ES given that `special_search.highlight.fragment.type` is set to one of the excepted types (`plain`, `unified`, and `fvh`). Type `plain` can be used without any specific requirements, for the other types please consult the ES documentation.

## Settings

Accessing an ES cluster from within Semantic MediaWiki requires some settings and customization and includes:

- [`$smwgElasticsearchEndpoints`](https://www.semantic-mediawiki.org/wiki/Help:$smwgElasticsearchEndpoints)
- [`$smwgElasticsearchConfig`](https://www.semantic-mediawiki.org/wiki/Help:$smwgElasticsearchConfig)
- [`$smwgElasticsearchProfile`](https://www.semantic-mediawiki.org/wiki/Help:$smwgElasticsearchProfile)

### Endpoints

`smwgElasticsearchEndpoints` is a __required__ setting and contains a list of available endpoints to create a connection with an ES cluster.

<pre>
$GLOBALS['smwgElasticsearchEndpoints'] = [
	[ 'host' => '192.168.1.126', 'port' => 9200, 'scheme' => 'http' ],
	'localhost:9200'
];
</pre>

Please consult the [reference material][es:conf:hosts] for details about the correct notation form.

### Config

`$smwgElasticsearchConfig` is a compound setting that collects various settings related to connection, index, and query details.

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

	// Section to optimize the query execution
	'query'       => [ ... ]
];
</pre>

A detailed list of settings and their explanations are available in the `DefaultSettings.php`. Please make sure that after changing any setting, `php rebuildElasticIndex.php --update-settings` is executed.

When modifying a particular setting, use an appropriate key to change the value of a parameter otherwise it is possible that the entire configuration is replaced.

<pre>
// Uses a specific key and therefore replaces only the specific parameter
$GLOBALS['smwgElasticsearchConfig']['query']['uri.field.case.insensitive'] = true;

// !!Override!! the entire configuration
$GLOBALS['smwgElasticsearchConfig'] = [
	'query' => [
		'uri.field.case.insensitive' => true
	]
];
</pre>

#### Shards and replicas

A default shards and replica configuration is applied to with:

- The `data` index has two primary shards and two replicas
- The `lookup` index has one primary shard and no replica with the documentation noting that "... consider using an index with a single shard ... lookup terms filter will prefer to execute the get request on a local node if possible ..."

If it is required to change the numbers of [shards][es:shards] and replicas then use the `$smwgElasticsearchConfig` setting.

<pre>
$GLOBALS['smwgElasticsearchConfig']['settings']['data'] = [
	'number_of_shards' => 3,
	'number_of_replicas' => 3
]
</pre>

ES comes with a precondition that any change to the `number_of_shards` requires to rebuild the entire index, so changes to that setting should be made carefully and in advance.

Read-heavy wikis might want to add (without the need re-index the data) replica shards at the time ES performance is in decline. As noted, [replica shards][es:replica-shards] should be put on an extra hardware.

#### Index mappings

By default `index_def` points to the index definition and the `data` index is assigned the `smw-data-standard.json` to define its settings and mappings that influence how ES analyzes and index documents including fields that are identified to contain text and string elements. Those text fields use the [standard analyzer][es:standard:analyzer] and should work for most applications.

The index name will be composed of a prefix such as `smw-data` (or `smw-lookup`), the wikiID, and a version indicator (used by the rollover) so that a single ES cluster can host different indices from different Semantic MediaWiki instances without interfering with each other.

#### Text, languages, and analyzers

For certain languages the `icu` analyzer (or any other language specific configuration) may provide better results therefore `index_def` provides a possibility to change the assignments and hereby allows custom settings such as different language [analyzer][es:lang:analyzer] to be used and increase the likelihood of better matching precision for text elements.

`smw-data-icu.json` is provided as example on how to alter those settings. It should be noted that query results on text fields may differ compared to when one would use the standard analyzer and users are expected to evaluate whether those settings are more favorable or not to a query answering.

Besides the different index mappings, it is recommended for a non-latin language environments to add the [analysis-icu plugin][es:icu:tokenizer] and select `smw-data-icu.json` as index definition (see also the [unicode normalization][es:unicode:normalization] guide) to make use of better unicode normalization and [case folding][es:unicode:case:folding].

Please note that any change to the index or its analyzer settings __requires__ to rebuild the entire index.

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
┃	┠━ Exception
┃	┠━ Connection    # Responsible for building a connection to ES
┃	┠━ Indexer       # Contains all necessary classes for updating the ES index
┃	┕━ QueryEngine   # Hosts the query builder and `#ask` language interpreter classes
┃
┠━ ElasticFactory
┕━ ElasticStore
</pre>

### Field mapping and serialization

<pre>
{
	"_index": "smw-data-mw-30-00-elastic-v1",
	"_type": "data",
	"_id": "334032",
	"_version": 2,
	"_source": {
		"subject": {
			"title": "ABC/20180716/k10011534941000",
			"subobject": "_f21687e8bab0ebee627f71654ddd4bc4",
			"namespace": 0,
			"interwiki": "",
			"sortkey": "foo ..."
		},
		"P:100": {
			"txtField": [
				"Foo bar ..."
			]
		},
		"P:4": {
			"wpgField": [
				"foobar"
			],
			"wpgID": [
				334125
			]
		}
	}
}
</pre>

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
* `attachment.{...}` will be added by the ingest processor

### ES DSL mapping

For example, the ES DSL for a `[[in:lorem ipsum]]` query (find all entities that contains `lorem ipsum`) on structured and unstructured fields will look similar to:

<pre>
"bool": {
    "must": {
        "query_string": {
            "fields": [
                "subject.title^8",
                "text_copy^5",
                "text_raw",
                "attachment.title^3",
                "attachment.content"
            ],
            "query": "*lorem ipsum*",
            "minimum_should_match": 1
        }
    }
}
</pre>

The term `lorem ipsum` will be queried in different fields with different boost factors to highlight preferences when a term is among a title or only part of a text field.

A request for a structured term (assigned to a property e.g. `[[Has text::lorem ipsum]]`) will generate a different ES DSL query.

<pre>
"bool": {
    "filter": {
        "term": {
            "P:100.txtField.keyword": "lorem ipsum"
        }
    }
}
</pre>

While `P:100.txtField` contains the text component that is assigned to `Has text` and by default is an analyzed field, the `keyword` field is selected to execute the query on a not analyzed content to match the exact term. Exact term matching means that the matching process distinguishes between `lorem ipsum` and `Lorem ipsum`.


On the contrary, a proximity request (e.g. `[[Has text::~lorem ipsum*]]`) has different requirements including case folding, lower, and upper case matching and therefore includes the analyzed field with an ES DSL output that is comparable to:

<pre>
"bool": {
    "must": {
        "query_string": {
            "fields": [
                "P:100.txtField",
                "P:100.txtField.keyword"
            ],
            "query": "lorem +ipsum*"
        }
    }
}
</pre>

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

The need to support ordering of results requires that the sorting needs to happen over the entire set of results that match a condition. It is not possible to split a search between two systems while retaining consistency for the offset (from where result starts and end) pointer.

> Why not use ES as a replacement?

Because at this point of implementation ES is used search engine and not a storage backend therefore the data storage and management remains part of the `SQLStore`. The `SQLStore` is responsible for creating IDs, storing data objects, and provide answers to requests that doesn't involve the `QueryEngine`.

> Limit of total fields [3000] in index [...] has been exceeded

If the rebuilder or ES returns with a similar message then the preconfigured limit needs to be changed which is most likely caused by an excessive use of property declarations. The user should question such usage patterns and analyze why so many properties are used and whether or not some can
be merged or properties are in fact misused as fact statements.

The limit is set to prevent [mapping explosion][es:map:explosion] but can be readjusted using the [index.mapping.total_fields.limit][es:mapping] (maximum number of fields in an index) setting.

<pre>
$GLOBALS['smwgElasticsearchConfig']['settings']['data'] = [
	'index.mapping.total_fields.limit' => 6000
];
</pre>

After changing those settings, ensure to run `php rebuildElasticIndex.php --update-settings`.

> Your version of PHP / json-ext does not support the constant 'JSON_PRESERVE_ZERO_FRACTION', which is important for proper type mapping in Elasticsearch. Please upgrade your PHP or json-ext.

[elasticsearch-php#534](https://github.com/elastic/elasticsearch-php/issues/534) has some details about the issue. Please check the [version matrix][es:version:matrix] to see which version is compatible with your PHP environment.

> "Connection.php: {"error":{"root_cause":[{"type":"parse_exception","reason":"No processor type exists with name [attachment]","header":{"processor_type":"attachment"}}] ..."

The file indexer (`experimental.file.ingest`) was enabled but the required ES [ingest-plugin][es:ingest] was not installed.

> I use CirrusSearch, can I search SMW (or its data) via CirrusSearch?

No, because first of all SMW doesn't rely on CirrusSearch at all and even if a user has CirrusSearch installed both extensions have different requirements and different indices and are not designed to share content with each other.

> Can I use `Special:Search` together with SMW and CirrusSearch?

Yes, by adding `$wgSearchType = 'SMWSearch';` one can use the `#ask` syntax (e.g. `[[Has date::>1970]]`) and execute structured or unstructured searches. Using the [extended profile](https://www.semantic-mediawiki.org/wiki/Help:SMWSearch/Extended_profile) or `#ask` constructs a search input that will retrieved results via Semantic MediaWiki (and hereby ES).

### Glossary

- `Document` is called in ES a content container to holds indexable content and is equivalent to an entity (subject) in Semantic MediaWiki
- `Index` holds all documents within a collection of types and contains inverted indices to search across everything within those documents at once
- `Node` is a running instance of Elasticsearch
- `Cluster` is a group of nodes

### Other recommendations

- Analysis ICU ( tokenizer and token filters from the Unicode ICU library), see `bin/elasticsearch-plugin install analysis-icu`
- A [curated list](https://github.com/dzharii/awesome-elasticsearch) of useful resources about elasticsearch including articles, videos, blogs, tips and tricks, use cases
- [Elasticsearch: The Definitive Guide](http://shop.oreilly.com/product/0636920028505.do) by Clinton Gormley and Zachary Tonge should provide insights in how to run and use Elasticsearch
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
[es:query:context]: https://www.elastic.co/guide/en/elasticsearch/reference/6.2/query-filter-context.html
[es:relevance]: https://www.elastic.co/guide/en/elasticsearch/guide/master/relevance-intro.html
[es:copy-to]: https://www.elastic.co/guide/en/elasticsearch/reference/master/copy-to.html
[oreilly:es-metrics-to-watch]: https://www.oreilly.com/ideas/10-elasticsearch-metrics-to-watch
[stack:segments]: https://stackoverflow.com/questions/15426441/understanding-segments-in-elasticsearch
[es:6]: https://www.elastic.co/blog/minimize-index-storage-size-elasticsearch-6-0
[packagist:es]:https://packagist.org/packages/elasticsearch/elasticsearch
[es:ingest]:https://www.elastic.co/guide/en/elasticsearch/plugins/master/ingest-attachment.html
[es:parent-join]: https://www.elastic.co/guide/en/elasticsearch/reference/current/parent-join.html
[es:replica-shards]:https://www.elastic.co/guide/en/elasticsearch/guide/current/replica-shards.html
[es:highlighting]: https://www.elastic.co/guide/en/elasticsearch/reference/current/search-request-highlighting.html
[smw:search]: https://www.semantic-mediawiki.org/wiki/Help:SMWSearch

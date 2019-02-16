# Usage

Use Elasticsearch as drop-in replacement for the existing `SQLStore` based query answering  and requires the following settings to be changed or set:

- Set `$GLOBALS['smwgDefaultStore'] = 'SMWElasticStore';`
- Set `$GLOBALS['smwgElasticsearchEndpoints'] = [ ... ];`
- Run `php setupStore.php` or `php update.php`
- Rebuild the index using `php rebuildElasticIndex.php`

For ES specific settings, please consult the [elasticsearch][es:conf] manual.

## Indexing, updates, and refresh intervals

Updates to an ES index happens instantaneously during a page action to guarantee that queries can use the latest available data set.

This [page][es:create:index] decribes the index creation process where Semantic MediaWiki provides two index types:

- the `data` index that hosts all user-facing queryable data (structured and unstructured content) and
- the `lookup` index to store queries used for concept, property path, and inverse match computations

### Indexing

The `rebuildElasticIndex.php` script is provided as method to replicate existing data from the `SQLStore` (fetches information directly from the property tables) to the ES backend instead of reparsing all content using the MW parser. The script operates in a [rollover mode][es:alias-zero] which is if there is already an existing index, a new index with a different version is created, leaving the current active index untouched and allowing queries to continue to operate while the index process is ongoing. Once completed, the new index switches places with the old index and is removed from the ES cluster at this point.

It should be noted that __active replication__ is paused for the duration of the rebuild in order for changes to be processed after the re-index has been completed. It is __obligatory__ to run the job scheduler after the completion of the task to process any outstanding jobs.

### Safe replication

The `ElasticStore` by default is set to a safe replication mode which entails that if during a page storage __no__ connection could be established to an ES cluster, a `smw.elasticIndexerRecovery` job is planned for changes that were not replicated. These jobs should be executed on a regular basis to ensure that data are kept in sync with the backend.

The `job.recovery.retries` setting is set to a maximum of retry attempts in case the job itself cannot establish a connection after which the job is canceled even though it could __not__ recover.

### Refresh interval

The [`refresh_interval`][es:indexing:speed] dictates how often Elasticsearch creates new [segments][stack:segments] and it set to `1s` as default. During the rebuild process the setting is changed to `-1` as recommended by the [documentation][es:indexing:speed]. If for some reason (aborted rebuild, exception etc.) the `refresh_interval` remained at `-1` then changes to an index will not be visible until a refresh has been commanded and to fix the situation it is suggested to run:

- `php rebuildElasticIndex.php --update-settings`
- `php rebuildElasticIndex.php --force-refresh`

## Querying and searching

`#ask` queries are system agnostic meaning that queries that worked with the `SQLStore` (or `SPARQLStore`) are expected to work equally with `ElasticStore` and not requiring any modifications to a query or its syntax.

The `ElasticStore` has set its query execution to a `compat.mode` where queries are expected to return the same results as the `SQLStore`. In some instances ES could provide a different result set especially in connection with boolean query operators but the `compat.mode` warrants consistency among results retrieved from the `ElasticStore` in comparison to the `SQLStore` especially when running the same set of integration tests against each store.

### Filter and query context

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

### Relevancy and scores

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

### Property chains, paths, and subqueries

ES does not support [subqueries][es:subqueries] or [join][es:joins] constructs natively but it provides a so called [terms lookup][es:terms-lookup] which we faciliate to execute a path (chain of properties) building an iterative process allowing to create a set of results that match a path condition (e.g. `Foo.bar.foobar`) with each element holding a restricted list of results from the previous execution to traverse the property path.

The introduced process allows to match the `SQLStore` behaviour in terms of path queries where the `QueryEngine` splits each path and computes a list of elements. To avoid issues with a vast list of matches, Semantic MediaWiki will "park" those results in the `lookup` index with the `subquery.terms.lookup.index.write.threshold` setting (default is 100) directing as to when the results are moved into the separate `lookup` index.

### Hierarchies

Property and category hierarchies are supported by relying on a conjunctive boolean expression for hierarchy members that are computed outside of the ES framework (the ES [parent join][es:parent-join] type is not used for this).

### Unstructured text

Two experimental settings allow to handle unstructured content (text that does not provide any explicit property value annotations) using a separate field in ES.

### Raw text

The `indexer.raw.text` setting enables to replicate the entire raw text of a page together with existing annotations so that unprocessed text can be searched in tandem with structured queries.

## Files and content ingestion

This requires the ES [ingest-attachment plugin][es:ingest] and the `indexer.experimental.file.ingest` setting.

The [ingest][es:ingest] process provides a method to retrieve content from files and make them available to ES and Semantic MediaWiki without requiring the actual content to be stored within the wiki itself.

In case where the ingestions and extraction was successful, a `File attachment` annotation will appear on the specific `File` entity and depending on the extraction quality of ES and Tika additional annotations will be added such as:

- `Content type`,
- `Content author`,
- `Content length`,
- `Content language`,
- `Content title`,
- `Content date`, and
- `Content keyword`

Due to size and memory consumption by ES/Tika, file content ingestions happens exclusively in background using the `smw.elasticFileIngest` job. Only after the job has been executed successfully, aforementioned annotations and file content will be accessible during a query request.

An "unstructured search" (i.e. searching without a property assignment) requires the wide proximity expression which conveniently are available as shortcut using `in:`, `phrase:`, or `not:`.

## Query debugging

`format=debug` will output a detailed description of the `#ask` and ES DSL used for a query answering making it possible to analyze and retrieve explanations from ES about a query request.

## Special:Search integration

In case [SMWSearch][smw:search] was enabled, it is possible to retrieve [highlighted][es:highlighting] text snippets for matched entities from ES given that `highlight.fragment.type` is set to one of the excepted types (`plain`, `unified`, and `fvh`). Type `plain` can be used without any specific requirements, for the other types please consult the ES documentation.

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
[es:query-dsl-terms-lookup]: https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-terms-query.html#query-dsl-terms-lookup
[smw:search]: https://www.semantic-mediawiki.org/wiki/Help:SMWSearch

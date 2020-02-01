[Usage][section:usage] | [Settings][section:config] | [Technical notes][section:technical] | [FAQ][section:faq]

`#ask` queries are system-agnostic, meaning that queries that worked with the `SQLStore` (or `SPARQLStore`) are expected to work equally with `ElasticStore` and do not require any modifications to a query or its syntax.

By default, the `ElasticStore` has set its query execution to a compatibility mode where queries are expected to return the same results as when used with the `SQLStore`. For example, in some instances Elasticsearch could provide a different result especially in connection with boolean query operators, but the `compat.mode` warrants consistency among results retrieved from the `ElasticStore` in comparison to the `SQLStore` (which is important when running the same set of integration tests against each store).

## Filter and query context

Most searches with a discrete value in Semantic MediaWiki will be classified as [structured search][es:structured:search] that operates with a [filter context][es:filter:context] while full-text or proximity searches use a [query context][es:query:context] that assigns relevancy scores to a match pool. A filter context will always yield a `1` relevancy score as it is translated into a boolean operation which either matches or neglects a result as part of a set.

* `[[Has page::Foo]]` (filter context) to match entities with value `Foo` for the property `Has page`
* `[[Has page::~*Foo*]]` (query context) to match entities with any value that contains `Foo` (and `FOO`,`foo` etc. ) for the `Has page` property

### Prefixes

To improve the handling of proximity searches, the following expressions can be used:

Expression | Interpret as | Description | Note
------------ | ------------- | ------------- | -------------
`in: ...`  |  `~~* ... *` or `~* ... *` | Find anything that contains `...` | The `in:` expression can also be combined with a property and depending on the type, context will be interpreted differently.
`phrase: ...` |  `~~" ... "` or `~" ... "` | Find anything that contains `...` in the exact same order | The `phrase:` expression is only relevant for literal components such as text or page titles as well as unstructured text.
`not: ...` | `!~~...` or `!~...` | Do not match any entity that matches `...` | The `not:` expression is intended to only match the exact entered term. It can be extended using `*` if necessary (e.g. `[[Has text::not:foo*]]`)

A wide proximity is expressed with `~~` and the intent to search where a specific property is unknown (in case of ES it can expand the search radius to fields that have not been annotated or processed by Semantic MediaWiki prior to a query request; see `indexer.raw.text` and `experimental.file.ingest`)

Type |  #ask | Interpret as
------------ | ------------ | -------------
\- | `[[in:some foo]]` | `[[~~*some foo*]]`
Text | `[[Has text::in:some foo]]` | `[[Has text::~*some foo*]]`
Page | `[[Has page::in:foo]]` | `[[Has text::~*foo*]]`
Number | `[[Has number::in:99]]` | `[[Has number:: [[≥0]] [[≤99]] ]]`
&nbsp; | `[[Has number::in:-100]]` | `[[Has number:: [[≥-100]] [[≤0]] ]]`
Time | `[[Has date::in:2000]]` | `[[Has date:: <q>[[≥2000]] [[<<1 January 2001 00:00:00]]</q> ]]`

## Relevancy and scores

[Relevancy][es:relevance] sorting is a topic of its own (and is only provided by Elasticsearch and the `ElasticStore`). In order to sort results by a score, the `#ask` query needs to signal that a different context is required during the query execution. The `es.score` sortkey (see `score.sortfield` which is used as a convention key) signals to the `QueryEngine` that for a non-filtered context score, tracking is to be enabled.

Only query constructs that use a non-filtered context (`~/!~/in/phrase/not`) provide meaningful scores that are expressive enough for sorting results; otherwise, results will not be distinguishable and not contribute to a meaningful overall sorting experience.

<pre>
// Find entities that contains "some text" in the property `Has text` and sort
// by its score returned from each matched document:

{{#ask: [[Has text::in:some text]]
 |sort=es.score
 |order=desc
}}
</pre>

## Property chains, paths, and subqueries

ES does not support [subqueries][es:subqueries] or [join][es:joins] constructs natively but it provides so-called [terms lookup][es:terms-lookup] which we enable to execute a path (chain of properties), building an iterative process allowing the creation of a set of results that match a path condition (e.g. `Foo.bar.foobar`), with each element holding a restricted list of results from the previous execution to traverse the property path.

The introduced process allows matching the `SQLStore` behaviour in terms of path queries where the `QueryEngine` splits each path and computes a list of elements. To avoid issues with a vast list of matches, Semantic MediaWiki will "park" those results in the `lookup` index with the `subquery.terms.lookup.index.write.threshold` setting (the default is 100) determining when the results will be moved into the separate `lookup` index.

## Hierarchies

Property and category hierarchies are supported by relying on a conjunctive boolean expression for hierarchy members that are computed outside of the Elasticsearch framework (the Elasticsearch [parent join][es:parent-join] type is not used for this).

## Examples

### File attachment

If the file ingestion was enabled and the processing has provided the `File attachment` property then access to its content is available using the property chain notation.

```
// Find all subjects (aka. files) that were ingested and indexed with a `image/png`
// content type

{{#ask: [[File attachment.Content type::image/png]]
 |?File attachment.Content title
}}
```
```
// Find all subjects (aka. files) that were ingested and indexed with a `application/pdf`
// content type and where the index content contains the `brown fox` text

{{#ask: [[File attachment.Content type::application/pdf]] [[in:brown fox]]
 |?File attachment.Content title
 |?File attachment.Content author
}}
```

## Query debugging

`format=debug` will output a detailed description of the `#ask` and Elasticsearch DSL used for a query response, making it possible to analyze and retrieve explanations from Elasticsearch about a query request.

## Special:Search integration

In the event [SMWSearch][smw:search] is enabled, it is possible to retrieve [highlighted][es:highlighting] text snippets for matched entities from Elasticsearch if `highlight.fragment.type` is set to one of the declared types (`plain`, `unified`, and `fvh`). Type `plain` can be used without any specific requirements; for the other types please consult the Elasticsearch documentation.

[es:conf]: https://www.elastic.co/guide/en/elasticsearch/reference/6.1/system-config.html
[es:conf:hosts]: https://www.elastic.co/guide/en/elasticsearch/client/php-api/current/configuration.html
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
[es:ingest:usage]:https://www.elastic.co/guide/en/elasticsearch/plugins/master/using-ingest-attachment.html
[es:ingest:node]:https://www.elastic.co/guide/en/elasticsearch/reference/master/ingest.html
[es:parent-join]: https://www.elastic.co/guide/en/elasticsearch/reference/current/parent-join.html
[es:replica-shards]:https://www.elastic.co/guide/en/elasticsearch/guide/current/replica-shards.html
[es:highlighting]: https://www.elastic.co/guide/en/elasticsearch/reference/current/search-request-highlighting.html
[es:query-dsl-terms-lookup]: https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-terms-query.html#query-dsl-terms-lookup
[smw:search]: https://www.semantic-mediawiki.org/wiki/Help:SMWSearch
[tika]: https://tika.apache.org/
[conf:example]:https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/src/Elastic/docs/config.md
[section:usage]: https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/src/Elastic/docs/usage.md
[section:config]: https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/src/Elastic/docs/config.md
[section:technical]: https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/src/Elastic/docs/technical.md
[section:faq]: https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/src/Elastic/docs/faq.md
[section:replication]: https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/src/Elastic/docs/replication.md
[section:search]: https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/src/Elastic/docs/search.md

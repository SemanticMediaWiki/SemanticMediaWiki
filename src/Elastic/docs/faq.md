# FAQ

### Why Elasticsearch?

- It it is relatively easy to install and run an ES instance (also on not recommended hardware).
- ES allows to scale its cluster horizontally without requiring changes to Semantic MediaWiki or its query engine.
- It is more likely that a user in a MediaWiki environment can provided access to an ES instance than to a `SPARQL` triple store (or a SORL/Lucence backend).

> Why not combine the `SQLStore` and ES search where ES only handles the text search?

The need to support ordering of results requires that the sorting needs to happen over the entire set of results that match a condition. It is not possible to split a search between two systems while retaining consistency for the offset (from where result starts and end) pointer.

> Why not use ES as a replacement?

Because at this point of implementation ES is used search engine and not a storage backend therefore the data storage and management remains part of the `SQLStore`. The `SQLStore` is responsible for creating IDs, storing data objects, and provide answers to requests that doesn't involve the `QueryEngine`.

### Elasticsearch

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

### MediaWiki, Semantic MediaWiki, and Elasticsearch

> I use CirrusSearch, can I search SMW (or its data) via CirrusSearch?

No, because first of all SMW doesn't rely on CirrusSearch at all and even if a user has CirrusSearch installed both extensions have different requirements and different indices and are not designed to share content with each other.

> Can I use `Special:Search` together with SMW and CirrusSearch?

Yes, by adding `$wgSearchType = 'SMWSearch';` one can use the `#ask` syntax (e.g. `[[Has date::>1970]]`) and execute structured or unstructured searches. Using the [extended profile](https://www.semantic-mediawiki.org/wiki/Help:SMWSearch/Extended_profile) or `#ask` constructs a search input that will retrieved results via Semantic MediaWiki (and hereby ES).

## Glossary

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
[es:query-dsl-terms-lookup]: https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-terms-query.html#query-dsl-terms-lookup
[smw:search]: https://www.semantic-mediawiki.org/wiki/Help:SMWSearch

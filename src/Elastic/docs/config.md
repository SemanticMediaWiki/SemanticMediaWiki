[Usage][section:usage] | [Settings][section:config] | [Technical notes][section:technical] | [FAQ][section:faq]

Accessing an Elasticsearch cluster from within Semantic MediaWiki requires to alter the following settings:

- [`$smwgElasticsearchEndpoints`](https://www.semantic-mediawiki.org/wiki/Help:$smwgElasticsearchEndpoints)
- [`$smwgElasticsearchConfig`](https://www.semantic-mediawiki.org/wiki/Help:$smwgElasticsearchConfig)
- [`$smwgElasticsearchProfile`](https://www.semantic-mediawiki.org/wiki/Help:$smwgElasticsearchProfile)

## Connection to Elasticsearch

`smwgElasticsearchEndpoints` is a __required__ setting and contains a list of available endpoints to establish a connection with an Elasticsearch cluster.

<pre>
$GLOBALS['smwgElasticsearchEndpoints'] = [
	[ 'host' => '192.168.1.126', 'port' => 9200, 'scheme' => 'http' ], // extended
	'localhost:9200' // inline
];
</pre>

Please consult the official [documentation][es:conf:hosts] for details about how to use the inline or extended form.

## Configuration

`$smwgElasticsearchConfig` is a compound setting that collects various parameters into one Semantic MediaWiki setting to shape the interaction with Elasticsearch including specific index and query details.

<pre>
$GLOBALS['smwgElasticsearchConfig'] = [

	// Points to index and mapping definition files
	'index_def'       => [ ... ],

	// Defines connection details for Elasticsearch endpoints
	'connection'  => [ ... ],

	// Holds replication details
	'indexer' => [ ... ],

	// Used to modify Elasticsearch specific settings
	'settings'    => [ ... ],

	// Section to optimize the query execution
	'query'       => [ ... ]
];
</pre>

### Changing a setting

A detailed list of settings and their explanations is available in the `DefaultSettings.php`. Please make sure that after changing any setting, `php rebuildElasticIndex.php --update-settings` is executed.

When modifying a particular setting, use an appropriate key to change the value of a parameter otherwise it is possible that the entire configuration is replaced.

<pre>
// Uses a specific key and therefore replaces only the specific parameter
$GLOBALS['smwgElasticsearchConfig']['query']['uri.field.case.insensitive'] = true;

// This !!overrides!! the entire configuration
$GLOBALS['smwgElasticsearchConfig'] = [
	'query' => [
		'uri.field.case.insensitive' => true
	]
];
</pre>

## Shards and replicas

The default shards/replica configuration is set to:

- The `data` index has two primary shards and two replicas
- The `lookup` index has one primary shard and no replica (the Elasticsearch [documentation][es:query-dsl-terms-lookup] notes that "... consider using an index with a single shard ... lookup terms filter will prefer to execute the get request on a local node if possible ...")

If it is required to change the numbers of [shards][es:shards] and replicas it is preferable to use the `$smwgElasticsearchConfig` setting for this with.

<pre>
$GLOBALS['smwgElasticsearchConfig']['settings']['data'] = [
	'number_of_shards' => 3,
	'number_of_replicas' => 3
]
</pre>

Elasticsearch comes with a precondition that any change to the `number_of_shards` requires to rebuild the entire index, so changes to that setting should be made carefully and in advance.

Read-heavy wikis might want to add (without the need the re-index the data) replica shards where Elasticsearch performance is in decline (the Elasticsearch documentation notes that [replica shards][es:replica-shards] should be put on an extra hardware).

## Field mappings

The `index_def` settings points to the index definition with the `data` index to be assigned the `smw-data-standard.json` as default to define its field mappings which influences how Elasticsearch analyzes and index documents including fields that are identified to contain text and string elements. Those text fields rely on the [standard analyzer][es:standard:analyzer] and should work for most applications.

The index name will be composed of a prefix such as `smw-data` (or `smw-lookup`), the `wikiID`, and a version indicator (part of the [rollover][es:alias-zero] support) so that a single ES cluster can host different indices from different Semantic MediaWiki instances without interfering with each other.

<pre>
{
	"_index": "smw-data-mw-foo-v1",
	"_type": "data",
	"_id": "1",
	"_version": 2,
	"_source": ...
}
</pre>

## Text, languages, and analyzers

For certain languages the `icu` analyzer (or any other language specific configuration) may provide better results, so one may alter the `index_def` index definitions hereby allowing custom settings such as deviating language [analyzers][es:lang:analyzer] to be used to increase the likelihood of better matching precision on text elements.

For a non-latin language environment the [analysis-icu plugin][es:icu:tokenizer] provides better support for [unicode normalization][es:unicode:normalization] and [case folding][es:unicode:case:folding] and selecting `smw-data-icu.json` as `index_def` setting may prove to create a better match accuracy during query answering especially on unstructured text elements or wide proximity searches.

`smw-data-icu.json` is provided as an example on how to alter those settings. It should be noted that query results on text fields may differ compared to when one would use the standard analyzer and users are expected to evaluate whether those settings are more favorable or not to the query answering.

Please note that any change to the index or its analyzer settings __requires__ to rebuild the entire index.

## Using a profile

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

The profile is loaded at the end of the configuration stack and will override any default or individual settings made to `$smwgElasticsearchConfig`.

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
[es:ingest]:https://www.elastic.co/guide/en/elasticsearch/plugins/master/ingest-attachment.html
[es:parent-join]: https://www.elastic.co/guide/en/elasticsearch/reference/current/parent-join.html
[es:replica-shards]:https://www.elastic.co/guide/en/elasticsearch/guide/current/replica-shards.html
[es:highlighting]: https://www.elastic.co/guide/en/elasticsearch/reference/current/search-request-highlighting.html
[es:query-dsl-terms-lookup]: https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-terms-query.html#query-dsl-terms-lookup
[smw:search]: https://www.semantic-mediawiki.org/wiki/Help:SMWSearch
[section:usage]: https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/src/Elastic/docs/usage.md
[section:config]: https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/src/Elastic/docs/config.md
[section:technical]: https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/src/Elastic/docs/technical.md
[section:faq]: https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/src/Elastic/docs/faq.md
[section:replication]: https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/src/Elastic/docs/replication.md
[section:search]: https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/src/Elastic/docs/search.md

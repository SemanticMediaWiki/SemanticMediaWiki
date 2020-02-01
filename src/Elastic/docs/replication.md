[Usage][section:usage] | [Settings][section:config] | [Technical notes][section:technical] | [FAQ][section:faq]

Updates to an Elasticsearch index happens instantaneously after a new revision has been saved in MediaWiki and after the storage layer receives an event that has been emitted by the Semantic MediaWiki/MediaWiki hook to guarantee that queries can use the latest available data set as soon as possible.

The [index creation][es:create:index] documentation describes how the index process occurs in Elasticsearch. Semantic MediaWiki provides two separate indices:

- the `data` index hosts all user-facing queryable data (structured and unstructured content)
- the `lookup` index stores term and lookup queries used for concept, property path, and inverse match computations

Each MediaWiki instance (hereby Semantic MediaWiki) with its own `wikiID` (internal name of the database, wiki site identification) replicates to a separate index which is why different MediaWiki installations (assuming the `wikiID` is different) can be hosted on the same Elasticsearch cluster without interfering with each other during the update or search.

<pre>
{
	// `smw-data` is the fixed part, `mw-foo` describes the wikiID, and
	// `v1` identifies the active version (required for the roll-over)
	"_index": "smw-data-mw-foo-v1",
	"_type": "data"
}
</pre>

## Active replication (update-based)

In the normal operative mode, `ElasticStore` uses an __active replication__ to transfer the data to the Elasticsearch cluster which means that changes (i.e those caused by an update, delete etc.) from wikipages are actively relicated and mostly instantaneously (depends on the refresh interval) visiable.

### Safe replication

The `ElasticStore` by default is set to use a safe replication mode which entails that if during a page storage __no__ connection could be established to an Elasticsearch cluster, a `smw.elasticIndexerRecovery` job is planned for changes that weren't replicated. These jobs should be executed on a regular basis to ensure that data are kept in sync with the backend.

The `job.recovery.retries` setting is set to a maximum of retry attempts in case the job itself cannot establish a connection, in which case the job is then canceled even though it could __not__ recover.

## Script-based replication

The `rebuildElasticIndex.php` script is provided as a method to transfer existing data from the `SQLStore` (fetching information directly from tables without re-parsing any wikipages) to the Elasticsearch backend.

The script operates in a [rollover mode][es:alias-zero] lets an existing index to remain operative until the new index with a different version (v1/v2) is created and released. The current active index is kept untouched so queries can continue to operate while the reindex process is ongoing and once completed, the new index switches places with the old index and is removed from the Elasticsearch cluster.

For the script-based replication to be as fast as possible some changes to operational settings are made:

- The __active replication__ is paused for the duration of the rebuild in order for changes to be processed after the re-index has been completed. It is __obligatory__ to run the job scheduler after the completion of the task to process any outstanding jobs.
- The [`refresh_interval`][es:indexing:speed] is changed to `-1` as recommended by the official [documentation][es:indexing:speed] to speed up the data transfer.

### Refresh interval

The [`refresh_interval`][es:indexing:speed] dictates how often Elasticsearch creates new [segments][stack:segments] and in the normal operative mode is set to `1s` as default to make updates appear near real time or instantaneously.

During the rebuild process the setting is changed to `-1` as recommended by the official [documentation][es:indexing:speed] so that changes to the cluster nodes can be replicated quicker. Now, if for some reason (e.g an aborted rebuild, raised exception etc.) the `refresh_interval` remains at `-1` (since the process was aborted without the possibility for Semantic MediaWiki to intervene on a OS level) and changes to an index will not be visible until the `refresh_interval`  has been reset, and to fix the setting it is recommended to run:

- `php rebuildElasticIndex.php --update-settings`
- `php rebuildElasticIndex.php --force-refresh`

## Replication monitoring

[Replication monitoring][smw:monitoring] has been added as feature to allow users to be informed about the state of a document replication with the Elasticsearch cluster given that the `ElasticStore` relies on active replication.

## Structured and unstructured data

There are two different types of data (or content) that is replicated to an Elasticsearch cluster. The most obvious and reliable of the two are structured data retrieved from:

- Properties and their annotations
- Other metadata

"Unstructured" as category classifies loose text without any metadata or specific annotations and includes:

- Raw text (article or wikipage)
- File content

Two experimental settings are provided to handle unstructured content (i.e. text that does not provide any explicit annotations or structured elements) by using a separate index field in Elasticsearch are defined by:

- `indexer.raw.text`
- `indexer.experimental.file.ingest`

It should be noted that if either of the setting is enabled, the index size will grow for the unstructured fields in size especially if users want to index large document files therefore expected index size should be estimated carefully.

The support for searching "unstructured text" (i.e. searching without a property assignment) is made possible by the wide proximity expression (`~~`) or the following prefixes (`in:`, `phrase:`, or `not:`) to indicate to a query request such as `[[in:some text]]` or `[[phrase:the brown fox]]` to include special "unstructured" index fields to match those requests.

Aside from searching for "unstructured text", combining structured and unstructured elements in a query such as `[[Has population::1000]] [[in:some text]]` improves the quality of search matches when Semantic MediaWiki users are to balance the cost of maintaining structured content and require unstructured content to broaden the scope and search depth.

### Raw text

The `indexer.raw.text` (default is `false`) setting is provided to replicate the entire raw text of an article revision as unprocessed text to the `text_raw` field.

### File content

The `indexer.experimental.file.ingest` (default is `false`) setting is provided to support the ingestion of file content. It requires the Elasticsearch [ingest-attachment plugin][es:ingest].

The [ingest][es:ingest] process provides a method to retrieve content from files (using the Apache [Tika][tika] component bundled with Elasticsearch) and make them available via Elasticsearch to Semantic MediaWiki without requiring the actual file content to be stored within the wiki itself.

Due to size and memory consumption requirements by Elasticsearch and Tika, file content ingestion happens exclusively in background using the `smw.elasticFileIngest` job and only after the job has been executed successfully  will the file content and additional annotations be accessible and available as indexed (searchable) content.

As the [documentation][es:ingest:usage] points out, "Extracting contents from binary data is a resource intensive operation and consumes a lot of resources. It is highly recommended to run pipelines using this processor in a dedicated ingest node." (see also the [ingest node][es:ingest:node] documentation).

The [replication monitoring][smw:monitoring] will indicate on a file page whether the ingest process was completed or not by checking if the `File attachment` property exists for the particular file entity.

#### Ingest and index process

1. File upload (wiki upload) and creation of a `File` page
2. Push `FileIngestJob` hereby register `smw.elasticFileIngest` job with the job queue, **waiting on** command line execution
3. Execution of `smw.elasticFileIngest`, runs `FileIndexer` which adds and runs the `attachment` pipeline
4. Retrieve response, run `AttachmentAnnotator` (adding `File Attachment` annotation)

The `rebuildElasticIndex.php` maintenance script comes with two options related to the file ingestion process:

- `skip-fileindex` to skip any file ingestion during the rebuild execution
- `run-fileindex` only run and execute file ingestions during the rebuild process

#### File attachment

Once the ingestion and extraction of content was successful, a `File attachment` annotation will appear on the specific `File` entity in Semantic MediaWiki, and based on the extraction quality of Tika (see the [documentation][es:ingest:usage] for details on what is retrievable), annotations will include:

- `Content type` (corresponds to `content_type`),
- `Content author` (corresponds to `author`),
- `Content length` (corresponds to `content_length`),
- `Content language` (corresponds to `language`),
- `Content title` (corresponds to `title`),
- `Content date` (corresponds to `date`), and
- `Content keyword` (corresponds to `keywords`)

The `File attachment` property is a container object which means accessing aforementioned properties requires the use of a property chain.

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

#### Index quality

If Elasticsearch (hereby Tika) doesn't provide information to some of the [properties][es:ingest:usage] then those will not appear as part of the `File attachment` annotation.

The quality of the text indexed and the information provided to the `File attachment` properties depends solely on Elasticsearch and Tika (a specific Tika version is bundled with a specific Elasticsearch release).

Any issues with the quality of indexed content or the recognition of specific information about a file (e.g. type, date, author etc.) has to be addressed in Elasticsearch and is not part or the scope of Semantic MediaWiki.

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
[smw:monitoring]:https://www.semantic-mediawiki.org/wiki/Help:Replication_monitoring

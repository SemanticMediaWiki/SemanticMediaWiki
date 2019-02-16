# Technical notes

Classes and objects related to the Elasticsearch interface and implementation are placed under the `SMW\Elastic` namespace.

<pre>
SMW\Elastic
│	├─ Admin         # Classes used to extend `Special:SemanticMediaWiki`
│	├─ Exception
│	├─ Connection    # Responsible for building a connection to ES
│	├─ Indexer       # Contains all necessary classes for updating the ES index
│	├─ Lookup        # Provides additional lookup services
│	└─ QueryEngine   # Hosts the query builder and `#ask` language interpreter classes
│
├─ ElasticFactory
└─ ElasticStore
</pre>

## Data mapping and serialization 

### Serialization format

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

### Field mapping

Semantic MediaWiki is [mapping][es:mapping] its internal structure using [`dynamic_templates`][es:dynamic:templates] to define expected data types, their attributes, and possibly add extra index fields (see [multi-fields][es:multi-fields]) to support certain query constructs.

The naming convention follows a very pragmatic naming scheme, `P:<ID>.<type>Field` with each new field (aka property) being mapped dynamically to a corresponding field type.

- `P:<ID>` identifies the property with a number which is the same as the internal ID in the `SQLStore` (`smw_id`)
- `<type>Field` declares a typed field (e.g. `txtField` which is important in case the type changes from `wpg` to `txt` and vice versa) and holds the actual indexable data.
- Dates are indexed using the julian day number (JDN) to allow for historic dates being applicable

The `SemanticData` object is always serialized in its entirety to avoid for the interface to keep delta information. Furthermore, ES itself always creates a new index document for each update therefore keeping deltas wouldn't make much of difference in terms of how the data are stored and updated and allows the indexer to take advantage of the [bulk][es:bulk] API making updates faster and more resilient while avoiding document comparison during an update process.

To allow for exact matches as well as full-text searches on the same field most mapped fields will have at least two or three additional [multi-field][es:multi-fields] elements to store text as `not_analyzed` (meaning as keyword) and as sortable entity.

An ES document can contain additional fields such as:

* `text_copy` mapping (see [copy-to][es:copy-to]) is used to enable wide proximity searches on textual annotated elements. For example, `[[in:foo bar]]` (eq. `[[~~foo bar]]`) translates into "Find all entities that have `foo bar` in one of its assigned `_uri`, `_txt`, or `_wpg` properties. The `text_copy` field is a compound field for all strings to be searched when a specific property is unknown.
* `text_raw` (requires `indexer.raw.text` to be set `true`) contains unstructured and unprocessed raw text from an article so that it can be used in combination with the proximity operators `[[in:lorem ipsum]]` and `[[phrase:lorem ipsum]]`.
* `attachment.{...}` will be added by the `ingest processor` when file content was successfully processed

## ES DSL mapping

`#ask` queries are transformed to represent an equivalent expression in the ES DSL hereby allowing the `ElasticStore` to be used as drop-in replacement for queries expressed using `#ask` language constructs.

For example, the `[[in:lorem ipsum]]` query (or as fully qualified `[[~~*lorem ipsum*]]`, find all entities that contains `lorem ipsum` on any document) on structured and unstructured fields written as ES DSL will look similar to:

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

## Monitoring

To make it easier for administrators to monitor the interface between Semantic MediaWiki and ES, several service links are provided for a convenient access to selected information.

The main access point is defined with `Special:SemanticMediaWiki/elastic` but only users with the `smw-admin` right (which is required for the `Special:SemanticMediaWiki` page) can access the information and only when an ES cluster is available.

## Logging

The enable connector specific logging, please use the `smw-elastic` identifier in your `LocalSettings.php`.

<pre>
$wgDebugLogGroups  = [
	'smw-elastic' => ".../logs/smw-elastic-{$wgDBname}.log",
];
</pre>

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

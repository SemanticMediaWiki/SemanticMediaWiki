# ElasticStore

[Requirements](#requirements) | [Features](#features) | [Setup](#setup) | [Usage][section:usage] | [Settings][section:config] | [Technical notes][section:technical] | [FAQ][section:faq]

The `ElasticStore` provides a framework to replicate Semantic MediaWiki related data to an Elasticsearch cluster and enable its `QueryEngine` to send `#ask` requests and retrieve information from Elasticsearch (aka ES) instead of the default `SQLStore`.

The objective is to provide an interface to Elasticsearch to:

- improve structured (and allow unstructured) content searches
- extend and improve full-text query support (including sorting of results by [relevancy][es:relevance])
- provide means for a scalability strategy by relying on the ES infrastructure

## Requirements

- Elasticsearch: Recommended 6.1+, Tested with 5.6.6
- Semantic MediaWiki: 3.0+
- [`elasticsearch/elasticsearch`][packagist:es] (PHP ^7.0 `~6.0` or PHP ^5.6.6 `~5.3`)

The `ElasticStore` relies on the [elasticsearch php-api][es:php-api] to communicate with Elasticsearch directly and is therefore independent of any other vendor or MediaWiki extension that may use Elasticsearch as search backend (e.g. `CirrusSearch`).

It is recommended to:

- Use Elasticsearch 6+ due to improvements to its [sparse field][es:6] handling
- Consult the official hardware [guide][es:hardware] on specific Elasticsearch requirements

## Features

The `ElasticStore` provides the same query features (given those are tested) as the `SQLStore` making its functionality equivalent to the `SQLStore` but provides better support for free text matches or for unstructured text (if enabled) when retrieved from an article or file. Furthermore, improved performance and scalability is expected especially when the document count becomes significant larger than 50000 articles or a lot of fulltext queries are executed.

- Active [replication monitoring][smw:monitoring] to inform user about the replication state of a document
- Handling of active property type changes without the need to rebuild the entire index itself after it is ensured that all [`ChangePropagation`][smw:changeprop] jobs have been processed
- Support of inverse queries such as `[[-Foo::Bar]]`
- Support of property chain and path queries (e.g. `[[Foo.Bar::Foobar]]`)
- Support of category and property hierarchies

## Setup

Before the ElasticStore (hereby Elasticsearch) can be used as drop-in replacement for the existing `SQLStore` based `QueryEngine` the following settings and operations are required:

- Set `$GLOBALS['smwgDefaultStore'] = 'SMWElasticStore';` (see [`$smwgDefaultStore`][smw:smwgDefaultStore])
- Set `$GLOBALS['smwgElasticsearchEndpoints'] = [ ... ];` (see [`$smwgElasticsearchEndpoints`][smw:smwgElasticsearchEndpoints])
- Run `php setupStore.php` or `php update.php`
- Rebuild the index using `php rebuildElasticIndex.php` (see [`rebuildElasticIndex.php`][smw:rebuildElasticIndex.php])

A more detailed introduction can be found as part of the [usage][section:usage] and [settings][section:config] section.

## General notes

Elasticsearch is not expected to be used as data store replacement which means a RDMBS backend (MySQL, SQLite, or Postgres) is still required.

The `ElasticStore` provides a customized serialization format to transform and transfer the required data to Elasticsearch. The `QueryEngine` provides a #ask-ES DSL interpreter (see [domain language][es:dsl]) which ensures that any existing `#ask` query can be answered by the Elasticsearch cluster without changing its syntax when switching from a `SQLStore` (given that index process has been completed).

[packagist:es]:https://packagist.org/packages/elasticsearch/elasticsearch
[es:php-api]: https://www.elastic.co/guide/en/elasticsearch/client/php-api/6.0/_installation_2.html
[es:dsl]: https://www.elastic.co/guide/en/elasticsearch/reference/6.1/query-dsl.html
[es:hardware]: https://www.elastic.co/guide/en/elasticsearch/guide/2.x/hardware.html#_memory
[es:relevance]: https://www.elastic.co/guide/en/elasticsearch/guide/master/relevance-intro.html
[es:6]: https://www.elastic.co/blog/minimize-index-storage-size-elasticsearch-6-0
[section:usage]: https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/src/Elastic/docs/usage.md
[section:config]: https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/src/Elastic/docs/config.md
[section:technical]: https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/src/Elastic/docs/technical.md
[section:faq]: https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/src/Elastic/docs/faq.md
[smw:smwgDefaultStore]:https://www.semantic-mediawiki.org/wiki/Help:$smwgDefaultStore
[smw:smwgElasticsearchEndpoints]:https://www.semantic-mediawiki.org/wiki/Help:$smwgElasticsearchEndpoints
[smw:rebuildElasticIndex.php]:https://www.semantic-mediawiki.org/wiki/Help:rebuildElasticIndex.php
[smw:monitoring]:https://www.semantic-mediawiki.org/wiki/Help:Replication_monitoring
[smw:changeprop]:https://www.semantic-mediawiki.org/wiki/Help:Change_propagation

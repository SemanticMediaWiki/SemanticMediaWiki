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

We rely on the [elasticsearch php-api][es:php-api] to communicate with Elasticsearch and are therefore independent from any other vendor or MediaWiki extension that may use ES as search backend (e.g. `CirrusSearch`).

It is recommended to use:

- ES 6+ due to improvements to its [sparse field][es:6] handling
- ES hardware with "... machine with 64 GB of RAM is the ideal sweet spot, but 32 GB and 16 GB machines are also common ..." as noted in the [elasticsearch guide][es:hardware]

## Features

- Handle property type changes without the need to rebuild the entire index itself after it is ensured that all `ChangePropagation` jobs have been processed
- Inverse queries are supported (e.g. `[[-Foo::Bar]]`)
- Property chains and paths queries are supported (e.g. `[[Foo.Bar::Foobar]]`)
- Category and property hierarchies are supported

ES is not expected to be used as solely data store replacement and therefore it is not assumed that ES returns all `_source` fields for a request.

The `ElasticStore` provides a customized serialization format to transform and transfer data, an interpreter (see  [domain language][es:dsl]) allows `#ask` queries to be answered by an ES instance.

## Setup

To use Elasticsearch as drop-in replacement for the existing `SQLStore` based query answering the following settings and actions are necessary.

- Set `$GLOBALS['smwgDefaultStore'] = 'SMWElasticStore';`
- Set `$GLOBALS['smwgElasticsearchEndpoints'] = [ ... ];`
- Run `php setupStore.php` or `php update.php`
- Rebuild the index using `php rebuildElasticIndex.php`

For a more detailed introduction, see the [usage][section:usage] and [settings][section:config] section and

- [`$smwgDefaultStore`][help:smwgDefaultStore]
- [`$smwgElasticsearchEndpoints`][help:smwgElasticsearchEndpoints]
- [`rebuildElasticIndex.php`][help:rebuildElasticIndex.php]

## more ...

- [Usage][section:usage]
- [Configuration and settings][section:config]
- [Technical notes][section:technical]
- [FAQ][section:faq]

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
[help:smwgDefaultStore]:https://www.semantic-mediawiki.org/wiki/Help:$smwgDefaultStore
[help:smwgElasticsearchEndpoints]:https://www.semantic-mediawiki.org/wiki/Help:$smwgElasticsearchEndpoints
[help:rebuildElasticIndex.php]:https://www.semantic-mediawiki.org/wiki/Help:rebuildElasticIndex.php

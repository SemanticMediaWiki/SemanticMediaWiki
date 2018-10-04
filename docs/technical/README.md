This document contains resources that can improve the understanding on "How Semantic MediaWiki
works" from an implementation and development point of view.

If you are new to SMW development, have a look at the:

* [Programmer's guide](https://www.semantic-mediawiki.org/wiki/Programmer%27s_guide)
* [Architecture guide](https://www.semantic-mediawiki.org/wiki/Architecture_guide)
* [Developer hub](https://www.semantic-mediawiki.org/wiki/Developer_hub) and [Coding conventions](https://www.semantic-mediawiki.org/wiki/Coding_conventions)
* [Code snippets](code-snippets/README.md)

## Overview

- [List of hooks](hooks.md) provided by Semantic MediaWiki
- Running [unit](https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/tests/phpunit/README.md), integration, and [JSONScript](https://github.com/SemanticMediaWiki/SemanticMediaWiki/tree/master/tests/phpunit/Integration/JSONScript) tests
- [api.md](api.md) provides an overview for available API modules
- [`Importer`](https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/src/Importer/README.md) contains a summary about the process and technical background of the content importer
- [`Lang`](https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/src/Lang/README.md)
- [`Serializers`](https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/docs/technical/doc.serializers.md) contains information about the Semantic MediaWiki serializers
- [`ext.smw.suggester`](https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/res/smw/suggester/README.md) on how to register additional tokens or context objects
- [`SMWSearch`](https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/src/MediaWiki/Search/README.md) `Special:Search` integration

### Store implementations

- SQLStore
  - [Overview](https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/src/SQLStore/README.md)
  - [QueryEngine](https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/src/SQLStore/QueryEngine/README.md)
  - [Installer](https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/docs/technical/doc.installer.md) contains a simplified schema about the `Installer` and `TableBuilder` interface

- SPARQLStore
  - [Overview](https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/src/SPARQLStore/README.md)

- ElasticStore
  - [Overview](https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/src/Elastic/README.md)

### Migration guide

- [2.5.x to 3.0.0](https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/docs/technical/migration-guide-3.0.md) contains information about the migration from 2.x to 3.x

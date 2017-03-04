This document contains resources that can improve the understanding on "How Semantic MediaWiki
is working" from an implementation and development point of view.

If you are new to SMW development, have a look at the [Programmer's]
(https://www.semantic-mediawiki.org/wiki/Programmer%27s_guide_to_SMW) and [Architecture guide](https://www.semantic-mediawiki.org/wiki/Architecture_guide) first.

## Overview

- [List of hooks](hooks.md) provided by Semantic MediaWiki
- Running [unit](https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/tests/phpunit/README.md), integration, and [JSONScript](https://github.com/SemanticMediaWiki/SemanticMediaWiki/tree/master/tests/phpunit/Integration/JSONScript) tests
- Using the [api.php](api.md) modules provided by Semantic MediaWiki
- `SQLStore`
  - [Overview](https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/src/SQLStore/README.md) about `SQLStore` related classes
  - [QueryEngine](https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/src/SQLStore/QueryEngine/README.md)
  - [Installer](https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/docs/technical/doc.installer.md) contains a simplified schema about the `Installer` and `TableBuilder` interface
- `SPARQLStore`
  - [Overview](https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/src/SPARQLStore/README.md)
- [`Importer`](https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/src/Importer/README.md) contains a summary about the process and technical background of the content importer
- [`ExtraneousLanguage`](https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/src/ExtraneousLanguage/README.md)
- [`Serializers`](https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/docs/technical/doc.serializers.md) contains information about the Semantic MediaWiki serializers

## Code snippets and code examples

- [phpunit.test.property](code-snippets/phpunit.test.property.md)
- [store.subobject](code-snippets/store.subobject.md)
- [query.someproperty.of.type.number](code-snippets/query.someproperty.of.type.number.md)
- [query.description](code-snippets/query.description.md)
- [register.datatype](code-snippets/extension.register.datatype.md)
- [semanticdata.access](code-snippets/semanticdata.access.md)

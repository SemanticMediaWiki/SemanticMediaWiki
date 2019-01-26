# Semantic MediaWiki 3.1

Not a release yet. A release is tentatively planned for Q1/Q2 2019.

## Compatibility

Please find relevant notes about the platform and database compatibility for this release in the [COMPATIBILITY](https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/docs/COMPATIBILITY.md) document.

## Highlights

## New features and enhancements

Changes to the DB are triggered by #3644.

### Setup

* [#3605](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3605) Conditionally create the full-text ([`smw_ft_search`](https://www.semantic-mediawiki.org/wiki/Table:smw_ft_search)) table

### Store

* [#3642](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3642) Added support in [`rebuildData.php`](https://www.semantic-mediawiki.org/wiki/rebuildData.php) to dispose outdated query links

#### ElasticStore

* [#3637](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3637) Uses `keyword` as type for the `P:*.geoField` mapping
* [#3638](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3638) Added minimal index document for an empty bulk request

### Query

* [#3644](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3644) Introduce [`DependencyLinksValidator`](https://www.semantic-mediawiki.org/wiki/Help:Embedded_query_update), refactor update logic ...

#### Result formats

* [#3650](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3650) Added support for `noimage` as output option for entity (aka. page) links

### API

...

### Misc

* [#3643](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3643) Added support for tracking [attachment links](https://www.semantic-mediawiki.org/wiki/Help:Attachment_links) via the `_ATTCH_LINK` property
* [#3652](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3652) Added [attachment display](https://www.semantic-mediawiki.org/wiki/Help:Attachment_links) in the `Factbox`

## Bug fixes

...

## Breaking changes and deprecations

...

## Other changes

* [#3580](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3580) Removed HHVM from test matrix (implicitly it means that HHVM is no longer supported)
* [#3612](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3612) Added `FieldType::TYPE_ENUM` support


## Contributors

...

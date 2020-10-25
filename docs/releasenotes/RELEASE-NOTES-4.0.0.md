# Semantic MediaWiki 4.0.0

Not released yet - under development

## Compatibility

* Dropped support for MediaWiki older than 1.35
* Dropped support for PHP older than 7.3

For more detailed information, see the [compatibility matrix](../COMPATIBILITY.md#compatibility).

## Upgrading

* When a triplestore is used with the SPARQL feature `SMW_SPARQL_QF_COLLATION`, the script `maintenance/updateEntityCollation.php` must be run (the collation sort key algorithm was changed).

## New features

## Enhancements

## Bug fixes

* [#4997](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/4997) Fixed the collation key for triplestores with the SPARQL feature `SMW_SPARQL_QF_COLLATION` in the case of an UCA collation


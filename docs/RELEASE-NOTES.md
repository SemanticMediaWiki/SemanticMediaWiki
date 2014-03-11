# Semantic MediaWiki 1.9.2


### New features

* #199 Added [object-Id lookup][id-lookup] via SMWAdmin
* #217 Extended ListResultPrinter to make `{{{userparam}}}` available to intro and outro templates and introduce `{{{swm-resultquerycondition}}}` parameter to access the source query condition within a template

### Bug fixes

* #215 (Bug 62150) Fixed malformed query order due to an unresolved prefix in SparqlQueryEngine

### Internal enhancements

* #195 Improved SQLStore3::getPropertyTables in order to use correct customizing
* #218 Extended SparqlStore to inject a SparqlDatabase (improves testablity) together with additional test coverage


[id-lookup]: https://www.semantic-mediawiki.org/wiki/Help:Object_ID_lookup

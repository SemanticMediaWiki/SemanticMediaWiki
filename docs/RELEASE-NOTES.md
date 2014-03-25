# Semantic MediaWiki 1.9.2


### New features

* #199 Added [object-Id lookup][id-lookup] via SMWAdmin
* #217 Extended ListResultPrinter to make `{{{userparam}}}` available to intro and outro templates and introduce [additional parameters][217] to be available

### Bug fixes

* #203 Fixed undefined index in connection with `$smwgQuerySources`
* #215 (Bug 62150) Fixed malformed query order due to an unresolved prefix in SparqlQueryEngine

### Internal enhancements

* #195 Improved SQLStore3::getPropertyTables in order to use correct customizing
* #204 Added update job for new redirects
* #207 Removed SMWParamSource and added a `$smwgQuerySources` integration test
* #218 Extended SparqlStore to inject a SparqlDatabase (improves testablity) together with additional test coverage
* #226 Added ExecutionTimeTestListener to report long running tests
* #227 Moved all job related classes into the `SMW\MediaWiki\Jobs\` namespace
* #234 Added a redirects regression test

[id-lookup]: https://www.semantic-mediawiki.org/wiki/Help:Object_ID_lookup
[217]: https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/217

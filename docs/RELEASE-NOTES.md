# Semantic MediaWiki 3.1.3

Released on January 24, 2020.

## Bug fixes and internal code changes

* [#4390](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/4390) as `a9fdd77`: Fixes the `filename` parameter for export formats providing it
* [#4393](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/4393) as `3a007b5`: Fixes title preview for namespaces in the standard search field when using `SMWSearch`
* [#4405](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/4407) as `c0e3ae9`: Fixes using `SMWElasticStore` as the default store for Elasticsearch lower than 6.4.0
* [#4407](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/4407) as `c18aad4`: Fixes the `#show` parser function no longer being able to query subobjects
* [#4419](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/4419) as `9460d09`: Fixes the `#show` parser function no longer following redirect targets
* [#4420](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/4420) as `2047e6f`: Removes overriding instance construction when using `SMWSparqlStore` as the default store
* [#4430](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/4430) as `620fd40`: Fixes a "TypeError" in "SchemaDefinition.php"
* `20ce009`: Makes a new constrictor argument optional for result formats provided by the "Semantic Result Formats" extension
* `fcd9d5f`: Fixes the `@annotation` query marker when used in conjuction with the "Page Forms" extension 
* Localisation updates from the translatewiki.net community of translators

## See also
* [RELEASE NOTES](https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/3.1.x/docs/releasenotes/RELEASE-NOTES-3.1.2.md) for Semantic MediaWiki 3.1.2
* [RELEASE NOTES](https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/3.1.x/docs/releasenotes/RELEASE-NOTES-3.1.1.md) for Semantic MediaWiki 3.1.1
* [RELEASE NOTES](https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/3.1.x/docs/releasenotes/RELEASE-NOTES-3.1.0.md) for Semantic MediaWiki 3.1.0

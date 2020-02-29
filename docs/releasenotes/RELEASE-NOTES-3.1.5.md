# Semantic MediaWiki 3.1.5

Released on February 29, 2020.

## Bug fixes and internal code changes

* [#4423](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/4423) as `2e11fd7`: Fixes table alias usage in select queries by maintenace script ["updateQueryDependencies.php"](https://www.semantic-mediawiki.org/wiki/Help:Maintenance_script_updateQueryDependencies.php)
* [#4500](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/4500) as `b9e1bff`: Fixes special page ["SemanticMediaWiki"](https://www.semantic-mediawiki.org/wiki/Help:Special:SemanticMediaWiki) being broken if configuration paramter `$wgDebugLogFile` is set
* [#4516](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/4516) as `13056ff`: Fixes external identifier formatting in [references](https://www.semantic-mediawiki.org/wiki/Help:Type_Reference)
* [#4532](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/4532) as `3a08cad`: Fixes the `InvalidArgumentException` issue when using the [`#info`](https://www.semantic-mediawiki.org/wiki/Help:Adding_tooltips) parser function with an integer parameter
* [#4550](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/4550) as `243f0ab`: Checks `PropertyRegistry::getInstance()->findPropertyIdByLabel( $label )` for `false`
* [#4589](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/4589) as `1ca5979` and `93765eb`: Fixes ["smwtable-clean"](https://www.semantic-mediawiki.org/wiki/Help:Table_format#smwtable-clean) class being broad by default

## See also
* [RELEASE NOTES](https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/3.1.x/docs/releasenotes/RELEASE-NOTES-3.1.4.md) for Semantic MediaWiki 3.1.4
* [RELEASE NOTES](https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/3.1.x/docs/releasenotes/RELEASE-NOTES-3.1.3.md) for Semantic MediaWiki 3.1.3
* [RELEASE NOTES](https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/3.1.x/docs/releasenotes/RELEASE-NOTES-3.1.2.md) for Semantic MediaWiki 3.1.2
* [RELEASE NOTES](https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/3.1.x/docs/releasenotes/RELEASE-NOTES-3.1.1.md) for Semantic MediaWiki 3.1.1
* [RELEASE NOTES](https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/3.1.x/docs/releasenotes/RELEASE-NOTES-3.1.0.md) for Semantic MediaWiki 3.1.0

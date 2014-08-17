# Semantic MediaWiki 2.1

This is not a release yet.

## SMWSearch search engine
This release adds a search engine (["SMWSearch"](https://semantic-mediawiki.org/wiki/Help:SMWSearch)) to allow semantic queries directly from MediaWiki's standard search (#450). The related configuration parameter [``$smwgFallbackSearchType``](https://semantic-mediawiki.org/wiki/Help:$smwgFallbackSearchType) assures that the default search engine for MediaWiki will be used in case "SMWSearch" returns no result. (#450)

## Internal changes

* #350 Passes all unit tests on `HHVM` 3.3+
* #486 Added support for `Jena Fuseki` 1.1.0

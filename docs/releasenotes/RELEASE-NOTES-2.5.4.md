# Semantic MediaWiki 2.5.4

Released on August 7, 2017.

## Enhancements

* [#2547](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2547) as `b527e3c` Added type `parser-html` to `JSONScript` testing to allow assertions on HTML structure
* Many new translations for numerous languages by the communtity of [translatewiki.net](https://translatewiki.net/w/i.php?title=Special%3AMessageGroupStats&x=D&group=mwgithub-semanticmediawiki&suppressempty=1)

## Bug fixes and internal code changes

* [#2563](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2563) as `f17f90f` Made `'HtmlValidator'` check for `'CssSelectorConverter'`
* [#2568](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2568) as `c8d6718` Made each parameter of the template calls created by the template format start on a new line
* [#2579](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2579) as `39b074b` Fixed class `'SMW\DataItemException'` not found
* [#2590](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2590) as `61ea7e0` **SECURITY** Made "Special:SemanticMediaWiki" ("Special:SMWAdmin") to check `'wpEditToken'`

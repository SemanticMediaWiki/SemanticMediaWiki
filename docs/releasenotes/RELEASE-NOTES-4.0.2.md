# Semantic MediaWiki 4.0.2

Released on July 21, 2022.

## Summary

This is a [patch release](../RELEASE-POLICY.md), meaning that it contains only fixes and no breaking changes.

This release improves compatibility with MediaWiki 1.37 and 1.38. It also brings a fix for a security issue.

Users of MediaWiki 1.37 and later and Semantic MediaWiki 4.0.1 and earlier are encouraged to upgrade.

## Upgrading

Get the new version via Composer:

* Step 1: if you are upgrading from SMW older than 4.0.0, ensure the SMW version in `composer.json` is `^4.0.2`
* Step 2: run composer in your MediaWiki directory: `composer update --no-dev --optimize-autoloader`

No need to run "update.php" or any other migration scripts.

## Changes

* [#5275](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/5275): **SECURITY** Sanitized query log before output. Thanks to [Markus Glaser](https://hallowelt.com/en/) for fixing and [Kirill Anikin](https://digitalcompliance.ru/) as well as [Justin Lloyd](https://www.arena.net/en) for reporting
* [#5271](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/5269): Replaced deprecated use of "SkinTemplateNavigation". Thanks to [Abijeet Patro](https://thecurlybraces.com/)
* [#5269](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/5269): Replaced deprecated use of `JobQueueGroup::singleton()`. Thanks to [Abijeet Patro](https://thecurlybraces.com/)
* [#5260](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/5260): Replaced deprecated use of `LinksUpdate::mRecursive`. Thanks to [Niklas Laxström](https://laxstrom.name/blag/)
* [#5258](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/5258): Replaced rdbms methods with ResultWrapper methods. Thanks to [Abijeet Patro](https://thecurlybraces.com/)
* [#5257](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/5257): Replaced deprecated use of `Sanitizer::removeHTMLtags`. Thanks to [Abijeet Patro](https://thecurlybraces.com/)
* [#5256](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/5256): Replaced deprecated use of `LinksUpdate::mTemplate`. Thanks to [Abijeet Patro](https://thecurlybraces.com/)
* [#5255](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/5255): Replaced deprecated use of "LinksUpdateConstructed". Thanks to [Abijeet Patro](https://thecurlybraces.com/)
* [#5254](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/5254): Replaced deprecated use of `ParserOutput::getPageProperty`. Thanks to [Abijeet Patro](https://thecurlybraces.com/)
* [#5249](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/5249): Fixed "Is a new page" special property. Thanks to [Markus Wagenhofer](https://gesinn.it/)
* [#5246](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/5246): Removed unused variable. Thanks to [Markus Wagenhofer](https://gesinn.it/)
* [#5236](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/5236): Fixed "TypeErrors" to be thrown. Thanks to [wgevaert](https://github.com/wgevaert)
* [#5216](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/5216): Fixed Resource Loader warning when loading the factbox module. Thanks to [Jeroen De Dauw](https://entropywins.wtf/) & [Professional.Wiki](https://professional.wiki/).
* [#5206](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/5206): Made "ElasticFactory" object to be injected into "ElasticStore". Thanks to [Marijn van Wezel](https://github.com/marijnvanwezel)
* Localisation updates. Thanks to [translatewiki.net](https://translatewiki.net/) and its community of translators



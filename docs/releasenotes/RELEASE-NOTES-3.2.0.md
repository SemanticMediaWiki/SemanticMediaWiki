# Semantic MediaWiki 3.2.0

Released on September 7, 2020.

This release comes without a detailed list of changes. We will bring back the list in the next release.

## Compatibility

This release supports MediaWiki 1.31.x up to 1.35.x and PHP 7.1.x up to PHP 7.4.x. Compared to Semantic MediaWiki 3.1.x,
support for PHP 7.0 has been dropped and support for MediaWiki 1.34 and 1.35 was added.

You might encounter deprecation warnings on MediaWiki 1.35 if you have these warnings turned on (they are off by default).
We are working on these and will create follow up releases as we fix them.

For more detailed information, see the [compatibility matrix](../COMPATIBILITY.md#compatibility).

## New features and enhancements

### Awareness

* [#4554](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/4554) Adds the [entity issue panel](https://www.semantic-mediawiki.org/wiki/Help:Entity_issue_panel)
* [#4686](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/4686) Show message on property page if the property namespace is functionless
* [#4490](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/4490) Adds a baloon help to filtering field on propert pages

### Schemas

* [#4417](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/4417) Adds a navigation bar to the schema page
* [#4422](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/4422) Adds a search field with search highlighting to schema navigation
* [#4657](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/4657) Adds profile schema display to property page
* [#3749](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3749) Adds the schema group import mechanism
* [#4592](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/4592) Adds predefined property groups → **BREAKING**
* [#4404](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/4404) Adds the error reporting mechanism for schema validation
* [#4633](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/4633) Improves error reporting for schema validation

### Setup and configuration

* [#4438](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/4438) Improves [setup checking](https://www.semantic-mediawiki.org/wiki/Help:Setup_check)
* [#4684](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/4684) Adds the [coniguration preloading mechanism](https://www.semantic-mediawiki.org/wiki/Help:Configuration_preloading)
* [#4721](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/4721) Adds the `smweditor` user group and the `smw-vieweditpageinfo` permission
* [#4698](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/4698) Adds the `smw-viewjobqueuewatchlist` and `smw-viewentityassociatedrevisionmismatch` permissions
* [#4645](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/4645) Show registered schema types on special page "SemanticMediaWiki"

### Maintenance

#### Notifications and awareness

* [#4458](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/4458) Adds special page ["PendingTaskList"](https://www.semantic-mediawiki.org/wiki/Help:Special:PendingTaskList)
* [#4476](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/4476) Show maintenance alert for datastore optimization in the "Alerts" tab on special page "SemanticMediaWiki"
* [#4744](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/4744) Show maintenance alert for outdated entities in the "Alerts" tab on special page "SemanticMediaWiki"
* [#4468](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/4468) Show deprecation notices for configuration parameters in "Alerts" tab on special page "SemanticMediaWiki"
* [#4646](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/4646) Show configuration and endpoints for Elasticsearch on special page "SemanticMediaWiki"

→ See also the help page on [maintenance alerts](https://www.semantic-mediawiki.org/wiki/Help:Maintenance_alerts)

#### Scripts

* [#4403](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/4403) Improves client output for maintenance scripts
* [#4466](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/4466) Adds the ["runImport.php"](https://www.semantic-mediawiki.org/wiki/Help:Maintenance_script_runImport.php) maintenance script
* [#4484](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/4484) Adds the ["disposeOutdatedEntities.php"](https://www.semantic-mediawiki.org/wiki/Help:Maintenance_script_disposeOutdatedEntities.php) maintenance script
* [#4504](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/4504) Adds the `check-file-attachment`, `namespace`, `id` and `v` options to the ["rebuildElasticMissingDocuments.php"](https://www.semantic-mediawiki.org/wiki/Help:Maintenance_script_rebuildElasticMissingDocuments.php) maintenance script

### Miscellaneous

* [#4586](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/4586) Adds the `max-width` and `theme` parameters to the [`#info` parser function](https://www.semantic-mediawiki.org/wiki/Help:Adding_tooltips)
* [#4664](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/4664) Adds `namedargs` as alias for the `named args` parameter to relevant result formats (list formats, templatefile format, category format)

## Overview of all changes

All relevant pull requests and issue items (code improvements, bug fixes and internal changes) which were closed or addressed with this release were added to the [SMW 3.2.0 milestone](https://github.com/SemanticMediaWiki/SemanticMediaWiki/milestone/34?closed=1) for your further information. 

## Contributors

- 301 - James Hong Kong
- 118 - Jeroen De Dauw ([sponsor Jeroen](https://github.com/sponsors/JeroenDeDauw))
-  70 - The translator community translatewiki.net
-  52 - Karsten Hoffmeyer ([sponsor Karsten](https://github.com/sponsors/kghbln))
-   4 - Jaider Andrade Ferreira
-   2 - Fonata
-   2 - Stephan
-   1 - Alex Winkler
-   1 - C. Scott Ananian
-   1 - DannyS712
-   1 - Mark A. Hershberger
-   1 - Máté Szabó
-   1 - Niklas Laxström
-   1 - Peter Grassberger
-   1 - Robert Vogel
-   1 - Yuki Shira
-   1 - carlo66

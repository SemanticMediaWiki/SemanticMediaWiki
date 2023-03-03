# Semantic MediaWiki 4.0.1

Released on March 24, 2022.

## Summary

This is a [patch release](../RELEASE-POLICY.md), meaning that it contains only fixes and no breaking changes.

This release improves compatibility with MediaWiki 1.38, fixes a warning occurring on MediaWiki 1.36 and later, and
closes a minor HTML table generation issue.

Users of MediaWiki 1.36 and later are encouraged to upgrade.

## Changes

* Added support for _installation_ with MediaWiki 1.38 by merging in the Tesa library. This avoids a composer error
  that mentions wikimedia/cdb. Semantic MediaWiki does not officially support the unreleased MediaWiki 1.38 yet! Improvements
  made by [Jeroen De Dauw](https://entropywins.wtf/) from [Professional.Wiki](https://professional.wiki/).
* Improved MediaWiki 1.38 compatibility by using ParserOutput getPageProperty. By [C. Scott Ananian](https://github.com/cscott).
* Fixed warning occurring on MediaWiki 1.36 and above during serialization. By [Sébastien Beyou](https://github.com/Seb35)
* Fixed HTML table creation issue, where closing tags would be added to empty tables. By ["miriamschlindwein"](https://github.com/miriamschlindwein).
* Localisation updates from the translatewiki.net community of translators

## Upgrading

Just get the new version. No need to run update.php or any other migration scripts.

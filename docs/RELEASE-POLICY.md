# Release Policy

Semantic MediaWiki follows [Semantic Versioning](https://semver.org/).

We have these types of releases:

* Patch releases (third number changes) - only include fixes - upgrading is very safe
* Minor releases (second number changes) - new features but no breaking changes
* Major releases (first number change) - include breaking changes - upgrades need attention

### Breaking changes

We only make the following changes in major releases.

* Removal of features
* Breaking changes to features, such as syntax changes or parameter name changes
* Removal of support for versions of MediaWiki
* Changes that require running update.php or doing a data rebuild

This means you can be sure none
of these types of changes are included in patch releases (ie SMW 1.5.2 to 1.5.3) or minor
releases (ie SMW 1.5.3 to 1.6.0). You can thus safely run composer update to pull in
Semantic MediaWiki fixes and features, as long as you have the major version pinned.

### See also

* [Release notes](releasenotes/README.md#release-notes)
* [Compatibility matrix](COMPATIBILITY.md#compatibility)
* [Installation guide](INSTALL.md#installation-guide-brief)

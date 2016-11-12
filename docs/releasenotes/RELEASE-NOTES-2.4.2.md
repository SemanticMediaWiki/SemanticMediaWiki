# Semantic MediaWiki 2.4.2

Released on November 12nd, 2016.

## Bug fixes

* #1829 Only have the `DisplayTitlePropertyAnnotator` create an annotation in case `SMW_DV_WPV_DTITLE` is enabled
* #1883 Avoid mismatch in case `hasSubSemanticData` has been overridden as by `Sql3StubSemanticData`
* #1885 Fixed postgres bytea escape/unescape on blob fields
* #1887 Moved `Hooks:CanonicalNamespaces` to an earlier execution point
* #1897 Work around deprecated/removed `DatabaseBase::getSearchEngine`
* #1901 Make `enableSemantics` call `NamespaceManager`
* #1911 Improve compatibility with MediaWiki 1.28+

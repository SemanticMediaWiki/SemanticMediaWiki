# Semantic MediaWiki 2.4.2

Released on November 13th, 2016.

## Bug fixes

* #1829 Only have the `DisplayTitlePropertyAnnotator` create an annotation in case `SMW_DV_WPV_DTITLE` is enabled
* #1883 Avoided mismatch in case `hasSubSemanticData` has been overridden as by `Sql3StubSemanticData`
* #1885 Fixed postgres bytea escape/unescape on blob fields
* #1887 Moved `Hooks:CanonicalNamespaces` to an earlier execution point
* #1897 Worked around deprecated/removed `DatabaseBase::getSearchEngine`
* #1901 Made `enableSemantics` call `NamespaceManager`
* #1911 Improved compatibility with MediaWiki 1.28+

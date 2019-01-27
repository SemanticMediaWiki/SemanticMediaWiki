# Semantic MediaWiki 3.0.1

Released on January 25, 2019.

## Enhancements
* [#3566](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3566) as `af04255`: Extended the array of permissive URI schemes of [configuration parameter `$smwgURITypeSchemeList`](https://www.semantic-mediawiki.org/wiki/Help:$smwgURITypeSchemeList)
* [#3596](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3596) as `a6ccc2a`: Added [`$smwgConfigFileDir` configuration parameter](https://www.semantic-mediawiki.org/wiki/Help:$smwgConfigFileDir) allowing to specify the location for the [setup information file](https://www.semantic-mediawiki.org/wiki/Help:Setup_information_file)
* [#3597](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3597) as `c92b2ca`: Extended and improved information on the ["Upgrade Error Screen"](https://www.semantic-mediawiki.org/wiki/Help:Upgrade/Upgrade_and_setup_consistency) and made it localizable
* [#3611](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3611) as `8f1177a`: Added ["populateHashField.php" maintenance script](https://www.semantic-mediawiki.org/wiki/Help:Maintenance_script_populateHashField.php) to decouple mass conversions of database field "smw_hash" in the "smw_objects_ids" database table when upgrading the database for large wikis
* Many new translations for numerous languages by the communtity of [translatewiki.net](https://translatewiki.net/w/i.php?title=Special%3AMessageGroupStats&x=D&group=mwgithub-semanticmediawiki&suppressempty=1)

## Bug fixes and internal code changes
* [#3565](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3565) as `6f24bf6`: Added missing system message for the "templatefile" format
* [#3572](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3572) as `70f629e`: Fixed `HtmlForm::getForm` to support a string as result on special page "Ask"
* [#3573](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3573) as `a59c76c`: Modified tests to avoid "Call to a member function getSchema() on null" for MediaWiki 1.32 and later
* [#3578](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3578) as `484a4b5`: Made indexer apply `pg_unescape_bytea` for bytea/blob values on postgres
* [#3584](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3584) as `1205b87`: Added pipe detection in printrequest labels (`[[ ... | ... ]]`)
* [#3585](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3585) as `5d6d6ff`: Fixed "`strpos()`: Non-string needles ..." for PHP 7.3 and later
* [#3586](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3586) as `53655ed`: Fixed `#set_recurring_event` parser function to respect related configuration parameters and their settings
* [#3595](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3595) as `e9ed65e`: Fixed invalid user names using the mandatory interwiki prefix for MediaWiki 1.31 and later by unlinking them
* [#3599](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3599) as `732ef23`: Fixed "`fputcsv` ... delimiter must be a single character" for the "csv" format
* [#3607](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3607) as `4d9e5a7`: Fixed `#set_recurring_event` parser function to cause "Call to undefined method `SMWDIError::getJD()`"
* [#3608](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3608) as `b17526d`: Fixed "QueryResultSerializer" to handle `_qty` on chained properties
* [#3609](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3609) as `c005c6f`: Restored use of `$wgDBTableOptions` configuration parameter
* [#3616](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3616) as `4b0cfb7`: Made `isCapitalLinks` be set in `_wpg` description context
* [#3617](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3617) as `9152f94`: Made "SemanticDataLookup", use `DISTINCT` for non subject items
* [#3622](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3622) as `cfbd338`: Fixed `#set_recurring_event` parser function to allow monthly events start on a 30th and 31st of a month
* [#3628](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3628) as `e587291`: Improved commandline prompts for maintenance script "populateHashField.php"
* [#3630](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3630) as `79aee30`: Added extra _uri validation for `http:///`
* [#3631](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3631) as `0903c1b`: Fixed `ResultFormatNotFoundException` on untrimmed format strings
* [#3632](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3632) as `24d8bae`: Changed to using `0x003D` instead of `-3D` to encode `=`
* [#3633](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3633) as `f70339a`: Made the container subject be used as context to check uniqueness constaints
* [#3634](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3634) as `711e365`: Made "WikiPageValue" use the provided fixed namespace
* [a5a4a0d](https://github.com/SemanticMediaWiki/SemanticMediaWiki/commit/a5a4a0d1b05eb622749fe59a1d2be4be699aaed4) as `bea16a5`: Fixed "PHP Notice: Uncommitted DB writes (transaction from ...)"
* [8bc4443](https://github.com/SemanticMediaWiki/SemanticMediaWiki/commit/8bc4443a6a48682e74e94a014adfcd91cb6104a5) as `5a729d4`:  Fixed `get_headers` can return `false`
* [8ca1ec0](https://github.com/SemanticMediaWiki/SemanticMediaWiki/commit/8ca1ec05ef56144b1991c0381696a52687e39ed4) as `93cf100`: Made "PHP Warning: Class '`SMW\CategoryResultPrinter`' not found in ... Aliases.php" be avoided

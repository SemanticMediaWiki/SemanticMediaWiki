# Semantic MediaWiki 3.1

Released on September 23, 2019.

## Highlights

This release brings the following highlights:

- Support for tracking [attachment links](https://www.semantic-mediawiki.org/wiki/Help:Attachment_links) added. They display on special page "Browse" and in the factbox using an extra tab ([#3643](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3643), [#3652](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3652), [#3661](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3661), [#4147](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/4147))
- Elasticsearch [replication monitoring](https://www.semantic-mediawiki.org/wiki/Help:Replication_monitoring) introduced ([#3697](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3697), [#3700](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3700), [#3713](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3713))
- [Embedded query update](https://www.semantic-mediawiki.org/wiki/Help:Embedded_query_update) feature refactored and improved by a new dependency links validation and invalidation mechanism ([#3644](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3644), [#3831](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3831))
- Support for [constraint schemas](https://www.semantic-mediawiki.org/wiki/Help:Constraint_schema) added ([#3746](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3746), [#3829](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3829), [#3968](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3968), [#4033](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/4033), [#4047](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/4047))
- Support for annotation value [sequence maps](https://www.semantic-mediawiki.org/wiki/Help:Sequence_map) added ([#4226](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/4226))

## Compatibility

This release supports MediaWiki 1.31.x up to 1.33.x and PHP 7.0.x up to PHP 7.4.x. For more detailed information, see the [compatibility matrix](https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/docs/COMPATIBILITY.md).

## New features and enhancements

Changes to the data store are now triggered by introducing `DependencyLinksValidator` a mechanism to validate temporal attributes ([#3644](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3644), [#3831](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3831)). This refactored and improved the [embedded query update](https://www.semantic-mediawiki.org/wiki/Help:Embedded_query_update) feature.

### Setup

* [#3605](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3605) Conditionally create the full-text ([`smw_ft_search`](https://www.semantic-mediawiki.org/wiki/Table:smw_ft_search)) table
* [#3738](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3738) Show an "in maintenance" screen with information while the [upgrade](https://www.semantic-mediawiki.org/wiki/Help:Upgrade) is progressing
* [#4026](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/4026) Show relative upgrade progress on the "in maintenance" screen
* [#4119](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/4119) Added check for `SMW_EXTENSION_LOADED` to enforce `enableSemantics`
* [#4123](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/4123) Added `smwgDefaultStore` to upgrade key matrix hereby making it part of the upgrade key
* [#4170](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/4170) Added check whether the extension registration is complete or not
* [#4190](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/4190) Prevent "Uncaught Exception: It was attempted to load SemanticMediaWiki twice"

### Store

* [#3642](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3642) Extended maintenance script ["rebuildData.php"](https://www.semantic-mediawiki.org/wiki/rebuildData.php) to support the removal of outdated query links
* [#3686](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3686) Display of semantic statistics on special page "Statistics" was improved and extended
* [#3782](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3782) Added check for retired properties
* [#3803](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3803) SQLite, use text type for `o_hash` field
* [#3809](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3809) DataUpdater, use changed revision
* [#3822](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3822) Check `smw_hash` and update if necessary
* [#3887](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3887) Added check to detect and remove detached subobjects i in the rebuilder
* [#4063](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/4063) Added a prefetch cache and lookup capabilities to minimize required read queries when resolving result objects 

#### ElasticStore

* [#3637](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3637) Uses `keyword` as type for the `P:*.geoField` mapping
* [#3638](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3638) Added minimal index document for an empty bulk request
* [#3693](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3693) Relaxed link removal in raw text
* [#3697](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3697) Added replication monitoring (`indexer.monitor.entity.replication`) on per entity base and [#3713](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3713) (`indexer.monitor.entity.replication.cache_lifetime`)
* [#3699](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3699) Added length restriction to value inputs for a query construct  (`query.maximum.value.length`)
* [#3700](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3700) Show indicator placeholder for replication monitoring
* [#3763](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3763) Forced `FileIngestJob` to wait on the command line before executing the file indexing
* [#3777](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3777) Added `rev_id` as field for indexing to extend the [replication monitoring](https://www.semantic-mediawiki.org/wiki/Help:Replication_monitoring)
* [#3810](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3810) Check for associated revision
* [#3835](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3835) Added capabilities to record replication issues
* [#3999](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3999) Added support for inverse property + category subquery
* [#4018](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/4018) Added replication check  to confirm connection status with the Elasticsearch
* [#4019](https://github.com/SemanticMediaWiki/SemanticMediaWiki/issues/4019) Show the Elasticsearch status unconditionally on the dashboard
* [#4088](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/4088) Fixed handling of predefined properties keys
* [#4114](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/4114) Added the "rebuildElasticMissingDocuments.php" maintenance script to find missing entities (aka documents) from Elasticsearch and schedule `smw.update` jobs for those identified documents
* [#4126](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/4126) Added a monitoring on whether an adminstrator has run the rebuild index script after switching to the `ElasticStore` or not
* [#4155](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/4155) Fixed `PredefinedPropertyLabelMismatchException` on invalid predefined property matches
* [#4158](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/4158) Added `--only-update` option to the "rebuildElasticIndex.php" maintenance script to run an update without switching indices or initiating a rollover
* [#4208](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/4208) Fixed that only deleted subobjects on related entities are removed during an replication
* [#4230](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/4230), [#4231](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/4231) Fixed overriding `smw_rev`, `smw_touched` on predefined properties during the setup and show user readable property labels
* [#4240](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/4240) Added support for running the rebuild index as part of the "updateEntityCollation.php" maintenance script execution
* [#4250](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/4250) Improve ICU related collation sorting

### Query

* [#3644](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3644) Added [`DependencyLinksValidator`](https://www.semantic-mediawiki.org/wiki/Help:Embedded_query_update), refactored the update logic, and improved the detection of outdated dependencies (see also #4265)
* [#3665](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3665) Added support for the `ctrl+q` shortkey to start the query process on special page "Ask"
* [#4064](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/4064) Fixed use of `+offset=` as printout parameter
* [#4137](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/4137) Added maintenance script ["updateQueryDependencies.php"](https://www.semantic-mediawiki.org/wiki/Help:UpdateQueryDependencies.php) to update the `smw_query_links` table on entities that contain embedded queries

#### Result formats

* [#3620](https://github.com/SemanticMediaWiki/SemanticMediaWiki/issues/3620) Fixed result printer "csv" to not ignore omitting of units with display formatter `#-n`
* [#3650](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3650) Added support for `noimage` as output option for entity (aka. page) links
* [#3734](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3734) Moved remaining result printers to new namespace
* [#3760](https://github.com/SemanticMediaWiki/SemanticMediaWiki/issues/3760) Removed `template arguments` and added `named args` to the "templatefile" result printer
* [#3793](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3793) Added support for (ul/ol) as value separator in result format "table"
* [#3873](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3873) Use canonical property label in a template context

### Misc

* [#3621](https://github.com/SemanticMediaWiki/SemanticMediaWiki/issues/3621) Added support for hidden annotation
* [#3643](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3643) Added support for tracking [attachment links](https://www.semantic-mediawiki.org/wiki/Help:Attachment_links) via the `_ATTCH_LINK` property
* [#3652](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3652) Added [attachment display](https://www.semantic-mediawiki.org/wiki/Help:Attachment_links) in the `Factbox`, [#3661](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3661) added suport for sorting attachment list columns, [#4147](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/4147) added a `Is local` column to indicate whether a file is local or not
* [#3678](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3678) Decodes `#` in a record text field
* [#3696](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3696) Highlighter to decode `<` and `>` in content
* [#3717](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3717) Highlighter to decode `\n` in content
* [#3718](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3718) Extended tables to find and remove duplicates
* [#3720](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3720) Added special page "MissingRedirectAnnotations" to show [missing redirect annotations](https://www.semantic-mediawiki.org/wiki/Help:Missing_redirect_annotations)
* [#3733](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3733) Added support for enforced property [parent type inheritance](https://www.semantic-mediawiki.org/wiki/Help:Mandatory_parent_datatype_inheritance) (disabled by default, can be enabled using the [`$smwgMandatorySubpropertyParentTypeInheritance`](https://www.semantic-mediawiki.org/wiki/Help:$smwgMandatorySubpropertyParentTypeInheritance) setting)
* [#3735](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3735) Added declaration check for when multiple `Has fields` declarations are used
* [#3747](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3747) Added an option to define `LAST_EDITOR`, `IS_IMPORTER`
* [#3749](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3749) Added [`PROPERTY_GROUP_SCHEMA`](https://www.semantic-mediawiki.org/wiki/Help:Schema/Type/PROPERTY_GROUP_SCHEMA) as schema type to to define [property groups](https://www.semantic-mediawiki.org/wiki/Help:Property_group) using a JSON schema
* [#3751](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3751) Added `?`, `*`, and `!` as invalid characters for a property name
* [#3756](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3756) Added properties count in use for a specific type to special page "Types"
* [#3779](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3779) Added normalization for `__` in propery names
* [#3790](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3790) Highlighter, remove trailing line feeds
* [#3792](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3792) Added the `_ERR_TYPE` predefine property
* [#3795](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3795) Decode values before comparing (&lt;/&gt;,</>)
* [#3816](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3816) Show filter count on property page
* [#3817](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3817) ExternalFormatterUri to replace ` ` with `_`
* [#3818](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3818) External identifier to support multi substitutes using {...}
* [#3819](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3819) Support `Has fields` to allow property names with `:`
* [#3821](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3821) Support schema change to push a change propagation dispatch job
* [#3864](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3864) Added core hook to support `--skip-optimize` in "update.php" again with MW 1.33+
* [#3866](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3866) Added check for [remnant entities](https://www.semantic-mediawiki.org/wiki/Help:Remnant_entities), `$smwgCheckForRemnantEntities `
* [#3869](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3869) Minimize redirect lookups on properties 
* [#3905](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3905) Added maintenance script "purgeEntityCache.php" to purge all cache entries (including associates) that use the `EntityCache` interface
* [#3920](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3920) Added `DisplayTitleFinder` to support a prefetch lookup so that titles can be fetched and cached in bulk to minimize the required database queries
* [#3922](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3922) Added the `--auto-recovery` option to maintenance scripts "rebuildElasticIndex.php" and "rebuildData.php"
* [#3928](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3928) Added `TableStatistics` to dashboard to gather some inforamtion of the table usage
* [#3940](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3940) Added support for `callable` in `$smwgFallbackSearchType` to allow using `SMWSearch` in tandem with for example `CirrusSearch`
* [#3945](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3945) Added support for the full pipe trick to the WikiPage datavalue
* [#3960](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3960) Added `--namespace` option filter to maintenance script "rebuildData.php"
* [#3965](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3965) Show usage (properties linked to a schema) for schemta that define a `usage_lookup`
* [#4042](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/4042) Added support for `#` as formatting directive to create a no link
* [#4048](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/4048) Added new `smwtable-clean` table CSS
* [#4151](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/4151) Added `--report-runtime` and `--with-maintenance-log` options to the "removeDuplicateEntities.php" maintenance script
* [#4069](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/4152) Added `--with-maintenance-log` option to the "rebuildElasticIndex.php" maintenance script
* [#4143](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/4143) Added support for `count` and `further results` to the [remote request](https://www.semantic-mediawiki.org/wiki/Help:Remote_request)
* [#4144](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/4144) Added schema summary
* [#4150](https://github.com/SemanticMediaWiki/SemanticMediaWiki/issues/4150) Fixed `enableSemantics` exception where external functions try to access Semantic MediaWiki that hasn't been enabled
* [#4223](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/4223) Improved the option display on the preference page
* [#4226](https://github.com/SemanticMediaWiki/SemanticMediaWiki/issues/4226) Introduced the concept of `sequence map` for annotation values
* [#4244](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/4244) Adding sorting of properties by label (not by key) on special page "Browse"
* [#4281](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/4281) Added wider search radius for `completionSearch`

#### Constraints

* [#3746](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3746) Added [`PROPERTY_CONSTRAINT_SCHEMA`] as a new schema type and introduce an approach by assigning a `[[Constraint schema::...]]` to a property
* [#3829](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3829) Added `_CONSTRAINT_SCHEMA` property (see #3746)
* [#3843](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3843) Show compiled constraint schema on property page
* [#3908](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3908) Added `unique_value_constraint`
* [#3968](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3968) Added support for displaying [`constraint` errors](https://www.semantic-mediawiki.org/wiki/Help:Constraint_error) using an [page indicator](https://www.mediawiki.org/wiki/Help:Page_status_indicators)
* [#3969](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3969) Added support for `custom_constraint` to enable users to define custom constraints via a `Constraint` interface and the provided hook
* [#3970](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3970) Added `non_negative_integer` constraint
* [#3981](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3981) Added `must_exists` constraint
* [#3989](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3989) Extended the constraint `ErrorLookup` to scan subobjects and cache the lookup, also added `smwgCheckForConstraintErrors` setting
* [#4010](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/4010) Added `single_value_constraint`
* [#4033](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/4033) Added support for [`CLASS_CONSTRAINT_SCHEMA`](https://www.semantic-mediawiki.org/wiki/Help:Schema/Type/CLASS_CONSTRAINT_SCHEMA)
* [#4047](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/4047) Added `SpecialConstraintErrorList` to display errors classified as constraint
* [#4069](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/4069) Added `shape_constraint`

## Bug fixes

* [#3568](https://github.com/SemanticMediaWiki/SemanticMediaWiki/issues/3568) Fixed "Warning: Cannot modify header information - headers already sent by" on a remote request
* [#3750](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3750) Checks whether the sort argument can be accessed or not in the datatable
* [#3839](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3839) Fixed display of time offset display for non date items on the property page
* [#3840](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3840) Fixed special page "Browse" and display of properties when more than 200 items are available
* [#3888](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3888) Fixed `MWUnknownContentModelException` while running maintenance script "rebuildData.php"
* [#3938](https://github.com/SemanticMediaWiki/SemanticMediaWiki/issues/3938) Fixed "Index name must always be lower case" in connection with Elasticsearch
* [#3914](https://github.com/SemanticMediaWiki/SemanticMediaWiki/issues/3914) Fixed "Cannot override final method Job::getTitle"
* [#4022](https://github.com/SemanticMediaWiki/SemanticMediaWiki/issues/4022) Fixed "Call to undefined method ... transformSearchTerm"
* [#4035](https://github.com/SemanticMediaWiki/SemanticMediaWiki/issues/4035) Fixed "DispatchContext.php .. subject is unknown"
* [#4071](https://github.com/SemanticMediaWiki/SemanticMediaWiki/issues/4071) Fixed "Minus prepended to queried negative values stored with datatype Number"
* [#4077](https://github.com/SemanticMediaWiki/SemanticMediaWiki/issues/4077) Fixed "Maintenance logging no longer works due to missing user"
* [#4091](https://github.com/SemanticMediaWiki/SemanticMediaWiki/issues/4091) Fixed "HTMLInfoField.php: 'default' must be a FieldLayout or subclass when using 'rawrow'"
* [#4110](https://github.com/SemanticMediaWiki/SemanticMediaWiki/issues/4110) Fixed "trailing spaces" in JSON language files
* [#4111](https://github.com/SemanticMediaWiki/SemanticMediaWiki/issues/4111) Fixed "TypeError SearchDatabase.php: Argument 1 passed to SearchDatabase ... must implement interface ... ILoadBalancer ..."
* [#4113](https://github.com/SemanticMediaWiki/SemanticMediaWiki/issues/4113) Fixed "Declaration of SMW\MediaWiki\Search\SearchResult::getTextSnippet($terms) should be compatible with SearchResult::getTextSnippet($terms = Array)"
* [#4160](https://github.com/SemanticMediaWiki/SemanticMediaWiki/issues/4160) Fixed "SQL error `... AND ( AND o_id LIKE '%input%') ...` when matching a string using `Store::getPropertyValues`"
* [#4205](https://github.com/SemanticMediaWiki/SemanticMediaWiki/issues/4205) Fixed "NavigationLinksWidget.php ... PHP Warning: A non-numeric value encountered"
* [#4210](https://github.com/SemanticMediaWiki/SemanticMediaWiki/issues/4210) Fixed "MediumSpecificBagOStuff reports  ... Serialization of 'Closure' is not allowed"
* [#4255](https://github.com/SemanticMediaWiki/SemanticMediaWiki/issues/4255) Fixed `allows value` declaration for record types
* [#4270](https://github.com/SemanticMediaWiki/SemanticMediaWiki/issues/4270) Fixed "Error: Call to undefined method RevisionSearchResult ..."

## Breaking changes and deprecations

* [#3808](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3808) Removed `CachingEntityLookup`
* [#3995](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3995) Disabled access to `Title` related methods in the `WikiPageValue`
* [#3402](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3402) Removed long deprecated functions from `SMWQueryProcessor`

## Other changes

* [#3580](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3580) Removed HHVM from the test matrix (implicitly it means that HHVM is no longer supported)
* [#3612](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3612) Added `FieldType::TYPE_ENUM` support
* [#3666](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3666) Uses HTML instead of JS for the SMWSearch namespace buttons
* [#3675](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3675) Support definition of field index type
* [#3682](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3682) Removed `IsFileCacheable` hook usage
* [#3685](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3685) Replaced qTip with tippy.js (3.4+) (#3811, #3812, #3813)
* [#3712](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3712) Uses `smw_rev` field to check if an update is skippable
* [#3721](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3721) Added index hint for page types
* [#3723](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3723) Added prefetch support for the property value list retrievable
* [#3770](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3770) Extended `ParserAfterTidy` hook event listening
* [#3780](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3780) Added `Database::beginSectionTransaction` due to MW 1.33
* [#3801](https://github.com/SemanticMediaWiki/SemanticMediaWiki/issues/3801) Class and namespace reorg
* [#3792](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3792) Added the `ProcessingError` interface to describe  error types
* [#3808](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3808) Removed `CachingEntityLookup`
* [#3807](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3808) Added `SMW::Event::RegisterEventListeners` hook
* [#3815](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3815) EntityValidator
* [#3823](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3823) Added 'jquery.async' as local copy
* [#3830](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3830) Added `Constraint` interface and `ConstraintCheckRunner`
* [#3895](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3895) Added the `SMW::SQLStore::Installer::BeforeCreateTablesComplete` hook
* [#3897](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3897) Added `SMW::RevisionGuard::*` hooks
* [#3924](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3924) Removed `SMWSQLStore3Readers`
* [#4066](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/4066) Moved `QueryResult` and `ResultArray`
* [#4131](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/4131) `LoadBalancerConnectionProvider` to rely on `getConnectionRef`
* [#4169](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/4169) Added the `SMW::Parser::AfterLinksProcessingComplete` hook to address things like [#3651](https://github.com/SemanticMediaWiki/SemanticMediaWiki/issues/3651)
* [#4189](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/4189) Isolated `smw_proptable_hash` handling
* [#4192](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/4192) Moved `SMWSQLStore3` to `SMW\SQLStore\SQLStore`
* [#4194](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/4194) Moved `SMWSQLStore3Writers` to `SMW\SQLStore\SQLStoreUpdater`
* [#4200](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/4200) Moved `SMWSql3SmwIds` to `SMW\SQLStore\EntityStore\EntityIdManager`
* [#4222](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/4222) Added exception handling to ensure that errors are logged during a deferred update 
* [#4240](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/4240) Added the  `SMW::Maintenance::AfterUpdateEntityCollationComplete` hook
* [#4273](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/4273) Added `ResultPrinterDependency` interface

## Contributors

- 650 – James Hong Kong
-  88 – translatewiki.net for the translator community
-  48 – Jeroen De Dauw
-  41 – Karsten Hoffmeyer
-   4 – DannyS712
-   2 – Bernhard Krabina
-   2 – Mark A. Hershberger
-   2 – Máté Szabó
-   2 – Zoran Dori
-   2 – Alexander Gesinn
-   1 – Alex Winkler
-   1 – Brett Zamir
-   1 – Clara
-   1 – Jaider Andrade Ferreira
-   1 – Morgon Kanter
-   1 – NIKITA
-   1 – Peter Grassberger
-   1 – Sébastien Beyou
-   1 – Timo Tijhof
-   1 – Tisza Gergő

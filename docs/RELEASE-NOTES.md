# Semantic MediaWiki 3.1

Not a release yet. A release is tentatively planned for Q2 2019.

## Compatibility

Please find relevant notes about the platform and database compatibility for this release in the [COMPATIBILITY](https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/docs/COMPATIBILITY.md) document.

## Highlights

- Attachment links and factbox display
- Elasticsearch replication monitoring
- Dependency links validation and invalidation
- Add `[[Constraint schema::...]]` to a property

## New features and enhancements

Changes to the DB are triggered by [#3644](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3644) Introduce `DependencyLinksValidator`, refactor update logic (#3831). 

### Setup

* [#3605](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3605) Conditionally create the full-text ([`smw_ft_search`](https://www.semantic-mediawiki.org/wiki/Table:smw_ft_search)) table
* [#3738](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3738) Show an "in maintenance" message while the [upgrade](https://www.semantic-mediawiki.org/wiki/Help:Upgrade) is progressing

### Store

* [#3642](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3642) Extended [`rebuildData.php`](https://www.semantic-mediawiki.org/wiki/rebuildData.php) to support the removal of outdated query links
* [#3686](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3686) Improved statistics output
* [#3782](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3782) Added check for retired properties
* [#3803](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3803) SQLite, use text type for `o_hash` field
* [#3809](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3809) DataUpdater, use changed revision
* [#3822](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3822) Check `smw_hash` and update if necessary

#### ElasticStore

* [#3637](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3637) Uses `keyword` as type for the `P:*.geoField` mapping
* [#3638](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3638) Added minimal index document for an empty bulk request
* [#3693](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3693) Relaxed link removal in raw text
* [#3697](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3697) Added replication monitoring (`indexer.monitor.entity.replication`) on per entity base and [#3713](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3713) (`indexer.monitor.entity.replication.cache.lifetime`)
* [#3699](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3699) Added length restriction to value inputs for a query construct  (`query.maximum.value.length`)
* [#3763](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3763) Forced `FileIngestJob` to wait on the command line before executing the file indexing
* [#3777](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3777) Added `rev_id` as field for indexing to extend the [replication monitoring](https://www.semantic-mediawiki.org/wiki/Help:Replication_monitoring)
* [#3810](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3810) Check for associated revision
* [#3835](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3835)


### Query

* [#3644](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3644) Added [`DependencyLinksValidator`](https://www.semantic-mediawiki.org/wiki/Help:Embedded_query_update), refactored the update logic, and improved the detection of outdated dependencies
* [#3665](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3665) Added support for the `ctrl+q` shortkey to start the query process in `Special:Ask`

#### Result formats

* [#3650](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3650) Added support for `noimage` as output option for entity (aka. page) links
* [#3734](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3734) Moved remaining result printers to new namespace
* [#3793](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3793) Added support for (ul/ol) as value separator in `format=table`

### API

...

### Misc

* [#3621](https://github.com/SemanticMediaWiki/SemanticMediaWiki/issues/3621) Added support for hidden annotation
* [#3643](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3643) Added support for tracking [attachment links](https://www.semantic-mediawiki.org/wiki/Help:Attachment_links) via the `_ATTCH_LINK` property
* [#3652](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3652) Added [attachment display](https://www.semantic-mediawiki.org/wiki/Help:Attachment_links) in the `Factbox` and [#3661](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3661) added suport for sorting attachment list columns 
* [#3678](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3678) Decodes `#` in a record text field
* [#3696](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3696) Highlighter to decode `<` and `>` in content
* [#3717](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3717) Highlighter to decode `\n` in content
* [#3718](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3718) Extended tables to find and remove duplicates 
* [#3720](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3720) Added `Special:MissingRedirectAnnotations` to show [missing redirect annotations](https://www.semantic-mediawiki.org/wiki/Help:Missing_redirect_annotations)
* [#3733](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3733) Added support for enforced property [parent type inheritance](https://www.semantic-mediawiki.org/wiki/Help:Mandatory_parent_datatype_inheritance) (disabled by default, can be enabled using the [`$smwgMandatorySubpropertyParentTypeInheritance`](https://www.semantic-mediawiki.org/wiki/Help:$smwgMandatorySubpropertyParentTypeInheritance) setting)
* [#3735](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3735) Added declaration check for when multiple `Has fields` declarations are used
* [#3746](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3746) Add [`PROPERTY_CONSTRAINT_SCHEMA`] as a new schema type and introduce an approach by assigning a [[Constraint schema::...]] to a property
* [#3746](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3746)
* [#3747](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3747) Added an option to define `LAST_EDITOR`, `IS_IMPORTER`
* [#3749](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3749) Added [`PROPERTY_GROUP_SCHEMA`](https://www.semantic-mediawiki.org/wiki/Help:Schema/Type/PROPERTY_GROUP_SCHEMA) as schema type to to define [property groups](https://www.semantic-mediawiki.org/wiki/Help:Property_group) using a JSON schema
* [#3751](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3751) Added `?`, `*`, and `!` as invalid characters for a property name
* [#3756](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3756) Added properties count in use for a specific type to `Special:Types`
* [#3779](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3779) Added normalization for `__` in propery names
* [#3790](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3790) Highlighter, remove trailing line feeds
* [#3792](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3792) Added the `_ERR_TYPE` predefine property
* [#3795](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3795) Decode values before comparing (&lt;/&gt;,</>)
* [#3816](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3816) Show filter count on property page
* [#3817](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3817) ExternalFormatterUri to replace ` ` with `_`
* [#3818](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3818) External identifier to support multi substitutes using {...}
* [#3819](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3819) Support `Has fields` to allow property names with `:`
* [#3821](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3821) Support schema change to push a change propagation dispatch job
* [#3829](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3829)
* [#3843](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3843)
* [#3864](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3864) Added core hook to suppport `--skip-optimize` in `update.php` again with MW 1.33+
* [#3866](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3866) [remnant entities](https://www.semantic-mediawiki.org/wiki/Help:Remnant_entities), `$smwgCheckForRemnantEntities `

## Bug fixes

* [#3750](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3750) Checks whether the sort argument can be accessed or not in the datatable
* [#3839](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3839)
* [#3840](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3840) Fixed `Special:Browse` and display of properties when more than 200 are available

## Breaking changes and deprecations

* [#3808](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3808) Rem CachingEntityLookup

## Other changes

* [#3580](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3580) Removed HHVM from the test matrix (implicitly it means that HHVM is no longer supported)
* [#3612](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3612) Added `FieldType::TYPE_ENUM` support
* [#3666](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3666) Uses HTML instead of JS for the SMWSearch namespace buttons
* [#3675](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3675) Support definition of field index type
* [#3682](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3682) Removed `IsFileCacheable` hook usage
* [#3683](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3683) Added the `SMW::SQLStore::Installer::AddAuxiliaryIndicesBeforeCreateTablesComplete` hook
* [#3685](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3685) Replaced qTip with tippy.js (3.4+) (#3811, #3812, #3813)
* [#3712](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3712) Uses `smw_rev` field to check if an update is skippable
* [#3721](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3721) Added index hint for page types
* [#3723](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3723) Added prefetch support for the property value list retrievable
* [#3739](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3739) Added the `SMW::Factbox::OverrideRevisionID` hook
* [#3762](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3762) Added the `SMW::DataUpdater::SkipUpdate` hook
* [#3763](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3763) Added the `SMW::ElasticStore::FileIndexer::ChangeFileBeforeIngestProcessComplete` hook
* [#3770](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3770) Extended `ParserAfterTidy` hook event listening
* [#3780](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3780) Added `Database::beginSectionTransaction` due to MW 1.33
* [#3801](https://github.com/SemanticMediaWiki/SemanticMediaWiki/issues/3801) Class and namespace reorg
* [#3792](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3792) Added the `ProcessingError` interface to describe  error types
* [#3808](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3808)
* [#3807](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3808) Added `SMW::Event::RegisterEventListeners` hook
* [#3815](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3815) EntityValidator
* [#3823](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3823)
* [#3830](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3830)

## Contributors

...

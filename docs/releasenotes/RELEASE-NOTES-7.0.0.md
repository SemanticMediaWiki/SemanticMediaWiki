# Semantic MediaWiki 7.0.0

Released on TBD.

This release adds MediaWiki 1.45 support and removes long-deprecated APIs, vendored libraries, and legacy dependencies in favor of MediaWiki core services. If you maintain an extension or integration that depends on SMW, review the [breaking changes](#breaking-changes) before upgrading.

## Compatibility

* Added support for MediaWiki 1.45 (new compared to SMW 6.0.x, which supported up to 1.44)
* Compatible with PHP 8.1 up to 8.4 and MediaWiki 1.43 up to 1.45

For more detailed information, see the [compatibility matrix](../COMPATIBILITY.md#compatibility).

## Changes

### Breaking changes

**Configuration and runtime:**

* **Fulltext search reindex required.** The vendored `Onoi\Tesa` text sanitizer has been replaced with PHP `intl` built-ins. If you have `smwgEnabledFulltextSearch` enabled, run `rebuildFulltextSearchTable.php` after upgrading. Transliteration now uses ICU instead of a static mapping table, which produces minor differences for some characters (e.g., German ü→u instead of ü→ue).
* **SPARQL HTTP configuration removed.** The `$smwgSparqlRepositoryConnectorForcedHttpVersion` setting no longer exists. SPARQL store connectors and `RemoteRequest` now use MediaWiki core's `HttpRequestFactory` for HTTP version negotiation. The `mediawiki/http-request` (`Onoi\HttpRequest`) dependency has been dropped.
* **`loadDefaultConfigFrom()` removed.** The method on the return value of `enableSemantics()` has been removed and will fatal if called. Replace with a direct `require`:

  ```php
  // Before (broken — will fatal)
  enableSemantics( 'example.org' )->loadDefaultConfigFrom( 'media.php' );

  // After
  wfLoadExtension( 'SemanticMediaWiki' );
  require "$IP/extensions/SemanticMediaWiki/data/config/media.php";
  ```

  Note: `enableSemantics()` itself still exists but is deprecated as a no-op (see Deprecations).

* **Legacy job aliases removed.** All job types must now use their `smw.*` names. The following aliases no longer work:

  | Removed alias | Use instead |
  |---|---|
  | `SMW\UpdateJob` | `smw.update` |
  | `SMW\RefreshJob` | `smw.refresh` |
  | `SMW\UpdateDispatcherJob` | `smw.updateDispatcher` |
  | `SMW\FulltextSearchTableUpdateJob` | `smw.fulltextSearchTableUpdate` |
  | `SMW\EntityIdDisposerJob` | `smw.entityIdDisposer` |
  | `SMW\PropertyStatisticsRebuildJob` | `smw.propertyStatisticsRebuild` |
  | `SMW\FulltextSearchTableRebuildJob` | `smw.fulltextSearchTableRebuild` |
  | `SMW\ChangePropagationDispatchJob` | `smw.changePropagationDispatch` |
  | `SMW\ChangePropagationUpdateJob` | `smw.changePropagationUpdate` |
  | `SMW\ChangePropagationClassUpdateJob` | `smw.changePropagationClassUpdate` |
  | `SMWUpdateJob` | `smw.update` |
  | `SMWRefreshJob` | `smw.refresh` |

**Dependencies and autoloading:**

* Removed the `mediawiki/parser-hooks` dependency.
* Removed `psr/log` from `composer.json`. Extensions that relied on SMW pulling in `psr/log` transitively must declare it in their own `composer.json`.
* Removed the root `DefaultSettings.php` shim (deprecated since 4.0.0). Use `SemanticMediaWiki::getDefaultSettings()` instead.
* Removed `Defines.php`.
* **`includes/` directory removed.** All classes have moved to `src/` under new namespaces (`DataItems/`, `DataValues/`, `Export/`, `Formatters/`, `Query/`, `QueryPages/`, `MediaWiki/Specials/`). Class aliases are provided for the transition (see Deprecations below), but code that loaded files by path (e.g., `require .../includes/dataitems/...`) will break.

**Hooks:**

* **`SMW::GroupPermissions::BeforeInitializationComplete` hook removed.** Permission rights and group assignments are now declared in `extension.json`. Extensions that modified SMW permissions via this hook should use MediaWiki's standard `$wgGroupPermissions` override in `LocalSettings.php` instead.

**Removed APIs:**

* Removed `getTextFromContent()`, `replacePrefixes()`, and `textAlreadyUpdatedForIndex()` from `ExtendedSearchEngine`, matching their removal from MediaWiki core's `SearchEngine`.
* Removed unused internal classes: `HtmlVTabs`, `SchemaParameterTypeMismatchException`, `CleanUpTables`, and `FlatSemanticDataSerializer`.
* Removed long-deprecated code originally scheduled for removal:
  * `smwfNormalTitleText()` — use `Localizer::getInstance()->normalizeTitleText()` (deprecated since 3.2)
  * `smwfNumberFormat()` — use `IntlNumberFormatter::getInstance()->getLocalizedFormattedNumber()` (deprecated since 2.1)
  * `SMW_HEADER_TOOLTIP`, `SMW_HEADER_SORTTABLE`, `SMW_HEADER_STYLE` constants and the numeric-id branch in `Outputs::requireHeadItem()`
  * `TimeValue::getXMLSchemaDate()` — use `getISO8601Date()`
  * `ValueDescription::getDataValue()` — use `getDataItem()`
  * `ResultPrinter::getParameters()` — use `getParamDefinitions()`
  * `ParserData::setData()` / `getData()` — use `setSemanticData()` / `getSemanticData()`
  * `Subobject::setSemanticData()` — use `setEmptyContainerForId()`
  * `PropertyRegistry::findPropertyLabel()` — use `findPropertyLabelById()`
  * `PropertyRegistry::getPredefinedPropertyTypeId()` — use `getPropertyValueTypeById()`
  * `PropertyRegistry::findPropertyId()` — use `findPropertyIdByLabel()`
  * `ParserFunctionFactory::getSubobjectParser()` — use `newSubobjectParserFunction()`
  * `ParserFunctionFactory::getRecurringEventsParser()` — use `newRecurringEventsParserFunction()`
  * `smwInitProperties` hook — use `SMW::Property::initProperties`
  * `SMWSQLStore3::deleteSubjectBefore` / `deleteSubjectAfter` hooks — use `SMW::SQLStore::BeforeDeleteSubjectComplete` / `AfterDeleteSubjectComplete`
  * `ParserParameterProcessor::getFirst()` — use `getFirstParameter()`
  * `DataValue::prepareValue()` — use `DescriptionBuilder`
  * `HashBuilder::createHashIdFromSegments()` — use `createFromSegments()`
  * `DataValueFactory::newDataItemValue()` — use `newDataValueByItem()`
  * `DataValueFactory::newPropertyObjectValue()` — use `newDataValueByProperty()`
  * `DataValueFactory::newTypeIdValue()` — use `newDataValueByType()`
  * `DataValueFactory::newPropertyValue()` — use `newDataValueByText()`
  * `InMemoryPoolCache::getPoolCacheFor()` — use `getPoolCacheById()`
  * `ParserParameterProcessor::getParameterValuesFor()` — use `getParameterValuesByKey()`
  * `Localizer::getLanguageCodeFrom()` — use `getAnnotatedLanguageCodeFrom()`
  * `ServicesFactory::newQueryParser()` — use `QueryFactory::newQueryParser()`
  * `DataTypeRegistry::getDataItemId()` — use `getDataItemByType()`
  * `DataTypeRegistry::getDefaultDataItemTypeId()` — use `getDefaultDataItemByType()`
  * `QueryResult::getLink()` — use `getQueryLink()`

### Deprecations

* `enableSemantics()` is deprecated and now a no-op. `wfLoadExtension( 'SemanticMediaWiki' )` alone is sufficient to install SMW, aligning with standard MediaWiki extension conventions. The RDF namespace URI is now auto-derived from `Special:URIResolver` when not explicitly set. Users who set a custom `$smwgNamespace` in `LocalSettings.php` are unaffected.
* The following class aliases are deprecated. They will be removed in a future release. Update any code referencing these to use the new namespaced class names:

  | Deprecated alias | New class name |
  |---|---|
  | `SMWDIBlob` | `SMW\DataItems\Blob` |
  | `SMWDIBoolean` | `SMW\DataItems\Boolean` |
  | `SMW\DIConcept` | `SMW\DataItems\Concept` |
  | `SMWDIContainer` | `SMW\DataItems\Container` |
  | `SMWDataItem` | `SMW\DataItems\DataItem` |
  | `SMWDIError` | `SMW\DataItems\Error` |
  | `SMWDIGeoCoord` | `SMW\DataItems\GeoCoord` |
  | `SMWDINumber` | `SMW\DataItems\Number` |
  | `SMW\DIProperty` | `SMW\DataItems\Property` |
  | `SMWDITime` | `SMW\DataItems\Time` |
  | `SMWDIUri` | `SMW\DataItems\Uri` |
  | `SMW\DIWikiPage` | `SMW\DataItems\WikiPage` |
  | `SMWDataValue` | `SMW\DataValues\DataValue` |
  | `SMWConceptValue` | `SMW\DataValues\ConceptValue` |
  | `SMWErrorValue` | `SMW\DataValues\ErrorValue` |
  | `SMWNumberValue` | `SMW\DataValues\NumberValue` |
  | `SMWPropertyListValue` | `SMW\DataValues\PropertyListValue` |
  | `SMWQuantityValue` | `SMW\DataValues\QuantityValue` |
  | `SMWRecordValue` | `SMW\DataValues\RecordValue` |
  | `SMWTimeValue` | `SMW\DataValues\TimeValue` |
  | `SMWURIValue` | `SMW\DataValues\URIValue` |
  | `SMWWikiPageValue` | `SMW\DataValues\WikiPageValue` |
  | `SMWExpData` | `SMW\Export\ExpData` |
  | `SMWExportController` | `SMW\Export\ExportController` |
  | `SMWExporter` | `SMW\Export\Exporter` |
  | `SMWQuery` | `SMW\Query\Query` |
  | `SMWQueryProcessor` | `SMW\Query\QueryProcessor` |
  | `SMW\QueryPrinterFactory` | `SMW\Query\QueryPrinterFactory` |
  | `SMW\PropertiesQueryPage` | `SMW\QueryPages\PropertiesQueryPage` |
  | `SMW\QueryPage` | `SMW\QueryPages\QueryPage` |
  | `SMW\UnusedPropertiesQueryPage` | `SMW\QueryPages\UnusedPropertiesQueryPage` |
  | `SMW\WantedPropertiesQueryPage` | `SMW\QueryPages\WantedPropertiesQueryPage` |
  | `SMWSpecialOWLExport` | `SMW\MediaWiki\Specials\SpecialOWLExport` |
  | `SMWSpecialTypes` | `SMW\MediaWiki\Specials\SpecialTypes` |
  | `SMW\SpecialConcepts` | `SMW\MediaWiki\Specials\SpecialConcepts` |
  | `SMW\SpecialPage` | `SMW\MediaWiki\Specials\SpecialPage` |
  | `SMW\SpecialProperties` | `SMW\MediaWiki\Specials\SpecialProperties` |
  | `SMW\SpecialUnusedProperties` | `SMW\MediaWiki\Specials\SpecialUnusedProperties` |
  | `SMW\SpecialWantedProperties` | `SMW\MediaWiki\Specials\SpecialWantedProperties` |
  | `SMW\MessageFormatter` | `SMW\Formatters\MessageFormatter` |
  | `SMWInfolink` | `SMW\Formatters\Infolink` |
  | `SMWPageLister` | `SMW\Formatters\PageLister` |
  | `SMW\Highlighter` | `SMW\Formatters\Highlighter` |
  | `SMW\RecurringEvents` | `SMW\Utils\RecurringEvents` |
  | `SMW\SemanticData` | `SMW\DataModel\SemanticData` |
  | `SMW\Subobject` | `SMW\DataModel\Subobject` |
  | `SMWElasticStore` | `SMW\Elastic\ElasticStore` |
  | `SMWSearch` | `SMW\MediaWiki\Search\ExtendedSearchEngine` |
  | `SMWOutputs` | `SMW\MediaWiki\Outputs` |
  | `SMWPageSchemas` | `SMW\MediaWiki\PageSchemas` |
  | `SMW\ContentParser` | `SMW\Parser\ContentParser` |
  | `SMW\SQLStore\Lookup\ListLookup` | `SMW\Lookup\ListLookup` |
  | `SMW\SQLStore\Lookup\CachedListLookup` | `SMW\Lookup\CachedListLookup` |

### Bug fixes

* Fixed incorrect timezone offset for negative half-hour timezones (e.g., Newfoundland `-3:30`): the 30-minute component was always added positively, producing `-2.5` instead of `-3.5` ([#6478](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/6478))
* Fixed CSV export producing malformed output when values contain the delimiter character ([#6343](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/6343))
* Fixed `#ask` sum format failing when encountering non-numeric values instead of treating them as zero ([#6253](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/6253))
* Fixed sortkey being silently dropped in certain query result contexts ([#6250](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/6250))
* Fixed template-rendered values with HTML tags breaking when used with named parameters ([#6235](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/6235))
* Fixed `limit`, `offset`, and `default` parameters being ignored in `@deferred` queries ([#6233](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/6233))
* Fixed malformed Page-type values producing unsanitized error messages ([#6234](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/6234))
* Fixed long property values being incorrectly truncated in the SQL store ([#6225](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/6225))
* Fixed content namespaces configuration failing when array keys are numeric ([#6293](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/6293))
* Fixed JavaScript configuration variables missing after deferred query execution ([#6266](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/6266))
* Fixed unwanted bullet-point styling in FacetedSearch result list ([#6287](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/6287))
* Fixed empty `<section>` tags producing broken output ([#6521](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/6521))
* Fixed `CannotCreateActorException` when importing pages on wikis with temporary accounts enabled ([#6331](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/6331))
* Fixed deprecation warnings on PostgreSQL ([#6202](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/6202))
* Fixed dynamic property deprecation warnings on PHP 8.2+ ([#6362](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/6362))
* Fixed PHP notices in `db-primary-keys.php` maintenance script ([#6466](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/6466))
* Fixed float-to-int precision loss in maintenance script progress output ([#6229](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/6229))
* Fixed null argument error in entity lookup task handler ([#6228](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/6228))
* Improved wording of the post-edit reload notice ([#6301](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/6301))
* Fixed maintenance log entries showing "performed unknown action" instead of a proper message, and improved log comment formatting from raw JSON to human-readable text ([#6146](https://github.com/SemanticMediaWiki/SemanticMediaWiki/issues/6146), [#6554](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/6554))

### Enhancements

* Changed `smw_hash` storage from hex-encoded to raw binary, reducing the hash index size and improving query performance on large wikis. Column type changes from `VARBINARY(40)` to `BINARY(20)` on MySQL/MariaDB and SQLite, and from `TEXT` to `BYTEA` on PostgreSQL. Existing hashes are converted automatically during `update.php`. ([#6587](https://github.com/SemanticMediaWiki/SemanticMediaWiki/issues/6587))
* Improved pagination performance on Special:Properties and Special:UnusedProperties by switching from OFFSET-based to cursor-based pagination. Browsing deep pages is now significantly faster on wikis with many properties. ([#6559](https://github.com/SemanticMediaWiki/SemanticMediaWiki/issues/6559))
  * Navigation links now use `after=` and `before=` URL parameters instead of `offset=`. Existing `offset=` bookmarks continue to work.
  * The numbered result list has been replaced with a bullet list, and the "starting with #N" indicator has been removed, as cursor-based pagination does not track absolute position.
* The SQLStore query engine now uses a derived-table SQL shape by default, pushing `DISTINCT` and the inner `LIMIT` / `ORDER BY` into a subquery to avoid an optimizer pathology around `DISTINCT` + `ORDER BY` on MariaDB that could degrade `#ask` query performance by several orders of magnitude. The `smw_object_ids` filter conditions remain on the outer query so the inner subquery sees only the property table it actually needs. ([#6559](https://github.com/SemanticMediaWiki/SemanticMediaWiki/issues/6559))
  * Set `$smwgQUseLegacyQuery = true` in `LocalSettings.php` to revert to the previous `SELECT DISTINCT` shape if you encounter a regression. The flag is provided as an emergency rollback; the rewrite is the supported path.
  * The disjunction temp-table insert no longer emits a redundant `DISTINCT` keyword — the enclosing `INSERT IGNORE` already deduplicates on the temp table's primary key. No configuration change needed.

### Internal improvements

* Native PHP type coverage significantly expanded across the entire codebase, including return types, parameter types, property types, and constructor promotion with `readonly`
* PHPUnit test suite reorganized into `Unit/` and `Integration/` directories
* Numerous static analysis (phan) errors fixed
* CI updated: added MediaWiki 1.45 to the test matrix, added cancellation of in-progress runs on new pushes, removed Travis CI leftovers

## Upgrading

**Run `update.php` after upgrading.** This release changes the `smw_hash` column type from `VARBINARY(40)` to `BINARY(20)`. The update script converts existing hash values automatically. For wikis with more than 200,000 entities, run `populateHashField.php --force-update` before `update.php`.

**If you use fulltext search** (`smwgEnabledFulltextSearch`): run `rebuildFulltextSearchTable.php` after upgrading to rebuild the index with the new ICU-based transliteration.

**Get the new version via Composer:**

* Step 1: if you are upgrading from SMW older than 6.0.0, ensure the SMW version in `composer.local.json` is `^7.0.0`
* Step 2: run composer in your MediaWiki directory: `composer update --no-dev --optimize-autoloader`

**Get the new version via Git:**

This is only for those who have installed SMW via Git.

* Step 1: do a `git pull` in the SemanticMediaWiki directory
* Step 2: run `composer update --no-dev --optimize-autoloader`

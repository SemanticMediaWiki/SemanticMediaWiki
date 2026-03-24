# Semantic MediaWiki 7.0.0

Released on TBD.

Like SMW 6.0.x, this version is compatible with MediaWiki 1.43 up to 1.45 and PHP 8.1 up to 8.4.
For more detailed information, see the [compatibility matrix](../COMPATIBILITY.md#compatibility).

## Changes

### Bug fixes

* Fixed incorrect timezone offset calculation for negative half-hour timezones such as Newfoundland (`-3:30`). The 30-minute component was always added positively, producing `-2.5` instead of the correct `-3.5`.

### Deprecations

* `enableSemantics()` is deprecated and now a no-op. `wfLoadExtension( 'SemanticMediaWiki' )` alone is sufficient to install SMW, aligning with standard MediaWiki extension conventions. The RDF namespace URI is now auto-derived from `Special:URIResolver` when not explicitly set. Users who set a custom `$smwgNamespace` in `LocalSettings.php` are unaffected.

  If you used configuration preloading via `enableSemantics`:

  ```php
  // Before (deprecated)
  enableSemantics( 'example.org' )->loadDefaultConfigFrom( 'media.php' );
  ```

  Replace with a direct `require`:

  ```php
  // After
  wfLoadExtension( 'SemanticMediaWiki' );
  require "$IP/extensions/SemanticMediaWiki/data/config/media.php";
  ```

* Replaced the vendored `Onoi\Tesa` text sanitizer library with PHP `intl` built-ins for fulltext search text processing. Users with `smwgEnabledFulltextSearch` enabled must run `rebuildFulltextSearchTable.php` after upgrading. Transliteration now uses ICU instead of a static mapping table, which produces minor differences for some characters (e.g., German ü→u instead of ü→ue). This does not affect search match quality.
* Removed unused internal classes: `HtmlVTabs`, `SchemaParameterTypeMismatchException`, `CleanUpTables`, and `FlatSemanticDataSerializer`.
* Removed long-deprecated code originally scheduled for removal before SMW 1.7:
  - `SMW_HEADER_TOOLTIP`, `SMW_HEADER_SORTTABLE`, `SMW_HEADER_STYLE` constants and the numeric-id branch in `Outputs::requireHeadItem()`
  - `TimeValue::getXMLSchemaDate()` (use `getISO8601Date()`)
  - `ValueDescription::getDataValue()` (use `getDataItem()`)
  - `ResultPrinter::getParameters()` (use `getParamDefinitions()`)
  - `ParserData::setData()` (use `setSemanticData()`)
  - `ParserData::getData()` (use `getSemanticData()`)
  - `Subobject::setSemanticData()` (use `setEmptyContainerForId()`)
  - `PropertyRegistry::findPropertyLabel()` (use `findPropertyLabelById()`)
  - `PropertyRegistry::getPredefinedPropertyTypeId()` (use `getPropertyValueTypeById()`)
  - `PropertyRegistry::findPropertyId()` (use `findPropertyIdByLabel()`)
  - `smwfNumberFormat()` (use `IntlNumberFormatter`)
  - `ParserFunctionFactory::getSubobjectParser()` (use `newSubobjectParserFunction()`)
  - `ParserFunctionFactory::getRecurringEventsParser()` (use `newRecurringEventsParserFunction()`)
  - `smwInitProperties` hook (use `SMW::Property::initProperties`)
  - `SMWSQLStore3::deleteSubjectBefore` / `SMWSQLStore3::deleteSubjectAfter` hooks (use `SMW::SQLStore::BeforeDeleteSubjectComplete` / `SMW::SQLStore::AfterDeleteSubjectComplete`)
  - `ParserParameterProcessor::getFirst()` (use `getFirstParameter()`)
  - `DataValue::prepareValue()` (use `DescriptionBuilder`)
  - `HashBuilder::createHashIdFromSegments()` (use `createFromSegments()`)
  - `DataValueFactory::newDataItemValue()` (use `newDataValueByItem()`)
  - `DataValueFactory::newPropertyObjectValue()` (use `newDataValueByProperty()`)
  - `DataValueFactory::newTypeIdValue()` (use `newDataValueByType()`)
  - `DataValueFactory::newPropertyValue()` (use `newDataValueByText()`)
* Moved permission rights and group assignments to declarative `AvailableRights` and `GroupPermissions` keys in `extension.json`. The `SMW::GroupPermissions::BeforeInitializationComplete` hook has been removed. Extensions that modified SMW permissions via this hook should use MediaWiki's standard `$wgGroupPermissions` override in `LocalSettings.php` instead.
* Removed the `$smwgSparqlRepositoryConnectorForcedHttpVersion` setting. HTTP version negotiation is now handled by MediaWiki's HTTP layer. The `mediawiki/http-request` (`Onoi\HttpRequest`) dependency has been dropped — SPARQL store connectors and `RemoteRequest` now use MediaWiki core's `HttpRequestFactory`.
* Removed the deprecated root `DefaultSettings.php` shim (deprecated since 4.0.0). Code that loaded settings directly via `require .../DefaultSettings.php` should use `SemanticMediaWiki::getDefaultSettings()` instead.

- Removed legacy job name aliases. All job types must now be referenced by their `smw.*` names. The following aliases no longer work:

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

- The following class aliases are deprecated. They will be removed in a future update. Update any code referencing these to use the new namespaced class names.

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

## Upgrading

No need to run "update.php" or any other migration scripts.

**Get the new version via Composer:**

* Step 1: if you are upgrading from SMW older than 6.0.0, ensure the SMW version in `composer.local.json` is `^7.0.0`
* Step 2: run composer in your MediaWiki directory: `composer update --no-dev --optimize-autoloader`

**Get the new version via Git:**

This is only for those who have installed SMW via Git.

* Step 1: do a `git pull` in the SemanticMediaWiki directory
* Step 2: run `composer update --no-dev --optimize-autoloader` in the MediaWiki directory

This document contains details about event handlers (also known as [Hooks][hooks]) provided by Semantic MediaWiki to enable users to extent and integrate custom specific solutions.

Implementing a hook should be made in consideration of the expected performance impact for the front-end (additional DB read/write transactions etc.) and/or the back-end (prolonged job backlog etc.) process.

### SMW::Factbox::BeforeContentGeneration
`SMW::Factbox::BeforeContentGeneration` enables to replace or amend text elements shown in the Factbox. Information can be added or redacted but only existing data for an entity (page) is provided which means this hook can not be used to alter the SemanticData container itself.

If `$smwgFactboxUseCache` is set `TRUE`, content is retrieved from cache without executing the hook and only after a new revision is available, the Factbox is being re-build and re-cached. If you want to allow that custom code to being executed on each request it is suggested to set the `$smwgFactboxUseCache` to `FALSE`.

```php
$GLOBALS['wgHooks']['SMW::Factbox::BeforeContentGeneration'][] = function ( &$text, SemanticData $semanticData ) {

	// Access to the Page language can be achieved by using
	$language = $semanticData->getSubject()->getTitle()->getPageLanguage()

	// Add formatted information as string
	$text = ...;

	// If returned false, only custom information will be displayed
	return false;

	// If returned true, amended information will appear before the standard Factbox
	return true;
};
```
Available since SMW 1.9 where the use of `smwShowFactbox` was deprecated with 1.9.

### SMW::Dispatcher::updateJobs
`SMW::Dispatcher::updateJobs` enables to add additional update jobs for a property and its related subjects.

```php
$GLOBALS['wgHooks']['SMW::Dispatcher::updateJobs'][] = function ( DIProperty $property, &$jobs ) {

	$jobs[] = new UpdateJob( ... );

	return true;
};
```
Available since SMW 1.9 and before 1.9 it was called smwUpdatePropertySubjects with a different interface.

### SMW::DataType::initTypes
Adds support for additional DataTypes.

```php
$GLOBALS['wgHooks']['SMW::DataType::initTypes'][] = function () {

	DataTypeRegistry::getInstance()->registerDataType( '_foo', '\SMW\FooValue', \SMW\DataItem::TYPE_GEO );

	return true;
};
```
Available since SMW 1.9 and before 1.9 it was called smwInitDatatypes.

## Store

### SMW::Store::BeforeQueryResultLookupCompleted

Enables to return a `QueryResult` object before the standard selection process is executed and to suppress the standard selection process completely return `FALSE`.

```php
$GLOBALS['wgHooks']['SMW::Store::BeforeQueryResultLookupCompleted'][] = function ( Store $store, Query $query, QueryResult &$result ) {

	return true or false;
};
```
Available since SMW 2.1.

### SMW::Store::AfterQueryResultLookupCompleted

```php
$GLOBALS['wgHooks']['SMW::Store::AfterQueryResultLookupCompleted'][] = function ( Store $store, QueryResult &$result ) {

	// A return value has no explicit meaning for the following processing
};
```
Available since SMW 2.1.

## SQLStore

### SMW::SQLStore::updatePropertyTableDefinitions
`SMW::SQLStore::updatePropertyTableDefinitions` is called during initialization of available property table definitions.

```php
$GLOBALS['wgHooks']['SMW::SQLStore::updatePropertyTableDefinitions'][] = function ( TableDefinition &$tableDefinitions ) {

	// Amend information
	$tableDefinitions[] = new TableDefinition( ... );

	return true;
};
```
Available since SMW 1.9.

## Other available hooks

Subsequent hooks should be renamed to follow a common naming practice that help distinguish them from other hook providers. In any case this list needs details and examples.

* `\SMW\DIProperty`, smwInitProperties (SMW::DataItem::initProperties)
* `\SMW\DataValueFactory`, smwInitDatatypes (SMW::DataValue::initDataTypes)
* `SMWExportController`, smwAddToRDFExport
* `SMWParamFormat`, SMWResultFormat
* `\SMW\Store`, SMWStore::updateDataBefore (SMW::Store::updateDataBefore)
* `\SMW\Store`, SMWStore::updateDataAfter (SMW::Store::updateDataAfter)
* `\SMW\Store`, smwInitializeTables (SMW::Store::initTables)
* `SMWSQLStore3SetupHandlers`, SMWCustomSQLStoreFieldType
* `SMWSQLStore3SetupHandlers`, smwRefreshDataJobs (SMW::SQLStore::updateJobs)
* `SMWSQLStore3Writers`, SMWSQLStore3::deleteSubjectBefore (SMW::SQLStore::deleteSubjectBefore)
* `SMWSQLStore3Writers`, SMWSQLStore3::deleteSubjectAfter (SMW::SQLStore::deleteSubjectAfter)
* `SMWSQLStore3Writers`, SMWSQLStore3::updateDataBefore (SMW::SQLStore::updateDataBefore)
* `SMWSQLStore3Writers`, SMWSQLStore3::updateDataAfter (SMW::SQLStore::updateDataAfter)
* `SMWSetupScript`, smwDropTables (SMW::Setup::dropTables)
* `SMW_refreshData`, smwDropTables (SMW::Setup::dropTables)

[hooks]: https://www.mediawiki.org/wiki/Hooks "Manual:Hooks"

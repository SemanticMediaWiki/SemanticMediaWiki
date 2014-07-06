This document contains details about event handlers (also known as [Hooks][hooks]) provided by Semantic MediaWiki to enable users to extent and integrate custom specific solutions. Implementing a hook should be made in consideration of the expected performance impact for a front-end (additional DB read/write etc.) and/or back-end (prolonged job backlog etc.) process.

### SMW::Factbox::showContent
<code>SMW::Factbox::showContent</code>(since SMW 1.9 resides in <code>\SMW\Factbox</code>) enables to replace or amend text elements shown in the Factbox. Information can be added or redacted but only existing data for an entity (page) is provided which means this hook can not be used to alter the SemanticData container itself.

In cases where the Factbox content is cached (<code>$smwgFactboxUseCache</code> is set true and only after a revision change, is being re-build and successively re-cached) it is suggested to disable the <code>$smwgFactboxUseCache</code> in order for custom code to be executed on each request.

```php
$GLOBALS['wgHooks']['SMW::Factbox::showContent'][] = function ( &$text, \SMW\SemanticData $semanticData ) {

	// Access to the Page language can be achieved by using
	$language = $semanticData->getSubject()->getTitle()->getPageLanguage()

	// Amend information, formatted as string
	$text = ...;

	// If returned false, only custom information will be displayed
	return false;

	// If returned true, amended information will appear before the standard Factbox
	return true;
};
```
The use of <code>smwShowFactbox</code> was deprecated with SMW 1.9.

### SMW::Dispatcher::updateJobs (SMW 1.9)
<code>SMW::Dispatcher::updateJobs</code> (<code>\SMW\UpdateDispatcherJob</code>) enables to add additional update jobs for a property and its related subjects.

```php
$GLOBALS['wgHooks']['SMW::Dispatcher::updateJobs'][] = function ( \SMW\DIProperty $property, &$jobs ) {

	$jobs[] = new UpdateJob( ... );

	return true;
};
```
Before 1.9 it was called smwUpdatePropertySubjects with a different interface.

### SMW::DataType::initTypes (SMW 1.9)
Adds support for additional DataTypes.

```php
$GLOBALS['wgHooks']['SMW::DataType::initTypes'][] = function () {

	DataTypeRegistry::getInstance()->registerDataType( '_foo', '\SMW\FooValue', \SMW\DataItem::TYPE_GEO );

	return true;
};
```
Before 1.9 it was called smwInitDatatypes.

## SQLStore
### SMW::SQLStore::updatePropertyTableDefinitions (SMW 1.9)
<code>SMW::SQLStore::updatePropertyTableDefinitions</code>(<code>\SMW\SQLStore\PropertyTableDefinitionBuilder</code>) is called during initialization of available property table definitions

```php
$GLOBALS['wgHooks']['SMW::SQLStore::updatePropertyTableDefinitions'][] = function ( \SMW\SQLStore\TableDefinition &$tableDefinitions ) {

	// Amend information
	$tableDefinitions[] = new TableDefinition( ... );

	return true;
};
```

## List of available hooks
Subsequent hooks should be renamed to follow a common naming practice that help distinguish them from other hook providers. In any case this list needs details and examples.

* <code>\SMW\DIProperty</code>, smwInitProperties (SMW::DataItem::initProperties)
* <code>\SMW\DataValueFactory</code>, smwInitDatatypes (SMW::DataValue::initDataTypes)
* <code>SMWExportController</code>, smwAddToRDFExport
* <code>SMWParamFormat</code>, SMWResultFormat
* <code>\SMW\Store</code>, SMWStore::updateDataBefore (SMW::Store::updateDataBefore)
* <code>\SMW\Store</code>, SMWStore::updateDataAfter (SMW::Store::updateDataAfter)
* <code>\SMW\Store</code>, smwInitializeTables (SMW::Store::initTables)
* <code>SMWSQLStore3SetupHandlers</code>, SMWCustomSQLStoreFieldType
* <code>SMWSQLStore3SetupHandlers</code>, smwRefreshDataJobs (SMW::SQLStore::updateJobs)
* <code>SMWSQLStore3Writers</code>, SMWSQLStore3::deleteSubjectBefore (SMW::SQLStore::deleteSubjectBefore)
* <code>SMWSQLStore3Writers</code>, SMWSQLStore3::deleteSubjectAfter (SMW::SQLStore::deleteSubjectAfter)
* <code>SMWSQLStore3Writers</code>, SMWSQLStore3::updateDataBefore (SMW::SQLStore::updateDataBefore)
* <code>SMWSQLStore3Writers</code>, SMWSQLStore3::updateDataAfter (SMW::SQLStore::updateDataAfter)
* <code>SMWSetupScript</code>, smwDropTables (SMW::Setup::dropTables)
* <code>SMW_refreshData</code>, smwDropTables (SMW::Setup::dropTables)

[hooks]: https://www.mediawiki.org/wiki/Hooks "Manual:Hooks"

This document contains details about event handlers (also known as [Hooks][hooks]) provided by Semantic MediaWiki to enable users to extent and integrate custom specific solutions.

Implementing a hook should be made in consideration of the expected performance impact for the front-end (additional DB read/write transactions etc.) and/or the back-end (prolonged job backlog etc.) process.

<table>
	<tr>
		<th>Name</th>
		<th width="70%">Description</th>
		<th>Since</th>
	</tr>
	<tr>
		<td>`SMW::Factbox::BeforeContentGeneration`</td>
		<td>to replace or amend text elements shown in a Factbox. See also `$smwgFactboxUseCache` settings.<sup>Use of `smwShowFactbox` was deprecated with 1.9</sup></td>
		<td>1.9</td>
	</tr>
	<tr>
		<td>`SMW::Job::updatePropertyJobs`</td>
		<td>to add additional update jobs for a property and related subjects.<sup>Use of `smwUpdatePropertySubjects` was deprecated with 1.9</sup></td>
		<td>1.9</td>
	</tr>
	<tr>
		<td>`SMW::DataType::initTypes`</td>
		<td>to add additional DataType support.<sup>Use of `smwInitDatatypes` was deprecated with 1.9</sup></td>
		<td>1.9</td>
	</tr>
	<tr>
		<td>`SMW::Property::initProperties`</td>
		<td>to add additional predefined properties.<sup>Use of `smwInitProperties` was deprecated with 2.1</sup></td>
		<td>2.1</td>
	</tr>
	<tr>
		<td>`SMW::Store::BeforeQueryResultLookupComplete`</td>
		<td>to return a `QueryResult` object before the standard selection process is executed with the power to suppress the standard selection process completely by returning `FALSE`.</td>
		<td>2.1</sup></td>
	</tr>
	<tr>
		<td>`SMW::Store::AfterQueryResultLookupComplete`</td>
		<td>to manipulate a `QueryResult` after the selection process.</td>
		<td>2.1</sup></td>
	</tr>
	<tr>
		<td>`SMW::SQLStore::updatePropertyTableDefinitions`</td>
		<td>to add additional table definitions during initialization.</td>
		<td>1.9</sup></td>
	</tr>
	<tr>
		<td>`SMW::SQLStore::BeforeDeleteSubjectComplete`</td>
		<td>called before deletion of a subject is completed</td>
		<td>2.1</sup></td>
	</tr>
	<tr>
		<td>`SMW::SQLStore::AfterDeleteSubjectComplete`</td>
		<td>called after deletion of a subject is completed.</td>
		<td>2.1</sup></td>
	</tr>
	<tr>
		<td>`SMW::SQLStore::BeforeChangeTitleComplete`</td>
		<td>called before change to a subject is completed.</td>
		<td>2.1</sup></td>
	</tr>
</table>

For implementation details, see examples provided by `SemanticMediaWikiProvidedHookInterfaceIntegrationTest`.

## Other available hooks

Subsequent hooks should be renamed to follow a common naming practice that help distinguish them from other hook providers. In any case this list needs details and examples.

* `\SMW\DataValueFactory`, smwInitDatatypes (SMW::DataValue::initDataTypes)
* `SMWExportController`, smwAddToRDFExport (SMW::Exporter::AfterRdfExportComplete)
* `SMWParamFormat`, SMWResultFormat
* `\SMW\Store`, SMWStore::updateDataBefore (SMW::Store::BeforeDataUpdateComplete)
* `\SMW\Store`, SMWStore::updateDataAfter (SMW::Store::AfterDataUpdateComplete)
* `\SMW\Store`, smwInitializeTables (SMW::Store::initTables)
* `SMWSQLStore3SetupHandlers`, SMWCustomSQLStoreFieldType
* `SMWSQLStore3SetupHandlers`, smwRefreshDataJobs (SMW::SQLStore::AfterRefreshDataJob)
* `SMWSQLStore3Writers`, SMWSQLStore3::updateDataBefore (SMW::SQLStore::BeforeDataUpdateComplete)
* `SMWSQLStore3Writers`, SMWSQLStore3::updateDataAfter (SMW::SQLStore::AfterDataUpdateComplete)
* `SMWSetupScript`, smwDropTables (SMW::Store::dropTables)
* `SMW_refreshData`, smwDropTables (SMW::Store::dropTables)

[hooks]: https://www.mediawiki.org/wiki/Hooks "Manual:Hooks"

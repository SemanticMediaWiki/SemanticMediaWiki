This document contains details about event handlers (also known as [Hooks][hooks]) provided by Semantic MediaWiki to enable users to extent and integrate custom specific solutions.

Implementing a hook should be made in consideration of the expected performance impact for the front-end (additional DB read/write transactions etc.) and/or the back-end (prolonged job backlog etc.) process.

## List of available hooks

### 1.9

- `SMW::Factbox::BeforeContentGeneration` to replace or amend text elements shown in a Factbox. See also `$smwgFactboxUseCache` settings.<sup>Use of `smwShowFactbox` was deprecated with 1.9</sup>
- `SMW::Job::updatePropertyJobs` to add additional update jobs for a property and related subjects.<sup>Use of `smwUpdatePropertySubjects` was deprecated with 1.9</sup>
- `SMW::DataType::initTypes` to add additional DataType support.<sup>Use of `smwInitDatatypes` was deprecated with 1.9</sup>
- `SMW::SQLStore::updatePropertyTableDefinitions` to add additional table definitions during initialization.

### 2.1

- `SMW::Store::BeforeQueryResultLookupComplete` to return a `QueryResult` object before the standard selection process is
  started and allows to suppress the standard selection process completely by returning `false`.
- `SMW::Store::AfterQueryResultLookupComplete`  to manipulate a `QueryResult` after the selection process.
- `SMW::Property::initProperties` to add additional predefined properties.<sup>Use of `smwInitProperties` was deprecated with 2.1</sup>
- `SMW::SQLStore::BeforeDeleteSubjectComplete` called before deletion of a subject is completed.
- `SMW::SQLStore::AfterDeleteSubjectComplete` called after deletion of a subject is completed.
- `SMW::SQLStore::BeforeChangeTitleComplete` called before change to a subject is completed.

### 2.2

- `SMW::Parser::BeforeMagicWordsFinder` allows to extend the magic words list that the `InTextAnnotationParser` should
  search for the wikitext.

### 2.3

- `SMW::SQLStore::BeforeDataRebuildJobInsert` to add update jobs while running the rebuild process.<sup>Use of `smwRefreshDataJobs` was deprecated with 2.3</sup>
- `SMW::SQLStore::AddCustomFixedPropertyTables` to add fixed property table definitions
- `SMW::Browse::AfterIncomingPropertiesLookupComplete` to extend the incoming properties display for `Special:Browse`
- `SMW::Browse::BeforeIncomingPropertyValuesFurtherLinkCreate` to replace the standard `SearchByProperty` with a custom link to an extended list of results (return `false` to replace the link)
- `SMW::SQLStore::AfterDataUpdateComplete` to add processing after the update has been completed and provides `CompositePropertyTableDiffIterator` to identify entities
   that have been added/removed during the update. <sup>Use of `SMWSQLStore3::updateDataAfter` was deprecated with 2.3</sup>

### 2.4

- `SMW::FileUpload::BeforeUpdate` to add extra annotations before the store update is triggered

## 2.5

### SMW::Job::AfterUpdateDispatcherJobComplete

Hook allows to add extra jobs after `UpdateDispatcherJob` has been processed.

<pre>
\Hooks::register( 'SMW::Job::AfterUpdateDispatcherJobComplete', function( $job  ) {

	// Find related dependencies
	$title = $job->getTitle();

	return true;
} );
</pre>

### SMW::SQLStore::Installer::AfterCreateTablesComplete

Hook allows to add extra tables after the creation process as been finalized.

<pre>
\Hooks::register( 'SMW::SQLStore::Installer::AfterCreateTablesComplete', function( $tableBuilder, $messageReporter ) {

	// Output details on the activity
	$messageReporter->reportMessage( '...' );

	// See documentation in the available TableBuilder interface
	$tableBuilder->create( ... );

	return true;
} );
</pre>

### SMW::SQLStore::Installer::AfterDropTablesComplete

Hook allows to remove extra tables after the drop process as been finalized.

<pre>
\Hooks::register( 'SMW::SQLStore::Installer::AfterDropTablesComplete', function( $tableBuilder, $messageReporter ) {

	// Output details on the activity
	$messageReporter->reportMessage( '...' );

	// See documentation in the available TableBuilder interface
	$tableBuilder->drop( ... );

	return true;
} );
</pre>


## Other available hooks

Subsequent hooks should be renamed to follow a common naming practice that help distinguish them from other hook providers. In any case this list needs details and examples.

* `SMWExportController`, smwAddToRDFExport (SMW::Exporter::AfterRdfExportComplete)
* `SMWParamFormat`, SMWResultFormat
* `\SMW\Store`, SMWStore::updateDataBefore (SMW::Store::BeforeDataUpdateComplete)
* `\SMW\Store`, SMWStore::updateDataAfter (SMW::Store::AfterDataUpdateComplete)
* `SMWSQLStore3Writers`, SMWSQLStore3::updateDataBefore (SMW::SQLStore::BeforeDataUpdateComplete)

[hooks]: https://www.mediawiki.org/wiki/Hooks "Manual:Hooks"

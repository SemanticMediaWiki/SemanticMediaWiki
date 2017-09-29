This document contains details about event handlers (also known as [Hooks][hooks]) provided by Semantic MediaWiki to enable users to extent and integrate custom specific solutions.

Implementing a hook should be made in consideration of the expected performance impact for the front-end (additional DB read/write transactions etc.) and/or the back-end (prolonged job backlog etc.) process.

# List of available hooks

## 1.9

- `SMW::Factbox::BeforeContentGeneration` to replace or amend text elements shown in a Factbox. See also `$smwgFactboxUseCache` settings.<sup>Use of `smwShowFactbox` was deprecated with 1.9</sup>
- `SMW::Job::updatePropertyJobs` to add additional update jobs for a property and related subjects.<sup>Use of `smwUpdatePropertySubjects` was deprecated with 1.9</sup>
- `SMW::DataType::initTypes` to add additional DataType support.<sup>Use of `smwInitDatatypes` was deprecated with 1.9</sup>
- `SMW::SQLStore::updatePropertyTableDefinitions` to add additional table definitions during initialization.

## 2.1

### SMW::Store::BeforeQueryResultLookupComplete

* Version: 2.1
* Description: Hook to return a `QueryResult` object before the standard selection process is started and allows to suppress the standard selection process completely by returning `false`.
* Reference class: `SMW_SQLStore3.php`

<pre>
\Hooks::register( 'SMW::Store::AfterQueryResultLookupComplete', function( $store, $query, &$queryResult, $queryEngine ) {

	// Allow default processing
	return true;

	// Stop further processing
	return false;
} );
</pre>

### SMW::Store::AfterQueryResultLookupComplete

* Version: 2.1
* Description: Hook to manipulate a `QueryResult` after the selection process.
* Reference class: `SMW_SQLStore3.php`

<pre>
\Hooks::register( 'SMW::Store::AfterQueryResultLookupComplete', function( $store, &$queryResult ) {

	return true;
} );
</pre>

### SMW::Property::initProperties

* Version: 2.1
* Description: Hook to add additional predefined properties (`smwInitProperties` was deprecated with 2.1)
* Reference class: `SMW\PropertyRegistry`

<pre>
\Hooks::register( 'SMW::Property::initProperties', function( $propertyRegistry ) {

	return true;
} );
</pre>

### SMW::SQLStore::BeforeDeleteSubjectComplete

* Version: 2.1
* Description: Hook is called before the deletion of a subject is completed
* Reference class: `SMW_SQLStore3_Writers.php`

<pre>
\Hooks::register( 'SMW::SQLStore::BeforeDeleteSubjectComplete', function( $store, $title ) {

	return true;
} );
</pre>

### SMW::SQLStore::AfterDeleteSubjectComplete

* Version: 2.1
* Description: Hook is called after the deletion of a subject is completed
* Reference class: `SMW_SQLStore3_Writers.php`

<pre>
\Hooks::register( 'SMW::SQLStore::AfterDeleteSubjectComplete', function( $store, $title ) {

	return true;
} );
</pre>

### SMW::SQLStore::BeforeChangeTitleComplete

* Version: 2.1
* Description: Hook is called before change to a subject is completed
* Reference class: `SMW_SQLStore3_Writers.php`

<pre>
\Hooks::register( 'SMW::SQLStore::BeforeChangeTitleComplete', function( $store, $oldTitle, $newTitle, $pageId, $redirectId ) {

	return true;
} );
</pre>

## 2.2

### SMW::Parser::BeforeMagicWordsFinder

* Version: 2.2
* Description: Hook allowing to extend the magic words list that the `InTextAnnotationParser` should search for the wikitext.
* Reference class: `\SMW\InTextAnnotationParser`

<pre>
\Hooks::register( 'SMW::Parser::BeforeMagicWordsFinder', function( array &$magicWords ) {

	return true;
} );
</pre>

## 2.3

### SMW::SQLStore::BeforeDataRebuildJobInserts

* Version: 2.3
* Description: Hook to add update jobs while running the rebuild process.<sup>Use of `smwRefreshDataJobs` was deprecated with 2.3</sup>
* Reference class: `\SMW\SQLStore\EntityRebuildDispatcher`

<pre>
\Hooks::register( 'SMW::SQLStore::BeforeDataRebuildJobInsert', function( $store, array &$jobs ) {

	return true;
} );
</pre>

### SMW::SQLStore::AddCustomFixedPropertyTables

* Version: 2.3
* Description: Hook to add fixed property table definitions
* Reference class: `\SMW\MediaWiki\Specials\Browse\ContentsBuilder`

<pre>
\Hooks::register( 'SMW::SQLStore::AddCustomFixedPropertyTables', function( array &$customFixedProperties, &$propertyTablePrefix ) {
	$customFixedProperties['Foo'] = '_Bar';

	return true;
} );
</pre>

### SMW::Browse::AfterIncomingPropertiesLookupComplete

* Version: 2.3
* Description: Hook to extend the incoming properties display for `Special:Browse`
* Reference class: `\SMW\MediaWiki\Specials\Browse\ContentsBuilder`

<pre>
\Hooks::register( 'SMW::Browse::AfterIncomingPropertiesLookupComplete', function( $store, $semanticData, $requestOptions ) {

	return true;
} );
</pre>

### SMW::Browse::BeforeIncomingPropertyValuesFurtherLinkCreate

* Version: 2.3
* Description: Hook to replace the standard `SearchByProperty` with a custom link to an extended list of results (return `false` to replace the link)
* Reference class: `\SMW\MediaWiki\Specials\Browse\ContentsBuilder`

<pre>
\Hooks::register( 'SMW::Browse::BeforeIncomingPropertyValuesFurtherLinkCreate', function( $property, $subject, &$propertyValue ) {

	return true;
} );
</pre>

### SMW::SQLStore::AfterDataUpdateComplete

* Version: 2.3
* Description: Hook to add processing after the update has been completed and provides `CompositePropertyTableDiffIterator` to identify entities that have been added/removed during the update. (`SMWSQLStore3::updateDataAfter` was deprecated with 2.3)
* Reference class: `\SMW\MediaWiki\Hooks\FileUpload`

<pre>
\Hooks::register( 'SMW::SQLStore::AfterDataUpdateComplete', function( $store, $semanticData, $propertyTableEntityDiffIterator ) {

	return true;
} );
</pre>

## 2.4

### SMW::FileUpload::BeforeUpdate

* Version: 2.4
* Description: Hook to add extra annotations before the `Store` update is triggered
* Reference class: `\SMW\MediaWiki\Hooks\FileUpload`

<pre>
\Hooks::register( 'SMW::FileUpload::BeforeUpdate', function( $filePage, $semanticData  ) {

	return true;
} );
</pre>

## 2.5

### SMW::Job::AfterUpdateDispatcherJobComplete

* Version: 2.5
* Description: Hook allows to add extra jobs after `UpdateDispatcherJob` has been processed.
* Reference class: `\SMW\MediaWiki\Jobs\UpdateDispatcherJob`

<pre>
\Hooks::register( 'SMW::Job::AfterUpdateDispatcherJobComplete', function( $job ) {

	// Find related dependencies
	$title = $job->getTitle();

	return true;
} );
</pre>

### SMW::SQLStore::Installer::AfterCreateTablesComplete

* Version: 2.5
* Description: Hook allows to add extra tables after the creation process as been finalized.
* Reference class: `\SMW\SQLStore\Installer`

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

* Version: 2.5
* Description: Hook allows to remove extra tables after the drop process as been finalized.
* Reference class: `\SMW\SQLStore\Installer`

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

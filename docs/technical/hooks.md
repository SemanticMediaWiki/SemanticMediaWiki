This document contains details about event handlers (also known as [Hooks][hooks]) provided by Semantic MediaWiki to enable users to extent and integrate custom specific solutions.

## Available hooks

Implementing a hook should be made in consideration of the expected performance impact for the front-end (additional DB read/write transactions etc.) and/or the back-end (prolonged job backlog etc.) process.

### Setup and registry

- [`SMW::DataType::initTypes`][hook.datatype.inittypes] to add additional [DataType][datamodel.datatype] support
- [`SMW::Property::initProperties`][hook.property.initproperties] to add additional predefined properties
- [`SMW::Constraint::initConstraints`][hook.constraint.initconstraints] to add custom constraints
- [`SMW::GetPreferences`][hook.getpreferences] to add extra preferences that are ordered on the Semantic MediaWiki user preference tab
- [`SMW::Setup::AfterInitializationComplete`][hook.setup.afterinitializationcomplete] to modify global configuration after initialization of Semantic MediaWiki is completed
- `SMW::Settings::BeforeInitializationComplete` to modify the Semantic MediaWiki configuration before the initialization is completed
- [`SMW::Event::RegisterEventListeners`][hook.event.registereventlisteners] to register additional event listeners

### Store

- [`SMW::SQLStore::BeforeDeleteSubjectComplete`][hook.sqlstore.beforedeletesubjectcomplete] is called before the deletion of a subject is completed
- [`SMW::SQLStore::AfterDeleteSubjectComplete`][hook.sqlstore.afterdeletesubjectcomplete] is called after the deletion of a subject is completed
- [`SMW::SQLStore::BeforeChangeTitleComplete`][hook.sqlstore.beforechangetitlecomplete] is called before change to a subject is completed
- [`SMW::SQLStore::BeforeDataRebuildJobInserts`][hook.sqlstore.beforedatarebuildjobinserts] to add update jobs while running the rebuild process
- [`SMW::SQLStore::BeforeDataUpdateComplete`][hook.sqlstore.beforedataupdatecomplete] to extend the `SemanticData` object before the update is completed
- [`SMW::SQLStore::AfterDataUpdateComplete`][hook.sqlstore.afterdataupdatecomplete] to process information after an update has been completed
- [`SMW::Store::BeforeDataUpdateComplete`][hook.store.beforedataupdatecomplete] to extend the `SemanticData` object before the update is completed
- [`SMW::Store::AfterDataUpdateComplete`][hook.store.afterdataupdatecomplete] to process information after an update has been completed

#### Property tables

- [`SMW::SQLStore::AddCustomFixedPropertyTables`][hook.sqlstore.addcustomfixedpropertytables] to add fixed property table definitions
- `SMW::SQLStore::updatePropertyTableDefinitions` to add additional table definitions during initialization
- [`SMW::SQLStore::EntityReferenceCleanUpComplete`][hook.sqlstore.entityreferencecleanupcomplete]  to process information about an entity where the clean-up has been finalized

#### Installer

- [`SMW::SQLStore::Installer::BeforeCreateTablesComplete`][hook.sqlstore.installer.beforecreatetablescomplete] to add additional table indices
- [`SMW::SQLStore::Installer::AfterCreateTablesComplete`][hook.sqlstore.installer.aftercreatetablescomplete] to add extra tables after the creation process as been finalized
- [`SMW::SQLStore::Installer::AfterDropTablesComplete`][hook.sqlstore.installer.afterdroptablescomplete] to remove extra tables after the drop process as been finalized

#### Query

- [`SMW::Store::BeforeQueryResultLookupComplete`][hook.store.beforequeryresultlookupcomplete] to return a `QueryResult` object before the standard selection process is started and allows to suppress the standard selection process completely by returning `false`
- [`SMW::Store::AfterQueryResultLookupComplete`][hook.store.afterqueryresultlookupcomplete] to manipulate a `QueryResult` after the selection process

### Parser, annotations, and revision

- [`SMW::Parser::BeforeMagicWordsFinder`][hook.parser.beforemagicwordsfinder] to extend the magic words list that the `InTextAnnotationParser` should inspect on a given text section
- [`SMW::Parser::AfterLinksProcessingComplete`][hook.parser.afterlinksprocessingcomplete] to add additional annotation parsing after `InTextAnnotationParser` has finished the processing of standard annotation links (e.g. `[[...::...]]`)
- [`SMW::FileUpload::BeforeUpdate`][hook.fileupload.beforeupdate] to add extra annotations on a `FileUpload` event before the `Store` update is triggered
- [`SMW::RevisionGuard::ChangeRevision`][hook.revisionguard.changerevision] to forcibly change a revision used during content parsing
- [`SMW::RevisionGuard::ChangeRevisionID`][hook.revisionguard.changerevisionid] to forcibly change the revision ID as in case of the `Factbox` when building the content.
- [`SMW::RevisionGuard::IsApprovedRevision`][hook.revisionguard.isapprovedrevision] to define whether a revision is approved or needs to be suppressed.
- [`SMW::RevisionGuard::ChangeFile`][hook.revisionguard.changefile] to forcibly change the file version used

### Miscellaneous

- `SMW::Factbox::BeforeContentGeneration` to replace or amend text elements shown in a Factbox
- [`SMW::Browse::AfterIncomingPropertiesLookupComplete`][hook.browse.afterincomingpropertieslookupcomplete] to extend the incoming properties display for `Special:Browse`
- [`SMW::Browse::BeforeIncomingPropertyValuesFurtherLinkCreate`][hook.browse.beforeincomingpropertyvaluesfurtherlinkcreate] to replace the standard `SearchByProperty` with a custom link in `Special:Browse` to an extended list of results (return `false` to replace the link)
- [`SMW::Browse::AfterDataLookupComplete`][hook.browse.afterdatalookupcomplete] to extend the HTML with data displayed on `Special:Browse`
- [`SMW::Admin::TaskHandlerFactory`][hook.admin.taskhandlerfactory] to extend available `TaskHandler` used in the `Special:SemanticMediaWiki` dashboard
- [`SMW::ResultFormat::OverrideDefaultFormat`][hook.resultformat.overridedefaultformat] to override the default result format handling
- [`SMW::Job::AfterUpdateDispatcherJobComplete`][hook.job.afterupdatedispatcherjobcomplete] to add additional update jobs for a property and related subjects
- [`SMW::Exporter::Controller::AddExpData`][hook.exporter.controller.addexpdata] to add additional RDF data for a selected subject
- [`SMW::Maintenance::AfterUpdateEntityCollationComplete`][hook.maintenance.afterupdateentitycollationcomplete] runs after the `updateEntityCollection.php` script has finished processing the update of entity collation changes

## Deprecated hooks

- `smwInitDatatypes` (since 1.9)
- `smwInitProperties` (since 2.1)
- `smwShowFactbox` (since 2.1)
- `smwRefreshDataJobs` (since 2.3)
- `smwUpdatePropertySubjects` (since 1.9)
- `smwAddToRDFExport` (since 3.0)
- `SMWSQLStore3::updateDataBefore` (since 3.1)
- `SMWSQLStore3::updateDataAfter` (since 2.3)
- `SMWStore::updateDataBefore` (since 3.1)
- `SMWStore::updateDataAfter` (since 3.1)
- `SMWResultFormat` (since 3.1)

[hooks]: https://www.mediawiki.org/wiki/Hooks "Manual:Hooks"
[hook.store.beforequeryresultlookupcomplete]: https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/docs/technical/hooks/hook.store.beforequeryresultlookupcomplete.md
[hook.store.afterqueryresultlookupcomplete]: https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/docs/technical/hooks/hook.store.afterqueryresultlookupcomplete.md
[datamodel.datatype]:https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/docs/architecture/datamodel.datatype.md
[hook.property.initproperties]:https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/docs/technical/hooks/hook.property.initproperties.md
[hook.datatype.inittypes]:https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/docs/technical/hooks/hook.datatype.inittypes.md
[hook.sqlstore.beforedeletesubjectcomplete]:https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/docs/technical/hooks/hook.sqlstore.beforedeletesubjectcomplete.md
[hook.sqlstore.afterdeletesubjectcomplete]:https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/docs/technical/hooks/hook.sqlstore.afterdeletesubjectcomplete.md
[hook.sqlstore.beforechangetitlecomplete]:https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/docs/technical/hooks/hook.sqlstore.beforechangetitlecomplete.md
[hook.parser.beforemagicwordsfinder]:https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/docs/technical/hooks/hook.parser.beforemagicwordsfinder.md
[hook.parser.afterlinksprocessingcomplete]:https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/docs/technical/hooks/hook.parser.afterlinksprocessingcomplete.md
[hook.sqlstore.beforedatarebuildjobinserts]:https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/docs/technical/hooks/hook.sqlstore.beforedatarebuildjobinserts.md
[hook.sqlstore.addcustomfixedpropertytables]:https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/docs/technical/hooks/hook.sqlstore.addcustomfixedpropertytables.md
[hook.browse.afterincomingpropertieslookupcomplete]:https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/docs/technical/hooks/hook.browse.afterincomingpropertieslookupcomplete.md
[hook.browse.beforeincomingpropertyvaluesfurtherlinkcreate]:https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/docs/technical/hooks/hook.browse.beforeincomingpropertyvaluesfurtherlinkcreate.md
[hook.browse.afterdatalookupcomplete]:https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/docs/technical/hooks/hook.browse.afterdatalookupcomplete.md
[hook.sqlstore.afterdataupdatecomplete]:https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/docs/technical/hooks/hook.sqlstore.afterdataupdatecomplete.md
[hook.sqlstore.beforedataupdatecomplete]:https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/docs/technical/hooks/hook.sqlstore.beforedataupdatecomplete.md
[hook.store.beforedataupdatecomplete]:https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/docs/technical/hooks/hook.store.beforedataupdatecomplete.md
[hook.store.afterdataupdatecomplete]:https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/docs/technical/hooks/hook.store.afterdataupdatecomplete.md
[hook.fileupload.beforeupdate]:https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/docs/technical/hooks/hook.fileupload.beforeupdate.md
[hook.job.afterupdatedispatcherjobcomplete]:https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/docs/technical/hooks/hook.job.afterupdatedispatcherjobcomplete.md
[hook.sqlstore.installer.aftercreatetablescomplete]:https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/docs/technical/hooks/hook.sqlstore.installer.aftercreatetablescomplete.md
[hook.sqlstore.installer.afterdroptablescomplete]:https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/docs/technical/hooks/hook.sqlstore.installer.afterdroptablescomplete.md
[hook.sqlstore.installer.beforecreatetablescomplete]:https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/docs/technical/hooks/hook.sqlstore.installer.beforecreatetablescomplete.md
[hook.getpreferences]:https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/docs/technical/hooks/hook.getpreferences.md
[hook.setup.afterinitializationcomplete]:https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/docs/technical/hooks/hook.setup.afterinitializationcomplete.md
[hook.exporter.controller.addexpdata]:https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/docs/technical/hooks/hook.exporter.controller.addexpdata.md
[hook.sqlstore.entityreferencecleanupcomplete]:https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/docs/technical/hooks/hook.sqlstore.entityreferencecleanupcomplete.md
[hook.admin.taskhandlerfactory]:https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/docs/technical/hooks/hook.admin.taskhandlerfactory.md
[hook.revisionguard.changerevision]:https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/docs/technical/hooks/hook.revisionguard.changerevision.md
[hook.revisionguard.changerevisionid]:https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/docs/technical/hooks/hook.revisionguard.changerevisionid.md
[hook.revisionguard.isapprovedrevision]:https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/docs/technical/hooks/hook.revisionguard.isapprovedrevision.md
[hook.revisionguard.changefile]:https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/docs/technical/hooks/hook.revisionguard.changefile.md
[hook.event.registereventlisteners]:https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/docs/technical/hooks/hook.event.registereventlisteners.md
[hook.resultformat.overridedefaultformat]:https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/docs/technical/hooks/hook.resultformat.overridedefaultformat.md
[hook.constraint.initconstraints]:https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/docs/technical/hooks/hook.constraint.initconstraints.md
[hook.maintenance.afterupdateentitycollationcomplete]:https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/docs/technical/hooks/hook.maintenance.afterupdateentitycollationcomplete.md
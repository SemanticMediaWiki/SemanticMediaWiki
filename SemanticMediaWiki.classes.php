<?php

/**
 * Class registration file for Semantic MediaWiki
 *
 * @file
 *
 * @license GNU GPL v2+
 * @since   1.9
 *
 * @author mwjames
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */

/**
 * Class registration file for Semantic MediaWiki
 *
 * @ingroup SMW
 */
return array(

	'SMWHooks'                  => 'SemanticMediaWiki.hooks.php',

	'SMW\FormatFactory'         => 'includes/FormatFactory.php',
	'SMW\Highlighter'           => 'includes/Highlighter.php',
	'SMW\ParameterInput'        => 'includes/ParameterInput.php',
	'SMWFactbox'                => 'includes/Factbox.php',
	'SMWInfolink'               => 'includes/SMW_Infolink.php',
	'SMWOutputs'                => 'includes/SMW_Outputs.php',
	'SMW\ParserTextProcessor'   => 'includes/ParserTextProcessor.php',
	'SMWSemanticData'           => 'includes/SMW_SemanticData.php',
	'SMW\SemanticData'          => 'includes/SMW_SemanticData.php', // 1.9
	'SMWPageLister'             => 'includes/SMW_PageLister.php',

	'SMWDataValueFactory'       => 'includes/DataValueFactory.php',
	'SMW\DataValueFactory'      => 'includes/DataValueFactory.php',

	'SMWParseData'              => 'includes/SMW_ParseData.php',
	'SMW\IParserData'           => 'includes/ParserData.php',
	'SMW\ParserData'            => 'includes/ParserData.php',

	'SMW\PropertyChangeNotifier'    => 'includes/PropertyChangeNotifier.php',
	'SMW\BasePropertyAnnotator'     => 'includes/BasePropertyAnnotator.php',
	'SMW\RedirectPropertyAnnotator' => 'includes/RedirectPropertyAnnotator.php',
	'SMW\Subobject'                 => 'includes/Subobject.php',
	'SMW\RecurringEvents'           => 'includes/RecurringEvents.php',
	'SMW\Settings'                  => 'includes/Settings.php',
	'SMW\NamespaceExaminer'         => 'includes/NamespaceExaminer.php',
	'SMW\Profiler'                  => 'includes/Profiler.php',
	'SMW\IdGenerator'               => 'includes/IdGenerator.php',
	'SMW\HashIdGenerator'           => 'includes/HashIdGenerator.php',
	'SMW\Accessor'                  => 'includes/Accessor.php',
	'SMW\Arrayable'                 => 'includes/Arrayable.php',
	'SMW\ArrayAccessor'             => 'includes/ArrayAccessor.php',
	'SMW\MessageReporter'           => 'includes/MessageReporter.php',
	'SMW\ObservableMessageReporter' => 'includes/MessageReporter.php',
	'SMW\ContentParser'             => 'includes/ContentParser.php',
	'SMW\UpdateObserver'            => 'includes/UpdateObserver.php',
	'SMW\TitleAccess'               => 'includes/TitleAccess.php',

	'SMW\Observable'                => 'includes/ObservableSubject.php',
	'SMW\Publisher'                 => 'includes/ObservableSubject.php',
	'SMW\ObservableSubject'         => 'includes/ObservableSubject.php',
	'SMW\StoreUpdater'              => 'includes/StoreUpdater.php',

	'SMW\Observer'                  => 'includes/Observer.php',
	'SMW\Subscriber'                => 'includes/Observer.php',

	'SMW\ObservableDispatcher'        => 'includes/ObservableSubjectDispatcher.php',
	'SMW\DispatchableSubject'         => 'includes/ObservableSubjectDispatcher.php',
	'SMW\ObservableSubjectDispatcher' => 'includes/ObservableSubjectDispatcher.php',

	'SMW\Cacheable'                 => 'includes/Cacheable.php',
	'SMW\Configurable'              => 'includes/Configurable.php',
	'SMW\StoreAccess'               => 'includes/StoreAccess.php',

	'SMW\CacheHandler'          => 'includes/cache/CacheHandler.php',
	'SMW\ResultCacheMapper'     => 'includes/cache/ResultCacheMapper.php',
	'SMW\CacheIdGenerator'      => 'includes/cache/CacheIdGenerator.php',

	// Hooks
	'SMW\HookBase'                  => 'includes/hooks/HookBase.php',
	'SMW\MediaWikiHook'             => 'includes/hooks/MediaWikiHook.php',
	'SMW\HooksLoader'               => 'includes/hooks/HooksLoader.php',
	'SMW\LinksUpdateConstructed'    => 'includes/hooks/LinksUpdateConstructed.php',
	'SMW\BeforePageDisplay'         => 'includes/hooks/BeforePageDisplay.php',

	// Formatters
	'SMW\ArrayFormatter'               => 'includes/formatters/ArrayFormatter.php',
	'SMW\ParserParameterFormatter'     => 'includes/formatters/ParserParameterFormatter.php',
	'SMW\MessageFormatter'             => 'includes/formatters/MessageFormatter.php',
	'SMW\TableFormatter'               => 'includes/formatters/TableFormatter.php',
	'SMW\ParameterFormatterFactory'    => 'includes/formatters/ParameterFormatterFactory.php',
	'SMW\ApiQueryResultFormatter'      => 'includes/formatters/ApiQueryResultFormatter.php',
	'SMW\ApiRequestParameterFormatter' => 'includes/formatters/ApiRequestParameterFormatter.php',

	// Exceptions
	'SMW\InvalidStoreException'        => 'includes/exceptions/InvalidStoreException.php',
	'SMW\InvalidSemanticDataException' => 'includes/exceptions/InvalidSemanticDataException.php',
	'SMW\InvalidNamespaceException'    => 'includes/exceptions/InvalidNamespaceException.php',
	'SMW\InvalidPropertyException'     => 'includes/exceptions/InvalidPropertyException.php',
	'SMW\InvalidResultException'       => 'includes/exceptions/InvalidResultException.php',
	'SMW\DataItemException'            => 'includes/exceptions/DataItemException.php', // 1.9
	'SMWDataItemException'             => 'includes/exceptions/DataItemException.php',
	'SMW\UnknownIdException'           => 'includes/exceptions/UnknownIdException.php',
	'SMW\InvalidSettingsArgumentException'   => 'includes/exceptions/InvalidSettingsArgumentException.php',
	'SMW\InvalidPredefinedPropertyException' => 'includes/exceptions/InvalidPredefinedPropertyException.php',

	// Query pages
	'SMW\QueryPage'                 => 'includes/querypages/QueryPage.php',
	'SMW\WantedPropertiesQueryPage' => 'includes/querypages/WantedPropertiesQueryPage.php',
	'SMW\UnusedPropertiesQueryPage' => 'includes/querypages/UnusedPropertiesQueryPage.php',
	'SMW\PropertiesQueryPage'       => 'includes/querypages/PropertiesQueryPage.php',

	// Article pages
	'SMWOrderedListPage'        => 'includes/articlepages/SMW_OrderedListPage.php',
	'SMWPropertyPage'           => 'includes/articlepages/SMW_PropertyPage.php',
	'SMW\ConceptPage'           => 'includes/articlepages/ConceptPage.php',

	// Printers
	'SMWExportPrinter'          => 'includes/queryprinters/FileExportPrinter.php',
	'SMW\FileExportPrinter'     => 'includes/queryprinters/FileExportPrinter.php',
	'SMW\ExportPrinter'         => 'includes/queryprinters/ExportPrinter.php',
	'SMWIResultPrinter'         => 'includes/queryprinters/SMW_IResultPrinter.php',
	'SMWTableResultPrinter'     => 'includes/queryprinters/TableResultPrinter.php',
	'SMW\TableResultPrinter'    => 'includes/queryprinters/TableResultPrinter.php', // 1.9
	'SMWCategoryResultPrinter'  => 'includes/queryprinters/SMW_QP_Category.php',
	'SMWEmbeddedResultPrinter'  => 'includes/queryprinters/SMW_QP_Embedded.php',
	'SMWCsvResultPrinter'       => 'includes/queryprinters/CsvResultPrinter.php',
	'SMW\CsvResultPrinter'      => 'includes/queryprinters/CsvResultPrinter.php', // 1.9
	'SMWDSVResultPrinter'       => 'includes/queryprinters/SMW_QP_DSV.php',
	'SMWRDFResultPrinter'       => 'includes/queryprinters/SMW_QP_RDF.php',
	'SMWResultPrinter'          => 'includes/queryprinters/ResultPrinter.php',
	'SMW\ResultPrinter'         => 'includes/queryprinters/ResultPrinter.php', // 1.9
	'SMW\ApiResultPrinter'      => 'includes/queryprinters/ApiResultPrinter.php',
	'SMWListResultPrinter'      => 'includes/queryprinters/ListResultPrinter.php',
	'SMW\ListResultPrinter'     => 'includes/queryprinters/ListResultPrinter.php',
	'SMW\FeedResultPrinter'     => 'includes/queryprinters/FeedResultPrinter.php', // 1.9
	'SMWJsonResultPrinter'      => 'includes/queryprinters/JsonResultPrinter.php',
	'SMW\JsonResultPrinter'     => 'includes/queryprinters/JsonResultPrinter.php', // 1.9
	'SMWAggregatablePrinter'    => 'includes/queryprinters/AggregatablePrinter.php',
	'SMW\AggregatablePrinter'   => 'includes/queryprinters/AggregatablePrinter.php', // 1.9

	// Data items
	'SMWDataItem'               => 'includes/dataitems/SMW_DataItem.php',
	'SMWDIProperty'             => 'includes/dataitems/SMW_DI_Property.php',
	'SMW\DIProperty'            => 'includes/dataitems/SMW_DI_Property.php', // 1.9
	'SMWDIBoolean'              => 'includes/dataitems/SMW_DI_Bool.php',
	'SMWDINumber'               => 'includes/dataitems/SMW_DI_Number.php',
	'SMWDIBlob'                 => 'includes/dataitems/SMW_DI_Blob.php',
	'SMWDIString'               => 'includes/dataitems/SMW_DI_String.php',
	'SMWStringLengthException'  => 'includes/dataitems/SMW_DI_String.php',
	'SMWDIUri'                  => 'includes/dataitems/SMW_DI_URI.php',
	'SMWDIWikiPage'             => 'includes/dataitems/SMW_DI_WikiPage.php',
	'SMW\DIWikiPage'            => 'includes/dataitems/SMW_DI_WikiPage.php', // 1.9
	'SMWDITime'                 => 'includes/dataitems/SMW_DI_Time.php',
	'SMWDIError'                => 'includes/dataitems/SMW_DI_Error.php',
	'SMWDIGeoCoord'             => 'includes/dataitems/SMW_DI_GeoCoord.php',
	'SMWContainerSemanticData'  => 'includes/dataitems/SMW_DI_Container.php',
	'SMWDIContainer'            => 'includes/dataitems/SMW_DI_Container.php',
	'SMWDISerializer'           => 'includes/dataitems/DISerializer.php',
	'SMW\DISerializer'          => 'includes/dataitems/DISerializer.php', // 1.9
	'SMWDIConcept'              => 'includes/dataitems/DIConcept.php',
	'SMW\DIConcept'             => 'includes/dataitems/DIConcept.php', // 1.9

	// Datavalues
	'SMWDataValue'              => 'includes/datavalues/SMW_DataValue.php',
	'SMWRecordValue'            => 'includes/datavalues/SMW_DV_Record.php',
	'SMWErrorValue'             => 'includes/datavalues/SMW_DV_Error.php',
	'SMWStringValue'            => 'includes/datavalues/SMW_DV_String.php',
	'SMWWikiPageValue'          => 'includes/datavalues/SMW_DV_WikiPage.php',
	'SMW\WikiPageValue'         => 'includes/datavalues/SMW_DV_WikiPage.php',
	'SMWPropertyValue'          => 'includes/datavalues/SMW_DV_Property.php',
	'SMWURIValue'               => 'includes/datavalues/SMW_DV_URI.php',
	'SMWTypesValue'             => 'includes/datavalues/SMW_DV_Types.php',
	'SMWPropertyListValue'      => 'includes/datavalues/SMW_DV_PropertyList.php',
	'SMWNumberValue'            => 'includes/datavalues/SMW_DV_Number.php',
	'SMWTemperatureValue'       => 'includes/datavalues/SMW_DV_Temperature.php',
	'SMWQuantityValue'          => 'includes/datavalues/SMW_DV_Quantity.php',
	'SMWTimeValue'              => 'includes/datavalues/SMW_DV_Time.php',
	'SMWBoolValue'              => 'includes/datavalues/SMW_DV_Bool.php',
	'SMWConceptValue'           => 'includes/datavalues/SMW_DV_Concept.php',
	'SMWImportValue'            => 'includes/datavalues/SMW_DV_Import.php',

	// Export
	'SMWExporter'               => 'includes/export/SMW_Exporter.php',
	'SMWExpData'                => 'includes/export/SMW_Exp_Data.php',
	'SMWExpElement'             => 'includes/export/SMW_Exp_Element.php',
	'SMWExpLiteral'             => 'includes/export/SMW_Exp_Element.php',
	'SMWExpResource'            => 'includes/export/SMW_Exp_Element.php',
	'SMWExpNsResource'          => 'includes/export/SMW_Exp_Element.php',
	'SMWExportController'       => 'includes/export/SMW_ExportController.php',
	'SMWSerializer'	            => 'includes/export/SMW_Serializer.php',
	'SMWRDFXMLSerializer'       => 'includes/export/SMW_Serializer_RDFXML.php',
	'SMWTurtleSerializer'       => 'includes/export/SMW_Serializer_Turtle.php',

	// Param classes
	'SMWParamFormat'            => 'includes/params/SMW_ParamFormat.php',
	'SMWParamSource'            => 'includes/params/SMW_ParamSource.php',

	// Parser hooks
	'SMW\InfoParserFunction'             => 'includes/parserhooks/InfoParserFunction.php',
	'SMW\ConceptParserFunction'          => 'includes/parserhooks/ConceptParserFunction.php',
	'SMW\DeclareParserFunction'          => 'includes/parserhooks/DeclareParserFunction.php',
	'SMW\SetParserFunction'              => 'includes/parserhooks/SetParserFunction.php',
	'SMW\AskParserFunction'              => 'includes/parserhooks/AskParserFunction.php',
	'SMW\ShowParserFunction'             => 'includes/parserhooks/ShowParserFunction.php',
	'SMW\ConceptParserFunction'          => 'includes/parserhooks/ConceptParserFunction.php',
	'SMW\SubobjectParserFunction'        => 'includes/parserhooks/SubobjectParserFunction.php',
	'SMW\RecurringEventsParserFunction'  => 'includes/parserhooks/RecurringEventsParserFunction.php',
	'SMW\DocumentationParserFunction'    => 'includes/parserhooks/DocumentationParserFunction.php',
	'SMW\ParserFunctionFactory'          => 'includes/parserhooks/ParserFunctionFactory.php',

	// Query related classes
	'SMW\QueryData'              => 'includes/query/QueryData.php',
	'SMWQueryProcessor'          => 'includes/query/SMW_QueryProcessor.php',
	'SMWQueryParser'             => 'includes/query/SMW_QueryParser.php',
	'SMWQueryLanguage'           => 'includes/query/SMW_QueryLanguage.php',
	'SMWQuery'                   => 'includes/query/SMW_Query.php',
	'SMWPrintRequest'            => 'includes/query/SMW_PrintRequest.php',
	'SMWThingDescription'        => 'includes/query/SMW_Description.php',
	'SMWClassDescription'        => 'includes/query/SMW_Description.php',
	'SMWConceptDescription'      => 'includes/query/SMW_Description.php',
	'SMWNamespaceDescription'    => 'includes/query/SMW_Description.php',
	'SMWValueDescription'        => 'includes/query/SMW_Description.php',
	'SMWConjunction'             => 'includes/query/SMW_Description.php',
	'SMWDisjunction'             => 'includes/query/SMW_Description.php',
	'SMWSomeProperty'            => 'includes/query/SMW_Description.php',

	// Stores & queries
	'SMWSparqlDatabase'          => 'includes/sparql/SMW_SparqlDatabase.php',
	'SMWSparqlDatabase4Store'    => 'includes/sparql/SMW_SparqlDatabase4Store.php',
	'SMWSparqlDatabaseVirtuoso'  => 'includes/sparql/SMW_SparqlDatabaseVirtuoso.php',
	'SMWSparqlDatabaseError'     => 'includes/sparql/SMW_SparqlDatabase.php',
	'SMWSparqlResultWrapper'     => 'includes/sparql/SMW_SparqlResultWrapper.php',
	'SMWSparqlResultParser'      => 'includes/sparql/SMW_SparqlResultParser.php',

	'SMW\Store\PropertyStatisticsRebuilder' => 'includes/storage/PropertyStatisticsRebuilder.php',
	'SMW\Store\PropertyStatisticsStore'     => 'includes/storage/PropertyStatisticsStore.php',
	'SMW\StoreFactory'                      => 'includes/storage/StoreFactory.php',
	'SMW\Store\Collectible'                 => 'includes/storage/Collector.php',
	'SMW\Store\Collector'                   => 'includes/storage/Collector.php',
	'SMWQueryResult'                        => 'includes/storage/SMW_QueryResult.php',
	'SMWResultArray'                        => 'includes/storage/SMW_ResultArray.php',
	'SMWStore'                              => 'includes/storage/SMW_Store.php',
	'SMW\Store'                             => 'includes/storage/SMW_Store.php',
	'SMWStringCondition'                    => 'includes/storage/SMW_Store.php',
	'SMWRequestOptions'                     => 'includes/storage/SMW_RequestOptions.php',
	'SMWSparqlStore'                        => 'includes/storage/SMW_SparqlStore.php',
	'SMWSparqlStoreQueryEngine'             => 'includes/storage/SMW_SparqlStoreQueryEngine.php',
	'SMWSQLHelpers'                         => 'includes/storage/SMW_SQLHelpers.php',

	// SQLStore (since 1.8)
	'SMW\SQLStore\PropertyStatisticsTable'           => 'includes/storage/SQLStore/PropertyStatisticsTable.php',
	'SMW\SQLStore\SimplePropertyStatisticsRebuilder' => 'includes/storage/SQLStore/SimplePropertyStatisticsRebuilder.php',
	'SMW\SQLStore\StatisticsCollector'               => 'includes/storage/SQLStore/StatisticsCollector.php',
	'SMW\SQLStore\WantedPropertiesCollector'         => 'includes/storage/SQLStore/WantedPropertiesCollector.php',
	'SMW\SQLStore\UnusedPropertiesCollector'         => 'includes/storage/SQLStore/UnusedPropertiesCollector.php',
	'SMW\SQLStore\PropertiesCollector'               => 'includes/storage/SQLStore/PropertiesCollector.php',
	'SMW\SQLStore\PropertyTableDefinitionBuilder'    => 'includes/storage/SQLStore/PropertyTableDefinitionBuilder.php',

	'SMWSQLStore3Table'                => 'includes/storage/SQLStore/SMW_SQLStore3Table.php', // Please fix me ...
	'SMW\SQLStore\TableDefinition'     => 'includes/storage/SQLStore/SMW_SQLStore3Table.php',

	'SMWSQLStore3'                     => 'includes/storage/SQLStore/SMW_SQLStore3.php',
	'SMWSql3StubSemanticData'          => 'includes/storage/SQLStore/SMW_Sql3StubSemanticData.php',
	'SMWSql3SmwIds'                    => 'includes/storage/SQLStore/SMW_Sql3SmwIds.php',
	'SMWSQLStore3Readers'              => 'includes/storage/SQLStore/SMW_SQLStore3_Readers.php',
	'SMWSQLStore3QueryEngine'          => 'includes/storage/SQLStore/SMW_SQLStore3_Queries.php',
	'SMWSQLStore3Query'                => 'includes/storage/SQLStore/SMW_SQLStore3_Queries.php',
	'SMWSQLStore3Writers'              => 'includes/storage/SQLStore/SMW_SQLStore3_Writers.php',
	'SMWSQLStore3SpecialPageHandlers'  => 'includes/storage/SQLStore/SMW_SQLStore3_SpecialPageHandlers.php',
	'SMWSQLStore3SetupHandlers'        => 'includes/storage/SQLStore/SMW_SQLStore3_SetupHandlers.php',
	'SMWDataItemHandler'               => 'includes/storage/SQLStore/SMW_DataItemHandler.php',
	'SMWDIHandlerBoolean'              => 'includes/storage/SQLStore/SMW_DIHandler_Bool.php',
	'SMWDIHandlerNumber'               => 'includes/storage/SQLStore/SMW_DIHandler_Number.php',
	'SMWDIHandlerBlob'                 => 'includes/storage/SQLStore/SMW_DIHandler_Blob.php',
	'SMWDIHandlerUri'                  => 'includes/storage/SQLStore/SMW_DIHandler_URI.php',
	'SMWDIHandlerWikiPage'             => 'includes/storage/SQLStore/SMW_DIHandler_WikiPage.php',
	'SMWDIHandlerTime'                 => 'includes/storage/SQLStore/SMW_DIHandler_Time.php',
	'SMWDIHandlerConcept'              => 'includes/storage/SQLStore/SMW_DIHandler_Concept.php',
	'SMWDIHandlerGeoCoord'             => 'includes/storage/SQLStore/SMW_DIHandler_GeoCoord.php',

	// Special pages
	'SMW\SpecialPage'               => 'includes/specials/SpecialPage.php',
	'SMW\SpecialSemanticStatistics' => 'includes/specials/SpecialSemanticStatistics.php',
	'SMW\SpecialConcepts'           => 'includes/specials/SpecialConcepts.php',
	'SMW\SpecialWantedProperties'   => 'includes/specials/SpecialWantedProperties.php',
	'SMW\SpecialUnusedProperties'   => 'includes/specials/SpecialUnusedProperties.php',
	'SMW\SpecialProperties'         => 'includes/specials/SpecialProperties.php',

	'SMWAskPage'                    => 'includes/specials/SMW_SpecialAsk.php',
	'SMWQuerySpecialPage'           => 'includes/specials/SMW_QuerySpecialPage.php',
	'SMWSpecialBrowse'              => 'includes/specials/SMW_SpecialBrowse.php',
	'SMWPageProperty'               => 'includes/specials/SMW_SpecialPageProperty.php',
	'SMWSearchByProperty'           => 'includes/specials/SMW_SpecialSearchByProperty.php',
	'SMWURIResolver'                => 'includes/specials/SMW_SpecialURIResolver.php',
	'SMWAdmin'                      => 'includes/specials/SMW_SpecialSMWAdmin.php',
	'SMWSpecialOWLExport'           => 'includes/specials/SMW_SpecialOWLExport.php',
	'SMWSpecialTypes'               => 'includes/specials/SMW_SpecialTypes.php',

	// Test cases
	'SMW\Test\ResultPrinterTestCase'         => 'tests/phpunit/QueryPrinterRegistryTestCase.php',
	'SMW\Test\QueryPrinterRegistryTestCase'  => 'tests/phpunit/QueryPrinterRegistryTestCase.php',
	'SMW\Test\QueryPrinterTestCase'          => 'tests/phpunit/QueryPrinterTestCase.php',
	'SMW\Tests\DataItemTest'                 => 'tests/phpunit/includes/dataitems/DataItemTest.php',
	'SMW\Test\SemanticMediaWikiTestCase'     => 'tests/phpunit/SemanticMediaWikiTestCase.php',
	'SMW\Test\ParserTestCase'                => 'tests/phpunit/ParserTestCase.php',
	'SMW\Test\ApiTestCase'                   => 'tests/phpunit/ApiTestCase.php',
	'SMW\Test\MockSuperUser'                 => 'tests/phpunit/MockSuperUser.php',
	'SMW\Test\MockObjectBuilder'             => 'tests/phpunit/MockObjectBuilder.php',
	'SMW\Test\SpecialPageTestCase'           => 'tests/phpunit/SpecialPageTestCase.php',
	'SMW\Test\CompatibilityTestCase'         => 'tests/phpunit/CompatibilityTestCase.php',
	'SMW\Test\MockUpdateObserver'            => 'tests/phpunit/MockUpdateObserver.php',

	// Jobs
	'SMW\UpdateDispatcherJob' => 'includes/jobs/UpdateDispatcherJob.php',
	'SMW\JobBase'             => 'includes/jobs/JobBase.php',
	'SMWUpdateJob'            => 'includes/jobs/UpdateJob.php',
	'SMW\UpdateJob'           => 'includes/jobs/UpdateJob.php', // 1.9
	'SMWRefreshJob'           => 'includes/jobs/SMW_RefreshJob.php',

	// API modules
	'SMW\ApiBase'    => 'includes/api/ApiBase.php',
	'SMW\ApiQuery'   => 'includes/api/ApiQuery.php',
	'SMW\ApiAsk'     => 'includes/api/ApiAsk.php',
	'SMW\ApiAskArgs' => 'includes/api/ApiAskArgs.php',
	'SMW\ApiInfo'    => 'includes/api/ApiInfo.php',

	// Maintenance scripts
	'SMWSetupScript' => 'maintenance/SMW_setup.php',
);

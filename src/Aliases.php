<?php

/**
 * SemanticMediaWiki compatibility aliases for classes that got moved into the SMW namespace
 */

// 3.0
class_alias( 'SMW\Updater\DeferredCallableUpdate', 'SMW\DeferredCallableUpdate' );
class_alias( 'SMW\Parser\InTextAnnotationParser', 'SMW\InTextAnnotationParser' );
class_alias( 'SMW\Encoder', 'SMW\UrlEncoder' );
class_alias( 'SMW\Query\ResultPrinter', 'SMW\QueryResultPrinter' );
class_alias( 'SMW\Query\ResultPrinter', 'SMWIResultPrinter' );
class_alias( 'SMW\Query\ExportPrinter', 'SMW\ExportPrinter' );
class_alias( 'SMW\Query\ResultPrinters\ResultPrinter', 'SMW\ResultPrinter' );
class_alias( 'SMW\Query\ResultPrinters\ResultPrinter', 'SMWResultPrinter' );
class_alias( 'SMW\Query\ResultPrinters\FileExportPrinter', 'SMW\FileExportPrinter' );
class_alias( 'SMW\Query\Parser', 'SMWQueryParser' );
class_alias( 'SMW\SQLStore\ChangeOp\ChangeOp', 'SMW\SQLStore\CompositePropertyTableDiffIterator' );
class_alias( 'SMW\Connection\ConnectionProvider', 'SMW\DBConnectionProvider' );
class_alias( 'SMW\DataValues\TypesValue', 'SMWTypesValue' );
class_alias( 'SMW\DataValues\PropertyValue', 'SMWPropertyValue' );

// 1.9.
class_alias( 'SMW\Store', 'SMWStore' );
class_alias( 'SMW\MediaWiki\Jobs\UpdateJob', 'SMWUpdateJob' );
class_alias( 'SMW\MediaWiki\Jobs\RefreshJob', 'SMWRefreshJob' );
class_alias( 'SMW\SemanticData', 'SMWSemanticData' );
class_alias( 'SMW\DIWikiPage', 'SMWDIWikiPage' );
class_alias( 'SMW\DIProperty', 'SMWDIProperty' );
class_alias( 'SMW\Serializers\QueryResultSerializer', 'SMWDISerializer' );
class_alias( 'SMW\DataValueFactory', 'SMWDataValueFactory' );
class_alias( 'SMW\Exception\DataItemException', 'SMWDataItemException' );
class_alias( 'SMW\SQLStore\PropertyTableDefinition', 'SMWSQLStore3Table' );
class_alias( 'SMW\DIConcept', 'SMWDIConcept' );
class_alias( 'SMW\Query\ResultPrinters\TableResultPrinter', 'SMWTableResultPrinter' );

// 2.0
class_alias( 'SMW\FileExportPrinter', 'SMWExportPrinter' );
class_alias( 'SMW\AggregatablePrinter', 'SMWAggregatablePrinter' );
class_alias( 'SMW\CategoryResultPrinter', 'SMWCategoryResultPrinter' );
class_alias( 'SMW\DsvResultPrinter', 'SMWDSVResultPrinter' );
class_alias( 'SMW\EmbeddedResultPrinter', 'SMWEmbeddedResultPrinter' );
class_alias( 'SMW\RdfResultPrinter', 'SMWRDFResultPrinter' );
class_alias( 'SMW\ListResultPrinter', 'SMWListResultPrinter' );
class_alias( 'SMW\RawResultPrinter', 'SMW\ApiResultPrinter' );

// 2.0
class_alias( 'SMW\SPARQLStore\SPARQLStore', 'SMWSparqlStore' );
class_alias( 'SMW\SPARQLStore\RepositoryConnectors\FourstoreRepositoryConnector', 'SMWSparqlDatabase4Store' );
class_alias( 'SMW\SPARQLStore\RepositoryConnectors\VirtuosoRepositoryConnector', 'SMWSparqlDatabaseVirtuoso' );
class_alias( 'SMW\SPARQLStore\RepositoryConnectors\GenericRepositoryConnector', 'SMWSparqlDatabase' );

// 2.1
class_alias( 'SMWSQLStore3', 'SMW\SQLStore\SQLStore' );
class_alias( 'SMW\Query\Language\Description', 'SMWDescription' );
class_alias( 'SMW\Query\Language\ThingDescription', 'SMWThingDescription' );
class_alias( 'SMW\Query\Language\ClassDescription', 'SMWClassDescription' );
class_alias( 'SMW\Query\Language\ConceptDescription', 'SMWConceptDescription' );
class_alias( 'SMW\Query\Language\NamespaceDescription', 'SMWNamespaceDescription' );
class_alias( 'SMW\Query\Language\ValueDescription', 'SMWValueDescription' );
class_alias( 'SMW\Query\Language\Conjunction', 'SMWConjunction' );
class_alias( 'SMW\Query\Language\Disjunction', 'SMWDisjunction' );
class_alias( 'SMW\Query\Language\SomeProperty', 'SMWSomeProperty' );
class_alias( 'SMW\Query\PrintRequest', 'SMWPrintRequest' );
class_alias( 'SMW\MediaWiki\Search\Search', 'SMWSearch' );

// 2.2
// Some weird SF dependency needs to be removed as quick as possible
class_alias( 'SMW\SQLStore\Lookup\ListLookup', 'SMW\SQLStore\PropertiesCollector' );
class_alias( 'SMW\SQLStore\Lookup\ListLookup', 'SMW\SQLStore\UnusedPropertiesCollector' );

class_alias( 'SMW\Exporter\Element\ExpElement', 'SMWExpElement' );
class_alias( 'SMW\Exporter\Element\ExpResource', 'SMWExpResource' );
class_alias( 'SMW\Exporter\Element\ExpNsResource', 'SMWExpNsResource' );
class_alias( 'SMW\Exporter\Element\ExpLiteral', 'SMWExpLiteral' );
class_alias( 'SMW\DataValues\ImportValue', 'SMWImportValue' );
class_alias( 'SMW\SQLStore\QueryEngine\QueryEngine', 'SMWSQLStore3QueryEngine' );

// 2.3
class_alias( 'SMW\ParserParameterProcessor', 'SMW\ParserParameterFormatter' );
class_alias( 'SMW\ParameterProcessorFactory', 'SMW\ParameterFormatterFactory' );

// 2.4
class_alias( 'SMW\RequestOptions', 'SMWRequestOptions' );
class_alias( 'SMW\StringCondition', 'SMWStringCondition' );
class_alias( 'SMW\HashBuilder', 'SMW\Hash' );
class_alias( 'SMW\DataValues\BooleanValue', 'SMWBoolValue' );

// 2.5
class_alias( 'SMW\QueryPrinterFactory', 'SMW\FormatFactory' );
class_alias( 'SMW\ParserFunctions\SubobjectParserFunction', 'SMW\SubobjectParserFunction' );
class_alias( 'SMW\ParserFunctions\RecurringEventsParserFunction', 'SMW\RecurringEventsParserFunction' );
class_alias( 'SMW\SQLStore\PropertyTableDefinition', 'SMW\SQLStore\TableDefinition' );
class_alias( 'SMW\DataModel\ContainerSemanticData', 'SMWContainerSemanticData' );

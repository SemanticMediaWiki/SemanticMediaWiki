<?php

/**
 * SemanticMediaWiki compatibility aliases for classes that got moved into the SMW namespace
 */

// 3.1
class_alias( \SMW\Query\ResultPrinters\RdfResultPrinter::class, 'SMWRDFResultPrinter' );
class_alias( \SMW\Query\ResultPrinters\EmbeddedResultPrinter::class, 'SMWEmbeddedResultPrinter' );
class_alias( \SMW\Query\ResultPrinters\DsvResultPrinter::class, 'SMWDSVResultPrinter' );
class_alias( \SMW\Query\ResultPrinters\AggregatablePrinter::class, 'SMWAggregatablePrinter' );
class_alias( \SMW\Property\Annotator::class, 'SMW\PropertyAnnotator' );
class_alias( \SMW\Property\SpecificationLookup::class, 'SMW\PropertySpecificationLookup' );
class_alias( \SMW\Property\RestrictionExaminer::class, 'SMW\PropertyRestrictionExaminer' );
class_alias( \SMW\Query\Result\ResultArray::class, 'SMWResultArray' );
class_alias( \SMW\Query\QueryResult::class, 'SMWQueryResult' );
class_alias( \SMW\Services\ServicesFactory::class, '\SMW\ApplicationFactory' );
class_alias( \SMW\SQLStore\EntityStore\EntityIdManager::class, '\SMWSql3SmwIds' );

// 3.0
class_alias( \SMW\MediaWiki\Deferred\CallableUpdate::class, 'SMW\DeferredCallableUpdate' );
class_alias( \SMW\MediaWiki\Deferred\TransactionalCallableUpdate::class, 'SMW\DeferredTransactionalCallableUpdate' );
class_alias( \SMW\Parser\InTextAnnotationParser::class, 'SMW\InTextAnnotationParser' );
class_alias( \SMW\Encoder::class, 'SMW\UrlEncoder' );
class_alias( \SMW\Query\ResultPrinter::class, 'SMW\QueryResultPrinter' );
class_alias( \SMW\Query\ResultPrinter::class, 'SMWIResultPrinter' );
class_alias( \SMW\Query\ExportPrinter::class, 'SMW\ExportPrinter' );
class_alias( \SMW\Query\ResultPrinters\ResultPrinter::class, 'SMW\ResultPrinter' );
class_alias( \SMW\Query\ResultPrinters\ResultPrinter::class, 'SMWResultPrinter' );
class_alias( \SMW\Query\ResultPrinters\FileExportPrinter::class, 'SMW\FileExportPrinter' );
class_alias( \SMW\Query\ResultPrinters\ListResultPrinter::class, 'SMW\ListResultPrinter' );
class_alias( \SMW\Query\Parser::class, 'SMWQueryParser' );
class_alias( \SMW\SQLStore\ChangeOp\ChangeOp::class, 'SMW\SQLStore\CompositePropertyTableDiffIterator' );
class_alias( \SMW\Connection\ConnectionProvider::class, 'SMW\DBConnectionProvider' );
class_alias( \SMW\DataValues\TypesValue::class, 'SMWTypesValue' );
class_alias( \SMW\DataValues\PropertyValue::class, 'SMWPropertyValue' );
class_alias( \SMW\DataValues\StringValue::class, 'SMWStringValue' );
class_alias( \SMW\MediaWiki\Connection\Database::class, '\SMW\MediaWiki\Database' );
class_alias( \SMWDIBlob::class, 'SMWDIString' );

// 1.9.
class_alias( \SMW\Store::class, 'SMWStore' );
class_alias( \SMW\MediaWiki\Jobs\UpdateJob::class, 'SMWUpdateJob' );
class_alias( \SMW\MediaWiki\Jobs\RefreshJob::class, 'SMWRefreshJob' );
class_alias( \SMW\SemanticData::class, 'SMWSemanticData' );
class_alias( \SMW\DIWikiPage::class, 'SMWDIWikiPage' );
class_alias( \SMW\DIProperty::class, 'SMWDIProperty' );
class_alias( \SMW\Serializers\QueryResultSerializer::class, 'SMWDISerializer' );
class_alias( \SMW\DataValueFactory::class, 'SMWDataValueFactory' );
class_alias( \SMW\Exception\DataItemException::class, 'SMWDataItemException' );
class_alias( \SMW\SQLStore\PropertyTableDefinition::class, 'SMWSQLStore3Table' );
class_alias( \SMW\DIConcept::class, 'SMWDIConcept' );
class_alias( \SMW\Query\ResultPrinters\TableResultPrinter::class, 'SMWTableResultPrinter' );

// 2.0
class_alias( \SMW\Query\ResultPrinters\FileExportPrinter::class, 'SMWExportPrinter' );
class_alias( \SMW\Query\ResultPrinters\CategoryResultPrinter::class, 'SMWCategoryResultPrinter' );
class_alias( \SMW\ListResultPrinter::class, 'SMWListResultPrinter' );

// 2.0
class_alias( \SMW\SPARQLStore\SPARQLStore::class, 'SMWSparqlStore' );
class_alias( \SMW\SPARQLStore\RepositoryConnectors\FourstoreRepositoryConnector::class, 'SMWSparqlDatabase4Store' );
class_alias( \SMW\SPARQLStore\RepositoryConnectors\VirtuosoRepositoryConnector::class, 'SMWSparqlDatabaseVirtuoso' );
class_alias( \SMW\SPARQLStore\RepositoryConnectors\GenericRepositoryConnector::class, 'SMWSparqlDatabase' );

// 2.1
class_alias( \SMW\SQLStore\SQLStore::class, 'SMWSQLStore3' );
class_alias( \SMW\Query\Language\Description::class, 'SMWDescription' );
class_alias( \SMW\Query\Language\ThingDescription::class, 'SMWThingDescription' );
class_alias( \SMW\Query\Language\ClassDescription::class, 'SMWClassDescription' );
class_alias( \SMW\Query\Language\ConceptDescription::class, 'SMWConceptDescription' );
class_alias( \SMW\Query\Language\NamespaceDescription::class, 'SMWNamespaceDescription' );
class_alias( \SMW\Query\Language\ValueDescription::class, 'SMWValueDescription' );
class_alias( \SMW\Query\Language\Conjunction::class, 'SMWConjunction' );
class_alias( \SMW\Query\Language\Disjunction::class, 'SMWDisjunction' );
class_alias( \SMW\Query\Language\SomeProperty::class, 'SMWSomeProperty' );
class_alias( \SMW\Query\PrintRequest::class, 'SMWPrintRequest' );
class_alias( \SMW\MediaWiki\Search\ExtendedSearchEngine::class, 'SMWSearch' );

// 2.2
// Some weird SF dependency needs to be removed as quick as possible
class_alias( \SMW\SQLStore\Lookup\ListLookup::class, 'SMW\SQLStore\PropertiesCollector' );
class_alias( \SMW\SQLStore\Lookup\ListLookup::class, 'SMW\SQLStore\UnusedPropertiesCollector' );

class_alias( \SMW\Exporter\Element\ExpElement::class, 'SMWExpElement' );
class_alias( \SMW\Exporter\Element\ExpResource::class, 'SMWExpResource' );
class_alias( \SMW\Exporter\Element\ExpNsResource::class, 'SMWExpNsResource' );
class_alias( \SMW\Exporter\Element\ExpLiteral::class, 'SMWExpLiteral' );
class_alias( \SMW\DataValues\ImportValue::class, 'SMWImportValue' );
class_alias( \SMW\SQLStore\QueryEngine\QueryEngine::class, 'SMWSQLStore3QueryEngine' );

// 2.3
class_alias( \SMW\ParserParameterProcessor::class, 'SMW\ParserParameterFormatter' );
class_alias( \SMW\ParameterProcessorFactory::class, 'SMW\ParameterFormatterFactory' );

// 2.4
class_alias( \SMW\RequestOptions::class, 'SMWRequestOptions' );
class_alias( \SMW\StringCondition::class, 'SMWStringCondition' );
class_alias( \SMW\HashBuilder::class, 'SMW\Hash' );
class_alias( \SMW\DataValues\BooleanValue::class, 'SMWBoolValue' );

// 2.5
class_alias( \SMW\QueryPrinterFactory::class, 'SMW\FormatFactory' );
class_alias( \SMW\ParserFunctions\SubobjectParserFunction::class, 'SMW\SubobjectParserFunction' );
class_alias( \SMW\ParserFunctions\RecurringEventsParserFunction::class, 'SMW\RecurringEventsParserFunction' );
class_alias( \SMW\SQLStore\PropertyTableDefinition::class, 'SMW\SQLStore\TableDefinition' );
class_alias( \SMW\DataModel\ContainerSemanticData::class, 'SMWContainerSemanticData' );

// 3.0 (late alias definition)
class_alias( \SMW\Elastic\ElasticStore::class, 'SMWElasticStore' );

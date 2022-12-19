<?php

use SMW\Globals;
use SMW\NamespaceManager;
use SMW\Services\ServicesFactory;
use SMW\Setup;
use SMW\SetupCheck;

/**
 * @codeCoverageIgnore
 *
 * This documentation group collects source code files belonging to Semantic
 * MediaWiki.
 *
 * For documenting extensions of SMW, please do not use groups starting with
 * "SMW" but make your own groups instead. Browsing at
 * https://doc.semantic-mediawiki.org/ is assumed to be easier this way.
 *
 * @defgroup SMW Semantic MediaWiki
 */
class SemanticMediaWiki {

	/**
	 * @since 2.4
	 */
	public static function initExtension( $credits = [] ) {

		if ( !defined( 'SMW_VERSION' ) && isset( $credits['version'] ) ) {
			define( 'SMW_VERSION', $credits['version'] );
			self::setupAliases();
			self::setupDefines();
			self::setupGlobals();
			require_once ( dirname( __DIR__ ) . "/includes/GlobalFunctions.php" );
		}

		// https://phabricator.wikimedia.org/T212738
		if ( !defined( 'MW_VERSION' ) ) {
			define( 'MW_VERSION', $GLOBALS['wgVersion'] );
		}

		// We're moving away from enableSemantics, so set this here.
		if ( !defined( 'SMW_EXTENSION_LOADED' ) ) {
			define( 'SMW_EXTENSION_LOADED', true );
		}

		// Registration point for required early registration
		Globals::replace(
			Setup::initExtension( $GLOBALS )
		);

		// Apparently this is required (1.28+) as the earliest possible execution
		// point in order for settings that refer to the SMW_NS_PROPERTY namespace
		// to be available in LocalSettings
		Globals::replace(
			NamespaceManager::initCustomNamespace( $GLOBALS )['newVars']
		);
	}

	/**
	 * Setup and initialization
	 *
	 * @note $wgExtensionFunctions variable is an array that stores
	 * functions to be called after most of MediaWiki initialization
	 * has finalized
	 *
	 * @see https://www.mediawiki.org/wiki/Manual:$wgExtensionFunctions
	 *
	 * @since  1.9
	 */
	public static function onExtensionFunction() {

		$namespace = new NamespaceManager();
		Globals::replace(
			$namespace->init( $GLOBALS )
		);

		$setup = new Setup();

		$setup->setHookDispatcher(
			ServicesFactory::getInstance()->getHookDispatcher()
		);

		Globals::replace(
			$setup->init( $GLOBALS, __DIR__ )
		);
	}

	/**
	 * Get an where the key is the old class name and the value is the new
	 * name.
	 */
	public static function getClassAliasMap(): array {
		return [
			// 3.2
			'\SMW\Localizer' => \SMW\Localizer\Localizer::class,
			'\SMW\Message' => \SMW\Localizer\Message::class,
			'\SMW\Lang\Lang' => \SMW\Localizer\LocalLanguage\LocalLanguage::class,
			'\SMWSerializer' => \SMW\Exporter\Serializer\Serializer::class,
			'\SMWTurtleSerializer' => \SMW\Exporter\Serializer\TurtleSerializer::class,
			'\SMWRDFXMLSerializer' => \SMW\Exporter\Serializer\RDFXMLSerializer::class,

			// 3.1
			'SMWRDFResultPrinter' => \SMW\Query\ResultPrinters\RdfResultPrinter::class,
			'SMWEmbeddedResultPrinter' => \SMW\Query\ResultPrinters\EmbeddedResultPrinter::class,
			'SMWDSVResultPrinter' => \SMW\Query\ResultPrinters\DsvResultPrinter::class,
			'SMWAggregatablePrinter' => \SMW\Query\ResultPrinters\AggregatablePrinter::class,
			'SMW\PropertyAnnotator' => \SMW\Property\Annotator::class,
			'SMW\PropertySpecificationLookup' => \SMW\Property\SpecificationLookup::class,
			'SMW\PropertyRestrictionExaminer' => \SMW\Property\RestrictionExaminer::class,
			'SMWResultArray' => \SMW\Query\Result\ResultArray::class,
			'SMWQueryResult' => \SMW\Query\QueryResult::class,
			'\SMW\ApplicationFactory' => \SMW\Services\ServicesFactory::class,
			'\SMWSql3SmwIds' => \SMW\SQLStore\EntityStore\EntityIdManager::class,

			// 3.0
			'SMW\DeferredCallableUpdate' => \SMW\MediaWiki\Deferred\CallableUpdate::class,
			'SMW\DeferredTransactionalCallableUpdate' => \SMW\MediaWiki\Deferred\TransactionalCallableUpdate::class,
			'SMW\InTextAnnotationParser' => \SMW\Parser\InTextAnnotationParser::class,
			'SMW\UrlEncoder' => \SMW\Encoder::class,
			'SMW\QueryResultPrinter' => \SMW\Query\ResultPrinter::class,
			'SMWIResultPrinter' => \SMW\Query\ResultPrinter::class,
			'SMW\ExportPrinter' => \SMW\Query\ExportPrinter::class,
			'SMW\ResultPrinter' => \SMW\Query\ResultPrinters\ResultPrinter::class,
			'SMWResultPrinter' => \SMW\Query\ResultPrinters\ResultPrinter::class,
			'SMW\FileExportPrinter' => \SMW\Query\ResultPrinters\FileExportPrinter::class,
			'SMW\ListResultPrinter' => \SMW\Query\ResultPrinters\ListResultPrinter::class,
			'SMWQueryParser' => \SMW\Query\Parser::class,
			'SMW\SQLStore\CompositePropertyTableDiffIterator' => \SMW\SQLStore\ChangeOp\ChangeOp::class,
			'SMW\DBConnectionProvider' => \SMW\Connection\ConnectionProvider::class,
			'SMWPropertyValue' => \SMW\DataValues\PropertyValue::class,
			'SMWStringValue' => \SMW\DataValues\StringValue::class,
			'\SMW\MediaWiki\Database' => \SMW\MediaWiki\Connection\Database::class,
			'SMWDIString' => \SMWDIBlob::class,

			// 1.9.
			'SMWStore' => \SMW\Store::class,
			'SMWUpdateJob' => \SMW\MediaWiki\Jobs\UpdateJob::class,
			'SMWRefreshJob' => \SMW\MediaWiki\Jobs\RefreshJob::class,
			'SMWSemanticData' => \SMW\SemanticData::class,
			'SMWDIWikiPage' => \SMW\DIWikiPage::class,
			'SMWDIProperty' => \SMW\DIProperty::class,
			'SMWDISerializer' => \SMW\Serializers\QueryResultSerializer::class,
			'SMWDataValueFactory' => \SMW\DataValueFactory::class,
			'SMWDataItemException' => \SMW\Exception\DataItemException::class,
			'SMWSQLStore3Table' => \SMW\SQLStore\PropertyTableDefinition::class,
			'SMWDIConcept' => \SMW\DIConcept::class,
			'SMWTableResultPrinter' => \SMW\Query\ResultPrinters\TableResultPrinter::class,

			// 2.0
			'SMWExportPrinter' => \SMW\Query\ResultPrinters\FileExportPrinter::class,
			'SMWCategoryResultPrinter' => \SMW\Query\ResultPrinters\CategoryResultPrinter::class,
			'SMWListResultPrinter' => \SMW\Query\ResultPrinters\ListResultPrinter::class,

			// 2.0
			'SMWSparqlStore' => \SMW\SPARQLStore\SPARQLStore::class,
			'SMWSparqlDatabase4Store' => \SMW\SPARQLStore\RepositoryConnectors\FourstoreRepositoryConnector::class,
			'SMWSparqlDatabaseVirtuoso' => \SMW\SPARQLStore\RepositoryConnectors\VirtuosoRepositoryConnector::class,
			'SMWSparqlDatabase' => \SMW\SPARQLStore\RepositoryConnectors\GenericRepositoryConnector::class,

			// 2.1
			'SMWSQLStore3' => \SMW\SQLStore\SQLStore::class,
			'SMWDescription' => \SMW\Query\Language\Description::class,
			'SMWThingDescription' => \SMW\Query\Language\ThingDescription::class,
			'SMWClassDescription' => \SMW\Query\Language\ClassDescription::class,
			'SMWConceptDescription' => \SMW\Query\Language\ConceptDescription::class,
			'SMWNamespaceDescription' => \SMW\Query\Language\NamespaceDescription::class,
			'SMWValueDescription' => \SMW\Query\Language\ValueDescription::class,
			'SMWConjunction' => \SMW\Query\Language\Conjunction::class,
			'SMWDisjunction' => \SMW\Query\Language\Disjunction::class,
			'SMWSomeProperty' => \SMW\Query\Language\SomeProperty::class,
			'SMWPrintRequest' => \SMW\Query\PrintRequest::class,
			'SMWSearch' => \SMW\MediaWiki\Search\ExtendedSearchEngine::class,

			// 2.2
			// Some weird SF dependency needs to be removed as quick as possible
			'SMW\SQLStore\PropertiesCollector' => \SMW\SQLStore\Lookup\ListLookup::class,
			'SMW\SQLStore\UnusedPropertiesCollector' => \SMW\SQLStore\Lookup\ListLookup::class,

			'SMWExpElement' => \SMW\Exporter\Element\ExpElement::class,
			'SMWExpResource' => \SMW\Exporter\Element\ExpResource::class,
			'SMWExpNsResource' => \SMW\Exporter\Element\ExpNsResource::class,
			'SMWExpLiteral' => \SMW\Exporter\Element\ExpLiteral::class,
			'SMWSQLStore3QueryEngine' => \SMW\SQLStore\QueryEngine\QueryEngine::class,

			// 2.3
			'SMW\ParserParameterFormatter' => \SMW\ParserParameterProcessor::class,
			'SMW\ParameterFormatterFactory' => \SMW\ParameterProcessorFactory::class,

			// 2.4
			'SMWRequestOptions' => \SMW\RequestOptions::class,
			'SMWStringCondition' => \SMW\StringCondition::class,
			'SMW\Hash' => \SMW\HashBuilder::class,
			'SMWBoolValue' => \SMW\DataValues\BooleanValue::class,

			// 2.5
			'SMW\FormatFactory' => \SMW\QueryPrinterFactory::class,
			'SMW\SubobjectParserFunction' => \SMW\ParserFunctions\SubobjectParserFunction::class,
			'SMW\RecurringEventsParserFunction' => \SMW\ParserFunctions\RecurringEventsParserFunction::class,
			'SMW\SQLStore\TableDefinition' => \SMW\SQLStore\PropertyTableDefinition::class,
			'SMWContainerSemanticData' => \SMW\DataModel\ContainerSemanticData::class,

			// 3.0 (late alias definition)
			'SMWElasticStore' => \SMW\Elastic\ElasticStore::class,
		];
	}

	/**
	 * SemanticMediaWiki compatibility aliases for classes that got moved into the SMW namespace
	 *
	 * @since 4.0
	 */
	public static function setupAliases(): void {
		foreach( self::getClassAliasMap() as $class => $canon ) {
			class_alias( $canon, $class );
		}
	}

	/**
	 * Constants relevant to Semantic MediaWiki
	 *
	 * @ingroup Constants
	 * @ingroup SMW
	 */
	public static function setupDefines() {

		if ( defined( 'SMW_SPECIAL_SEARCHTYPE' ) ) {
			return;
		}

		/**@{
		 * Constants for the search type
		 */
		define( 'SMW_SPECIAL_SEARCHTYPE', 'SMWSearch' );
		/**@}*/

		/**@{
		 * Constants for the exporter/OWL serializer
		 */
		define( 'SMW_SERIALIZER_DECL_CLASS', 1 );
		define( 'SMW_SERIALIZER_DECL_OPROP', 2 );
		define( 'SMW_SERIALIZER_DECL_APROP', 4 );
		/**@}*/

		/**@{
		 * Constants to indicate that the installer is called from the `ExtensionSchemaUpdates`
		 * hook.
		 */
		define( 'SMW_EXTENSION_SCHEMA_UPDATER', 'smw/extension/schema/updater' );
		/**@}*/

		/**@{
		 * SMW\ResultPrinter related constants that define
		 * how/if headers should be displayed
		 */
		define( 'SMW_HEADERS_SHOW', 2 );
		define( 'SMW_HEADERS_PLAIN', 1 );
		// Used to be "false" hence use "0" to support extensions that still assume this.
		define( 'SMW_HEADERS_HIDE', 0 );
		/**@}*/

		/**@{
		 * Constants for denoting output modes in many functions: HTML or Wiki?
		 * "File" is for printing results into stand-alone files (e.g. building RSS)
		 * and should be treated like HTML when building single strings. Only query
		 * printers tend to have special handling for that.
		 */
		define( 'SMW_OUTPUT_HTML', 1 );
		define( 'SMW_OUTPUT_WIKI', 2 );
		define( 'SMW_OUTPUT_FILE', 3 );
		define( 'SMW_OUTPUT_RAW', 4 );
		/**@}*/

		/**@{
		 * Constants for displaying the factbox
		 */
		define( 'SMW_FACTBOX_HIDDEN', 1 );
		define( 'SMW_FACTBOX_SPECIAL', 2 );
		define( 'SMW_FACTBOX_NONEMPTY', 3 );
		define( 'SMW_FACTBOX_SHOWN', 5 );

		define( 'SMW_FACTBOX_CACHE', 16 );
		define( 'SMW_FACTBOX_PURGE_REFRESH', 32 );
		define( 'SMW_FACTBOX_DISPLAY_SUBOBJECT', 64 );
		define( 'SMW_FACTBOX_DISPLAY_ATTACHMENT', 128 );

		/**@}*/

		/**@{
		 * Constants for regulating equality reasoning
		 */
		define( 'SMW_EQ_NONE', 1 );
		define( 'SMW_EQ_SOME', 2 );
		define( 'SMW_EQ_FULL', 4 );
		/**@}*/

		/**@{
		 * Constants for internal entity types
		 */
		define( 'SMW_SUBENTITY_MONOLINGUAL', '_ML' );
		define( 'SMW_SUBENTITY_REFERENCE', '_REF' );
		define( 'SMW_SUBENTITY_QUERY', '_QUERY' );
		define( 'SMW_SUBENTITY_ERROR', '_ERR' );
		/**@}*/

		/**@{
		 * Flags to classify available query descriptions,
		 * used to enable/disable certain features
		 */
		// [[some property::...]]
		define( 'SMW_PROPERTY_QUERY', 1 );
		// [[Category:...]]
		define( 'SMW_CATEGORY_QUERY', 2 );
		// [[Concept:...]]
		define( 'SMW_CONCEPT_QUERY', 4 );
		// [[User:+]] etc.
		define( 'SMW_NAMESPACE_QUERY', 8 );
		// any conjunctions
		define( 'SMW_CONJUNCTION_QUERY', 16 );
		// any disjunctions (OR, ||)
		define( 'SMW_DISJUNCTION_QUERY', 32 );
		// subsumes all other options
		define( 'SMW_ANY_QUERY', 0xFFFFFFFF );
		/**@}*/

		/**@{
		 * Constants for defining which concepts to show only if cached
		 */
		// show concept elements anywhere only if cached
		define( 'CONCEPT_CACHE_ALL', 4 );
		// show without cache if concept is not harder than permitted inline queries
		define( 'CONCEPT_CACHE_HARD', 1 );
		// show all concepts even without any cache
		define( 'CONCEPT_CACHE_NONE', 0 );
		/**@}*/

		/**@{
		 * Constants for identifying javascripts as used in SMWOutputs
		 */
		/// @deprecated Use module 'ext.smw.tooltips', see SMW_Ouptuts.php. Vanishes in SMW 1.7 at
		/// the latest.
		define( 'SMW_HEADER_TOOLTIP', 2 );
		/// @deprecated Module removed. Vanishes in SMW 1.7 at the latest.
		define( 'SMW_HEADER_SORTTABLE', 3 );
		/// @deprecated Use module 'ext.smw.style', see SMW_Ouptuts.php. Vanishes in SMW 1.7 at the
		/// latest.
		define( 'SMW_HEADER_STYLE', 4 );
		/**@}*/

		/**@{
		 *  Comparators for datavalues
		 */
		// Matches only datavalues that are equal to the given value.
		define( 'SMW_CMP_EQ', 1 );
		// Matches only datavalues that are less or equal than the given value.
		define( 'SMW_CMP_LEQ', 2 );
		// Matches only datavalues that are greater or equal to the given value.
		define( 'SMW_CMP_GEQ', 3 );
		// Matches only datavalues that are unequal to the given value.
		define( 'SMW_CMP_NEQ', 4 );
		// Matches only datavalues that are LIKE the given value.
		define( 'SMW_CMP_LIKE', 5 );
		// Matches only datavalues that are not LIKE the given value.
		define( 'SMW_CMP_NLKE', 6 );
		// Matches only datavalues that are less than the given value.
		define( 'SMW_CMP_LESS', 7 );
		// Matches only datavalues that are greater than the given value.
		define( 'SMW_CMP_GRTR', 8 );
		// Native LIKE matches (in disregards of an existing full-text index)
		define( 'SMW_CMP_PRIM_LIKE', 20 );
		// Native NLIKE matches (in disregards of an existing full-text index)
		define( 'SMW_CMP_PRIM_NLKE', 21 );
		// Short-cut for ~* ... *
		define( 'SMW_CMP_IN', 22 );
		// Short-cut for a phrase match ~" ... " mostly for a full-text context
		define( 'SMW_CMP_PHRASE', 23 );
		// Short-cut for ~! ... * ostly for a full-text context
		define( 'SMW_CMP_NOT', 24 );
		/**@}*/

		/**@{
		 * Constants for date formats (using binary encoding of nine bits:
		 * 3 positions x 3 interpretations)
		 */
		// Month-Day-Year
		define( 'SMW_MDY', 785 );
		// Day-Month-Year
		define( 'SMW_DMY', 673 );
		// Year-Month-Day
		define( 'SMW_YMD', 610 );
		// Year-Day-Month
		define( 'SMW_YDM', 596 );
		// Month-Year
		define( 'SMW_MY', 97 );
		// Year-Month
		define( 'SMW_YM', 76 );
		// Year
		define( 'SMW_Y', 9 );
		// an entered digit can be a year
		define( 'SMW_YEAR', 1 );
		// an entered digit can be a year
		define( 'SMW_DAY', 2 );
		// an entered digit can be a month
		define( 'SMW_MONTH', 4 );
		// an entered digit can be a day, month or year
		define( 'SMW_DAY_MONTH_YEAR', 7 );
		// an entered digit can be either a month or a year
		define( 'SMW_DAY_YEAR', 3 );
		/**@}*/

		/**@{
		 * Constants for date/time precision
		 */
		define( 'SMW_PREC_Y', 0 );
		define( 'SMW_PREC_YM', 1 );
		define( 'SMW_PREC_YMD', 2 );
		define( 'SMW_PREC_YMDT', 3 );
		// with time zone
		define( 'SMW_PREC_YMDTZ', 4 );
		/**@}*/

		/**@{
		 * Constants for SPARQL supported query features (mostly SPARQL 1.1) because we are unable
		 * to verify against the REST API whether a feature is supported or not
		 */
		// does not support any features
		define( 'SMW_SPARQL_QF_NONE', 0 );
		// support for inverse property paths to find redirects
		define( 'SMW_SPARQL_QF_REDI', 2 );
		// support for rdfs:subPropertyOf*
		define( 'SMW_SPARQL_QF_SUBP', 4 );
		// support for rdfs:subClassOf*
		define( 'SMW_SPARQL_QF_SUBC', 8 );
		// support for use of $smwgEntityCollation
		define( 'SMW_SPARQL_QF_COLLATION', 16 );
		// support case insensitive pattern matches
		define( 'SMW_SPARQL_QF_NOCASE', 32 );
		/**@}*/

		/**@{
		 * Constants for SPARQL repository sepcific features
		 */
		// does not support any features
		define( 'SMW_SPARQL_NONE', 0 );
		// ping connection before update
		define( 'SMW_SPARQL_CONNECTION_PING', 2 );
		/**@}*/

		/**@{
		 * Deprecated since 3.0, remove options after complete removal in 3.1
		 */
		define( 'SMW_HTTP_DEFERRED_ASYNC', true );
		define( 'SMW_HTTP_DEFERRED_SYNC_JOB', 4 );
		define( 'SMW_HTTP_DEFERRED_LAZY_JOB', 8 );
		/**@}*/

		/**@{
		 * Constants DV features
		 */
		define( 'SMW_DV_NONE', 0 );
		// PropertyValue to follow a property redirect target
		define( 'SMW_DV_PROV_REDI', 2 );
		// MonolingualTextValue requires language code
		define( 'SMW_DV_MLTV_LCODE', 4 );
		// Preserve spaces in unit labels
		define( 'SMW_DV_NUMV_USPACE', 8 );
		// Allows pattern
		define( 'SMW_DV_PVAP', 16 );
		// WikiPageValue to use an explicit display title
		define( 'SMW_DV_WPV_DTITLE', 32 );
		// PropertyValue allow to find a property using the display title
		define( 'SMW_DV_PROV_DTITLE', 64 );
		// Declares a uniqueness constraint
		define( 'SMW_DV_PVUC', 128 );
		// TimeValue to indicate calendar model
		define( 'SMW_DV_TIMEV_CM', 256 );
		// Preferred property label
		define( 'SMW_DV_PPLB', 512 );
		// PropertyValue to output a hint in case of a preferred label usage
		define( 'SMW_DV_PROV_LHNT', 1024 );
		// Have WikiPageValue use a full pipe trick when rendering its caption.
		define( 'SMW_DV_WPV_PIPETRICK', 2048 );
		/**@}*/

		/**@{
		 * Constants for Fulltext types
		 */
		define( 'SMW_FT_NONE', 0 );
		// DataItem::TYPE_BLOB
		define( 'SMW_FT_BLOB', 2 );
		// DataItem::TYPE_URI
		define( 'SMW_FT_URI', 4 );
		// DataItem::TYPE_WIKIPAGE
		define( 'SMW_FT_WIKIPAGE', 8 );
		/**@}*/

		/**@{
		 * Constants for admin features
		 */
		define( 'SMW_ADM_NONE', 0 );
		// RefreshStore
		define( 'SMW_ADM_REFRESH', 2 );
		// IDDisposal
		define( 'SMW_ADM_DISPOSAL', 4 );
		// SetupStore
		define( 'SMW_ADM_SETUP', 8 );
		// Property statistics update
		define( 'SMW_ADM_PSTATS', 16 );
		// Fulltext update
		define( 'SMW_ADM_FULLT', 32 );
		// Maintenance script docs
		define( 'SMW_ADM_MAINTENANCE_SCRIPT_DOCS', 64 );
		// Show Overview tab
		define( 'SMW_ADM_SHOW_OVERVIEW', 128 );
		// Table optimization alert
		define( 'SMW_ADM_ALERT_LAST_OPTIMIZATION_RUN', 2048 );
		/**@}*/

		/**@{
		 * Constants for ResultPrinter
		 */
		define( 'SMW_RF_NONE', 0 );
		// #2022 Enable 2.5 behaviour for template handling
		define( 'SMW_RF_TEMPLATE_OUTSEP', 2 );
		/**@}*/

		/**@{
		 * Constants for $smwgExperimentalFeatures
		 */
		define( 'SMW_QUERYRESULT_PREFETCH', 2 );
		define( 'SMW_SHOWPARSER_USE_CURTAILMENT', 4 );
		/**@}*/

		/**@{
		 * Constants for $smwgFieldTypeFeatures
		 */
		define( 'SMW_FIELDT_NONE', 0 );
		// Using FieldType::TYPE_CHAR_NOCASE
		define( 'SMW_FIELDT_CHAR_NOCASE', 2 );
		// Using FieldType::TYPE_CHAR_LONG
		define( 'SMW_FIELDT_CHAR_LONG', 4 );
		/**@}*/

		/**@{
		 * Constants for $smwgQueryProfiler
		 */
		define( 'SMW_QPRFL_NONE', 0 );
		// Support for Query parameters
		define( 'SMW_QPRFL_PARAMS', 2 );
		// Support for Query duration
		define( 'SMW_QPRFL_DUR', 4 );
		/**@}*/

		/**@{
		 * Constants for $smwgBrowseFeatures
		 */
		define( 'SMW_BROWSE_NONE', 0 );
		// Support for the toolbox link
		define( 'SMW_BROWSE_TLINK', 2 );
		// Support inverse direction
		define( 'SMW_BROWSE_SHOW_INVERSE', 4 );
		// Support for incoming links
		define( 'SMW_BROWSE_SHOW_INCOMING', 8 );
		// Support for grouping properties
		define( 'SMW_BROWSE_SHOW_GROUP', 16 );
		// Support for the sortkey display
		define( 'SMW_BROWSE_SHOW_SORTKEY', 32 );
		// Support for using the API as request backend
		define( 'SMW_BROWSE_USE_API', 64 );
		/**@}*/

		/**@{
		 * Constants for $smwgParserFeatures
		 */
		define( 'SMW_PARSER_NONE', 0 );
		// Support for strict mode
		define( 'SMW_PARSER_STRICT', 2 );
		// Support for using the StripMarkerDecoder
		define( 'SMW_PARSER_UNSTRIP', 4 );
		// Support for display of inline errors
		define( 'SMW_PARSER_INL_ERROR', 8 );
		// Support for parsing hidden categories
		define( 'SMW_PARSER_HID_CATS', 16 );
		// Support for links in value
		define( 'SMW_PARSER_LINV', 32 );
		// Support for links in value
		define( 'SMW_PARSER_LINKS_IN_VALUES', 32 );
		/**@}*/

		/**@{
		 * Constants for LinksInValue features
		 */
		// Using the PCRE approach
		define( 'SMW_LINV_PCRE', 2 );
		// Using the Obfuscator approach
		define( 'SMW_LINV_OBFU', 4 );
		/**@}*/

		/**@{
		 * Constants for $smwgCategoryFeatures
		 */
		define( 'SMW_CAT_NONE', 0 );
		// Support resolving category redirects
		define( 'SMW_CAT_REDIRECT', 2 );
		// Support using a category as instantiatable object
		define( 'SMW_CAT_INSTANCE', 4 );
		// Support for category hierarchies
		define( 'SMW_CAT_HIERARCHY', 8 );
		/**@}*/

		/**@{
		 * Constants for $smwgQSortFeatures
		 */
		define( 'SMW_QSORT_NONE', 0 );
		// General sort support
		define( 'SMW_QSORT', 2 );
		// Random sort support
		define( 'SMW_QSORT_RANDOM', 4 );
		// Unconditional sort support
		define( 'SMW_QSORT_UNCONDITIONAL', 8 );
		/**@}*/

		/**@{
		 * Constants for $smwgRemoteReqFeatures
		 */
		// Remote responses are enabled
		define( 'SMW_REMOTE_REQ_SEND_RESPONSE', 2 );
		// Shows a note
		define( 'SMW_REMOTE_REQ_SHOW_NOTE', 4 );
		/**@}*/

		/**@{
		 * Constants for Schema groups
		 */
		define( 'SMW_SCHEMA_GROUP_FORMAT', 'schema/group/format' );
		define( 'SMW_SCHEMA_GROUP_SEARCH', 'schema/group/search' );
		define( 'SMW_SCHEMA_GROUP_PROPERTY', 'schema/group/property' );
		define( 'SMW_SCHEMA_GROUP_CONSTRAINT', 'schema/group/constraint' );
		define( 'SMW_SCHEMA_GROUP_PROFILE', 'schema/group/profile' );

		/**@{
		 * Constants for Special:Ask submit method
		 */
		define( 'SMW_SASK_SUBMIT_GET', 'get' );
		define( 'SMW_SASK_SUBMIT_GET_REDIRECT', 'get.redirect' );
		define( 'SMW_SASK_SUBMIT_POST', 'post' );
		/**@}*/

		/**@{
		 * Constants for constraint error check
		 */
		define( 'SMW_CONSTRAINT_ERR_CHECK_NONE', false );
		define( 'SMW_CONSTRAINT_ERR_CHECK_MAIN', 'check/main' );
		define( 'SMW_CONSTRAINT_ERR_CHECK_ALL', 'check/all' );
		/**@}*/

		/**@{
		 * Constants for content types
		 */
		define( 'CONTENT_MODEL_SMW_SCHEMA', 'smw/schema' );
		/**@}*/
	}

	/**
	 * Get the array that DefaultSettings.php is supposed to return.  We did not put it inline here
	 * as we did with Aliases.php and Defines.php because there are references to that file online
	 * for documentation.
	 */
	public static function getDefaultSettings(): array {
		static $settings = null;
		if ( $settings === null ) {
			$settings = include dirname( __DIR__ ) . '/includes/DefaultSettings.php';
			if ( !is_array( $settings ) ) {
				throw new Exception( "Including DefaultSettings.php did not return an array." );
			}
		}
		return $settings;
	}

	/**
	 * Set up $GLOBALS according to what is found in DefaultSettings.php
	 */
	public static function setupGlobals(): void {
		$defaultSettings = self::getDefaultSettings();
		if ( is_array( $defaultSettings ) ) {
			foreach ( $defaultSettings as $key => $value ) {
				if ( !isset( $GLOBALS[$key] ) ) {
					$GLOBALS[$key] = $value;
				}
			}
		}
	}
}

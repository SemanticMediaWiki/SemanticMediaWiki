<?php

namespace SMW\Test;

use SMWDataItem;
use SMWPrintRequest;

/**
 * @codeCoverageIgnore
 *
 * MockObject repository holds specifications on mock objects
 *
 * @ingroup Test
 *
 * @group SMW
 * @group SMWExtension
 *
 * @licence GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class MockObjectRepository extends \PHPUnit_Framework_TestCase {

	/** @var MockObjectBuilder */
	protected $builder;

	/**
	 * @since 1.9
	 *
	 * @param MockObjectBuilder $builder
	 */
	public function __construct( MockObjectBuilder $builder ) {
		$this->builder = $builder;
	}

	/**
	 * Returns a SemanticData object
	 *
	 * @since 1.9
	 *
	 * @return SemanticData
	 */
	public function SemanticData() {

		$semanticData = $this->getMockBuilder( 'SMW\SemanticData' )
			->disableOriginalConstructor()
			->getMock();

		foreach ( $this->builder->getInvokedMethods() as $method ) {

			$semanticData->expects( $this->any() )
				->method( $method )
				->will( $this->builder->setCallback( $method ) );

		}

		return $semanticData;
	}

	/**
	 * Helper method that returns a CacheableResultCollector object
	 *
	 * @since 1.9
	 *
	 * @return CacheableResultCollector
	 */
	public function CacheableResultCollector() {

		// CacheableResultCollector is an abstract class therefore necessary methods
		// are declared by default while other methods are only mocked if needed
		// because setMethods overrides the original signature
		$methods = array( 'cacheSetup', 'runCollector' );

		if ( $this->builder->hasValue( 'getResults' ) ) {
			$methods[] = 'getResults';
		}

		$collector = $this->getMockBuilder( '\SMW\Store\CacheableResultCollector' )
			->setMethods( $methods )
			->getMock();

		$collector->expects( $this->any() )
			->method( 'runCollector' )
			->will( $this->returnValue( $this->builder->setValue( 'runCollector' ) ) );

		$collector->expects( $this->any() )
			->method( 'cacheSetup' )
			->will( $this->returnValue( $this->builder->setValue( 'cacheSetup' ) ) );

		$collector->expects( $this->any() )
			->method( 'getResults' )
			->will( $this->returnValue( $this->builder->setValue( 'getResults' ) ) );

		return $collector;
	}

	/**
	 * Returns an Observer object
	 *
	 * @since 1.9
	 *
	 * @return Observer
	 */
	public function FakeObserver() {

		// Observer is an obstract class therefore create a FakeObserver
		// that can include different methods from different observers

		$methods = $this->builder->getInvokedMethods();

		$observer = $this->getMockBuilder( 'SMW\BaseObserver' )
			->setMethods( $methods )
			->getMock();

		foreach ( $methods as $method ) {

			$observer->expects( $this->any() )
				->method( $method )
				->will( $this->builder->setCallback( $method ) );

		}

		return $observer;
	}

	/**
	 * Returns an ObservableSubject object
	 *
	 * @since 1.9
	 *
	 * @return ObservableSubject
	 */
	public function FakeObservableSubject() {

		$methods = $this->builder->getInvokedMethods();

		$observer = $this->getMockBuilder( 'SMW\ObservableSubject' )
			->setMethods( $methods )
			->getMock();

		foreach ( $methods as $method ) {

			$observer->expects( $this->any() )
				->method( $method )
				->will( $this->builder->setCallback( $method ) );

		}

		return $observer;
	}

	/**
	 * Returns an DependencyObject object
	 *
	 * @since 1.9
	 *
	 * @return DependencyObject
	 */
	public function DependencyObject() {

		$methods = $this->builder->getInvokedMethods();

		$dependencyObject = $this->getMockBuilder( 'SMW\DependencyObject' )
			->setMethods( $methods )
			->getMock();

		foreach ( $methods as $method ) {

			$dependencyObject->expects( $this->any() )
				->method( $method )
				->will( $this->builder->setCallback( $method ) );

		}

		return $dependencyObject;
	}

	/**
	 * Returns an Observer object
	 *
	 * @since 1.9
	 *
	 * @return Observer
	 */
	public function FakeDependencyContainer() {

		$methods = $this->builder->getInvokedMethods();

		$dependencyObject = $this->getMockBuilder( 'SMW\NullDependencyContainer' )
			->setMethods( $methods )
			->getMock();

		foreach ( $methods as $method ) {

			$dependencyObject->expects( $this->any() )
				->method( $method )
				->will( $this->builder->setCallback( $method ) );

		}

		return $dependencyObject;
	}

	/**
	 * Returns a ParserData object
	 *
	 * @since 1.9
	 *
	 * @return ParserData
	 */
	public function ParserData() {

		$methods = $this->builder->getInvokedMethods();

		$parserData = $this->getMockBuilder( 'SMW\ParserData' )
			->disableOriginalConstructor()
			->getMock();

		foreach ( $methods as $method ) {

			$parserData->expects( $this->any() )
				->method( $method )
				->will( $this->builder->setCallback( $method ) );

		}

		return $parserData;
	}

	/**
	 * Returns a Factbox object
	 *
	 * @since 1.9
	 *
	 * @return Factbox
	 */
	public function Factbox() {

		$factbox = $this->getMockBuilder( '\SMW\Factbox' )
			->disableOriginalConstructor()
			->getMock();

		$factbox->expects( $this->any() )
			->method( 'isVisible' )
			->will( $this->returnValue( $this->builder->setValue( 'isVisible' ) ) );

		$factbox->expects( $this->any() )
			->method( 'getContent' )
			->will( $this->returnValue( $this->builder->setValue( 'getContent' ) ) );

		return $factbox;
	}

	/**
	 * Returns a SMWQuery object
	 *
	 * @since 1.9
	 *
	 * @return SMWQuery
	 */
	public function Query() {

		$query = $this->getMockBuilder( 'SMWQuery' )
			->disableOriginalConstructor()
			->getMock();

		return $query;
	}

	/**
	 * @since 1.9
	 *
	 * @return SMWDescription
	 */
	public function QueryDescription() {

		$requiredAbstractMethods = array(
			'getQueryString',
			'isSingleton'
		);

		$methods = array_unique( array_merge( $requiredAbstractMethods, $this->builder->getInvokedMethods() ) );

		$queryDescription = $this->getMockBuilder( '\SMWDescription' )
			->setMethods( $methods )
			->getMock();

		foreach ( $methods as $method ) {

			$queryDescription->expects( $this->any() )
				->method( $method )
				->will( $this->builder->setCallback( $method ) );

		}

		return $queryDescription;
	}

	/**
	 * Returns a User object
	 *
	 * @since 1.9
	 *
	 * @return User
	 */
	public function User() {

		$user = $this->getMockBuilder( 'User' )
			->disableOriginalConstructor()
			->getMock();

		$user->expects( $this->any() )
			->method( 'getUserPage' )
			->will( $this->returnValue( $this->builder->setValue( 'getUserPage' ) ) );

		return $user;
	}

	/**
	 * Returns a ParserOptions object
	 *
	 * @since 1.9
	 *
	 * @return ParserOptions
	 */
	public function ParserOptions() {

		$parserOptions = $this->getMockBuilder( 'ParserOptions' )
			->disableOriginalConstructor()
			->getMock();

		$parserOptions->expects( $this->any() )
			->method( 'getTargetLanguage' )
			->will( $this->returnValue( $this->builder->setValue( 'getTargetLanguage' ) ) );

		return $parserOptions;
	}

	/**
	 * Returns a ContentParser object
	 *
	 * @since 1.9
	 *
	 * @return ContentParser
	 */
	public function ContentParser() {

		$contentParser = $this->getMockBuilder( '\SMW\ContentParser' )
			->disableOriginalConstructor()
			->getMock();

		$contentParser->expects( $this->any() )
			->method( 'getOutput' )
			->will( $this->returnValue( $this->builder->setValue( 'getOutput', $this->builder->newObject( 'ParserOutput' ) ) ) );

		$contentParser->expects( $this->any() )
			->method( 'getErrors' )
			->will( $this->returnValue( $this->builder->setValue( 'getErrors', array() ) ) );

		return $contentParser;
	}

	/**
	 * Returns a ParserOutput object
	 *
	 * @since 1.9
	 *
	 * @return ParserOutput
	 */
	public function ParserOutput() {

		$parserOutput = $this->getMockBuilder( 'ParserOutput' )
			->disableOriginalConstructor()
			->getMock();


		foreach ( $this->builder->getInvokedMethods() as $method ) {

			$parserOutput->expects( $this->any() )
				->method( $method )
				->will( $this->builder->setCallback( $method ) );

		}

		return $parserOutput;
	}

	/**
	 * Returns a WikiPage object
	 *
	 * @since 1.9
	 *
	 * @return WikiPage
	 */
	public function WikiPage() {

		$wikiPage = $this->getMockBuilder( 'WikiPage' )
			->disableOriginalConstructor()
			->getMock();

		foreach ( $this->builder->getInvokedMethods() as $method ) {

			$wikiPage->expects( $this->any() )
				->method( $method )
				->will( $this->builder->setCallback( $method ) );

		}

		return $wikiPage;
	}

	/**
	 * Returns a Revision object
	 *
	 * @since 1.9
	 *
	 * @return Revision
	 */
	public function Revision() {

		$revision = $this->getMockBuilder( 'Revision' )
			->disableOriginalConstructor()
			->getMock();

		foreach ( $this->builder->getInvokedMethods() as $method ) {

			$revision->expects( $this->any() )
				->method( $method )
				->will( $this->builder->setCallback( $method ) );

		}

		return $revision;
	}

	/**
	 * Returns a DataValue object
	 *
	 * @since 1.9
	 *
	 * @return DataValue
	 * @throws OutOfBoundsException
	 */
	public function DataValue() {

		if ( !$this->builder->hasValue( 'DataValueType' ) ) {
			throw new OutOfBoundsException( 'DataValueType is missing' );
		}

		$dataValue = $this->getMockBuilder( $this->builder->setValue( 'DataValueType' ) )
			->disableOriginalConstructor()
			->getMock();

		foreach ( $this->builder->getInvokedMethods() as $method ) {

			$dataValue->expects( $this->any() )
				->method( $method )
				->will( $this->builder->setCallback( $method ) );

		}

		return $dataValue;
	}

	/**
	 * Returns a SMWQueryResult object
	 *
	 * @since 1.9
	 *
	 * @return SMWQueryResult
	 */
	public function QueryResult() {

		$queryResult = $this->getMockBuilder( 'SMWQueryResult' )
			->disableOriginalConstructor()
			->getMock();

		$queryResult->expects( $this->any() )
			->method( 'getErrors' )
			->will( $this->returnValue( $this->builder->setValue( 'getErrors', array() ) ) );

		// Word of caution, onConsecutiveCalls is used in order to ensure
		// that a while() loop is not trapped in an infinite loop and returns
		// a false at the end
		$queryResult->expects( $this->any() )
			->method( 'getNext' )
			->will( $this->onConsecutiveCalls( $this->builder->setValue( 'getNext' ) , false ) );

		foreach ( $this->builder->getInvokedMethods() as $method ) {

			$queryResult->expects( $this->any() )
				->method( $method )
				->will( $this->builder->setCallback( $method ) );

		}

		return $queryResult;
	}

	/**
	 * Helper method that returns a DIWikiPage object
	 *
	 * @since 1.9
	 *
	 * @return DIWikiPage
	 */
	public function DIWikiPage() {

		$diWikiPage = $this->getMockBuilder( '\SMW\DIWikiPage' )
			->disableOriginalConstructor()
			->getMock();

		$diWikiPage->expects( $this->any() )
			->method( 'getTitle' )
			->will( $this->returnValue( $this->builder->setValue( 'getTitle' ) ) );

		$diWikiPage->expects( $this->any() )
			->method( 'getDBkey' )
			->will( $this->returnValue( $this->builder->setValue( 'getDBkey', $this->builder->newRandomString( 10, 'DIWikiPage-auto-dbkey' ) ) ) );

		$diWikiPage->expects( $this->any() )
			->method( 'getPrefixedText' )
			->will( $this->returnValue( $this->builder->setValue( 'getPrefixedText', $this->builder->newRandomString( 10, 'DIWikiPage-auto-prefixedText' ) ) ) );

		$diWikiPage->expects( $this->any() )
			->method( 'getDIType' )
			->will( $this->returnValue( SMWDataItem::TYPE_WIKIPAGE ) );

		$diWikiPage->expects( $this->any() )
			->method( 'findPropertyTypeID' )
			->will( $this->returnValue( $this->builder->setValue( 'findPropertyTypeID', '_wpg' ) ) );

		$diWikiPage->expects( $this->any() )
			->method( 'getSubobjectName' )
			->will( $this->returnValue( $this->builder->setValue( 'getSubobjectName', '' ) ) );

		return $diWikiPage;
	}

	/**
	 * Returns a DIProperty object
	 *
	 * @since 1.9
	 *
	 * @return DIProperty
	 */
	public function DIProperty() {

		$property = $this->getMockBuilder( '\SMW\DIProperty' )
			->disableOriginalConstructor()
			->getMock();

		$property->expects( $this->any() )
			->method( 'findPropertyTypeID' )
			->will( $this->returnValue( $this->builder->setValue( 'findPropertyTypeID', '_wpg' ) ) );

		$property->expects( $this->any() )
			->method( 'getKey' )
			->will( $this->returnValue( $this->builder->setValue( 'getKey', '_wpg' ) ) );

		$property->expects( $this->any() )
			->method( 'getDIType' )
			->will( $this->returnValue( SMWDataItem::TYPE_PROPERTY ) );

		foreach ( $this->builder->getInvokedMethods() as $method ) {

			$property->expects( $this->any() )
				->method( $method )
				->will( $this->builder->setCallback( $method ) );

		}

		return $property;
	}

	/**
	 * Returns a Store object
	 *
	 * @note MockStore is based on the abstract Store class which avoids
	 * dependency on a specific Store implementation (SQLStore etc.), the mock
	 * object will allow to override necessary methods
	 *
	 * @since 1.9
	 *
	 * @return Store
	 */
	public function Store() {

		// SMW\Store is an abstract class, use setMethods to implement
		// required abstract methods
		$requiredAbstractMethods = array(
			'setup',
			'drop',
			'getStatisticsTable',
			'getObjectIds',
			'refreshData',
			'getStatistics',
			'getQueryResult',
			'getPropertiesSpecial',
			'getUnusedPropertiesSpecial',
			'getWantedPropertiesSpecial',
			'getPropertyTables',
			'deleteSubject',
			'doDataUpdate',
			'changeTitle',
			'getProperties',
			'getInProperties',
			'getAllPropertySubjects',
			'getSQLConditions',
			'getSemanticData',
			'getPropertyValues',
			'getPropertySubjects',
			'refreshConceptCache',
			'deleteConceptCache',
			'getConceptCacheStatus',
			'clearData',
			'updateData'
		);

		$methods = array_unique( array_merge( $requiredAbstractMethods, $this->builder->getInvokedMethods() ) );

		$idTable = $this->getMock( 'stdClass', array( 'getIdTable') );

		$idTable->expects( $this->any() )
			->method( 'getIdTable' )
			->will( $this->returnValue( 'smw_id_table_test' ) );

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->setMethods( $methods )
			->getMock();

		$store->expects( $this->any() )
			->method( 'getObjectIds' )
			->will( $this->returnValue( $idTable ) );

		$store->expects( $this->any() )
			->method( 'getStatisticsTable' )
			->will( $this->returnValue( 'smw_statistics_table_test' ) );

		foreach ( $this->builder->getInvokedMethods() as $method ) {

			$store->expects( $this->any() )
				->method( $method )
				->will( $this->builder->setCallback( $method ) );

		}

		return $store;
	}

	/**
	 * Returns a TableDefinition object
	 *
	 * @since 1.9
	 *
	 * @return TableDefinition
	 */
	public function SQLStoreTableDefinition() {

		$tableDefinition = $this->getMockBuilder( 'SMW\SQLStore\TableDefinition' )
			->disableOriginalConstructor()
			->getMock();

		foreach ( $this->builder->getInvokedMethods() as $method ) {

			$tableDefinition->expects( $this->any() )
				->method( $method )
				->will( $this->builder->setCallback( $method ) );

		}

		return $tableDefinition;
	}

	/**
	 * Returns a SMWDIError object
	 *
	 * @since 1.9
	 *
	 * @return SMWDIError
	 */
	public function DIError() {

		$errors = $this->getMockBuilder( 'SMWDIError' )
			->disableOriginalConstructor()
			->getMock();

		$errors->expects( $this->any() )
			->method( 'getErrors' )
			->will( $this->returnValue( $this->builder->setValue( 'getErrors' ) ) );

		return $errors;
	}

	/**
	 * Returns a Title mock object
	 *
	 * @note This mock object avoids the involvement of LinksUpdate (which
	 * requires DB access) and returns a randomized LatestRevID/ArticleID
	 *
	 * @since 1.9
	 *
	 * @return Title
	 */
	public function Title() {

		// When interacting with a "real" Parser object, the Parser expects in
		// in 1.21+ a content model to be present while in MW 1.19/1.20 such
		// object is not required. In order to avoid operational obstruction a
		// model is set as default and can if necessary individually be overridden
		$contentModel = class_exists( 'ContentHandler') ? CONTENT_MODEL_WIKITEXT : null;

		$title = $this->getMockBuilder( 'Title' )
			->disableOriginalConstructor()
			->getMock();

		$title->expects( $this->any() )
			->method( 'getDBkey' )
			->will( $this->returnValue( $this->builder->setValue( 'getDBkey', $this->builder->newRandomString( 10, 'Title-auto-dbkey' ) ) ) );

		$title->expects( $this->any() )
			->method( 'getInterwiki' )
			->will( $this->returnValue( $this->builder->setValue( 'getInterwiki', '' ) ) );

		$title->expects( $this->any() )
			->method( 'getArticleID' )
			->will( $this->returnValue( $this->builder->setValue( 'getArticleID', rand( 10, 10000 ) ) ) );

		$title->expects( $this->any() )
			->method( 'getNamespace' )
			->will( $this->returnValue( $this->builder->setValue( 'getNamespace', NS_MAIN ) ) );

		$title->expects( $this->any() )
			->method( 'isKnown' )
			->will( $this->returnValue( $this->builder->setValue( 'exists' ) ) );

		$title->expects( $this->any() )
			->method( 'exists' )
			->will( $this->returnValue( $this->builder->setValue( 'exists' ) ) );

		$title->expects( $this->any() )
			->method( 'getLatestRevID' )
			->will( $this->returnValue( $this->builder->setValue( 'getLatestRevID', rand( 10, 5000 ) ) ) );

		$title->expects( $this->any() )
			->method( 'getFirstRevision' )
			->will( $this->returnValue( $this->builder->setValue( 'getFirstRevision' ) ) );

		$title->expects( $this->any() )
			->method( 'getText' )
			->will( $this->returnValue( $this->builder->setValue( 'getText' ) ) );

		$title->expects( $this->any() )
			->method( 'getPrefixedText' )
			->will( $this->returnValue( $this->builder->setValue( 'getPrefixedText', $this->builder->newRandomString( 10, 'Title-auto-prefixedtext' ) ) ) );

		$title->expects( $this->any() )
			->method( 'isSpecialPage' )
			->will( $this->returnValue( $this->builder->setValue( 'isSpecialPage', false ) ) );

		$title->expects( $this->any() )
			->method( 'getContentModel' )
			->will( $this->returnValue( $this->builder->setValue( 'getContentModel', $contentModel ) ) );

		$title->expects( $this->any() )
			->method( 'getPageLanguage' )
			->will( $this->returnValue( $this->builder->setValue( 'getPageLanguage' ) ) );

		$title->expects( $this->any() )
			->method( 'isRedirect' )
			->will( $this->returnValue( $this->builder->setValue( 'isRedirect', false ) ) );

		$title->expects( $this->any() )
			->method( 'inNamespace' )
			->will( $this->builder->setCallback( 'inNamespace' ) );

		return $title;
	}

	/**
	 * Helper method that returns a Skin object
	 *
	 * @since 1.9
	 *
	 * @return Skin
	 */
	public function Skin() {

		$skin = $this->getMockBuilder( 'Skin' )
			->disableOriginalConstructor()
			->getMock();

		foreach ( $this->builder->getInvokedMethods() as $method ) {

			$skin->expects( $this->any() )
				->method( $method )
				->will( $this->builder->setCallback( $method ) );

		}

		return $skin;
	}

	/**
	 * Helper method that returns a SkinTemplate object
	 *
	 * @since 1.9
	 *
	 * @return SkinTemplate
	 */
	public function SkinTemplate() {

		$skinTemplate = $this->getMockBuilder( 'SkinTemplate' )
			->disableOriginalConstructor()
			->getMock();

		$skinTemplate->expects( $this->any() )
			->method( 'getSkin' )
			->will( $this->returnValue( $this->builder->setValue( 'getSkin' ) ) );

		return $skinTemplate;
	}

	/**
	 * Helper method that returns a Parser object
	 *
	 * @since 1.9
	 *
	 * @return Parser
	 */
	public function Parser() {

		$parser = $this->getMockBuilder( 'Parser' )
			->disableOriginalConstructor()
			->getMock();

		foreach ( $this->builder->getInvokedMethods() as $method ) {

			$parser->expects( $this->any() )
				->method( $method )
				->will( $this->builder->setCallback( $method ) );

		}

		return $parser;
	}

	/**
	 * Helper method that returns a LinksUpdate object
	 *
	 * @since 1.9
	 *
	 * @return LinksUpdate
	 */
	public function LinksUpdate() {

		$linksUpdate = $this->getMockBuilder( 'LinksUpdate' )
			->disableOriginalConstructor()
			->getMock();

		foreach ( $this->builder->getInvokedMethods() as $method ) {

			$linksUpdate->expects( $this->any() )
				->method( $method )
				->will( $this->builder->setCallback( $method ) );

		}

		return $linksUpdate;
	}

	/**
	 * Helper method that returns a OutputPage object
	 *
	 * @since 1.9
	 *
	 * @return OutputPage
	 */
	public function OutputPage() {

		$outputPage = $this->getMockBuilder( 'OutputPage' )
		->disableOriginalConstructor()
		->getMock();

		$outputPage->expects( $this->any() )
			->method( 'getTitle' )
			->will( $this->returnValue( $this->builder->setValue( 'getTitle' ) ) );

		$outputPage->expects( $this->any() )
			->method( 'getContext' )
			->will( $this->returnValue( $this->builder->setValue( 'getContext' ) ) );

		$outputPage->expects( $this->any() )
			->method( 'addModules' )
			->will( $this->returnValue( $this->builder->setValue( 'addModules' ) ) );

		$outputPage->expects( $this->any() )
			->method( 'addLink' )
			->will( $this->returnValue( $this->builder->setValue( 'addLink' ) ) );

		// getHeadLinksArray doesn't exist in MW 1.19
		$outputPage->expects( $this->any() )
			->method( 'getHeadLinksArray' )
			->will( $this->builder->setCallback( 'getHeadLinksArray' ) );

		return $outputPage;
	}

	/**
	 * Helper method that returns a DatabaseBase object
	 *
	 * @since 1.9
	 *
	 * @return DatabaseBase
	 */
	public function DatabaseBase() {

		// DatabaseBase is an abstract class, use setMethods to implement
		// required abstract methods
		$requiredAbstractMethods = array(
			'selectField',
			'doQuery',
			'getType',
			'open',
			'fetchObject',
			'fetchRow',
			'numRows',
			'numFields',
			'fieldName',
			'insertId',
			'dataSeek',
			'lastErrno',
			'lastError',
			'fieldInfo',
			'indexInfo',
			'affectedRows',
			'strencode',
			'getSoftwareLink',
			'getServerVersion',
			'closeConnection'
		);

		$methods = array_unique( array_merge( $requiredAbstractMethods, $this->builder->getInvokedMethods() ) );

		$databaseBase = $this->getMockBuilder( 'DatabaseBase' )
			->disableOriginalConstructor()
			->setMethods( $methods )
			->getMock();

		foreach ( $this->builder->getInvokedMethods() as $method ) {

			$databaseBase->expects( $this->any() )
				->method( $method )
				->will( $this->builder->setCallback( $method ) );

		}

		return $databaseBase;
	}

	/**
	 * Returns a TitleAccess object
	 *
	 * @since 1.9
	 *
	 * @return TitleAccess
	 */
	public function TitleAccess() {

		$titleAccess = $this->getMockForAbstractClass( '\SMW\TitleAccess' );

		$titleAccess->expects( $this->any() )
			->method( 'getTitle' )
			->will( $this->returnValue( $this->builder->setValue( 'getTitle' ) ) );

		return $titleAccess;
	}

	/**
	 * Returns a Content object
	 *
	 * @since 1.9
	 *
	 * @return Content
	 */
	public function Content() {

		$content = $this->getMockBuilder( 'Content' )
			->disableOriginalConstructor()
			->getMock();

		$content->expects( $this->any() )
			->method( 'getParserOutput' )
			->will( $this->returnValue( $this->builder->setValue( 'getParserOutput' ) ) );

		return $content;
	}

	/**
	 * Returns a SMWDataItem object
	 *
	 * @since 1.9
	 *
	 * @return SMWDataItem
	 */
	public function DataItem() {

		$dataItem = $this->getMockBuilder( 'SMWDataItem' )
			->disableOriginalConstructor()
			->setMethods( array( 'getNumber', 'getDIType', 'getSortKey', 'equals', 'getSerialization' ) )
			->getMock();

		$dataItem->expects( $this->any() )
			->method( 'getDIType' )
			->will( $this->returnValue( $this->builder->setValue( 'getDIType' ) ) );

		$dataItem->expects( $this->any() )
			->method( 'getSortKey' )
			->will( $this->returnValue( $this->builder->setValue( 'getSortKey' ) ) );

		$dataItem->expects( $this->any() )
			->method( 'getNumber' )
			->will( $this->returnValue( $this->builder->setValue( 'getNumber' ) ) );

		return $dataItem;
	}

	/**
	 * Helper method that returns a SMWPrintRequest object
	 *
	 * @since 1.9
	 *
	 * @return SMWPrintRequest
	 */
	public function PrintRequest() {

		$printRequest = $this->getMockBuilder( 'SMWPrintRequest' )
			->disableOriginalConstructor()
			->getMock();

		$printRequest->expects( $this->any() )
			->method( 'getText' )
			->will( $this->returnValue( $this->builder->setValue( 'getText', $this->builder->newRandomString( 10, 'Auto-printRequest' ) ) ) );

		$printRequest->expects( $this->any() )
			->method( 'getLabel' )
			->will( $this->returnValue( $this->builder->setValue( 'getLabel' ) ) );

		$printRequest->expects( $this->any() )
			->method( 'getMode' )
			->will( $this->returnValue( $this->builder->setValue( 'getMode', SMWPrintRequest::PRINT_THIS ) ) );

		$printRequest->expects( $this->any() )
			->method( 'getTypeID' )
			->will( $this->returnValue( $this->builder->setValue( 'getTypeID' ) ) );

		$printRequest->expects( $this->any() )
			->method( 'getOutputFormat' )
			->will( $this->returnValue( $this->builder->setValue( 'getOutputFormat' ) ) );

		$printRequest->expects( $this->any() )
			->method( 'getParameter' )
			->will( $this->returnValue( $this->builder->setValue( 'getParameter', 'center' ) ) );

		return $printRequest;
	}

	/**
	 * Helper method that returns a SMWResultArray object
	 *
	 * @since 1.9
	 *
	 * @return SMWResultArray
	 */
	public function ResultArray() {

		$resultArray = $this->getMockBuilder( 'SMWResultArray' )
			->disableOriginalConstructor()
			->getMock();

		$resultArray->expects( $this->any() )
			->method( 'getText' )
			->will( $this->returnValue( $this->builder->setValue( 'getText' ) ) );

		$resultArray->expects( $this->any() )
			->method( 'getPrintRequest' )
			->will( $this->returnValue( $this->builder->setValue( 'getPrintRequest' ) ) );

		$resultArray->expects( $this->any() )
			->method( 'getContent' )
			->will( $this->returnValue( $this->builder->setValue( 'getContent' ) ) );

		$resultArray->expects( $this->any() )
			->method( 'getNextDataValue' )
			->will( $this->onConsecutiveCalls( $this->builder->setValue( 'getNextDataValue' ), false ) );

		$resultArray->expects( $this->any() )
			->method( 'getNextDataItem' )
			->will( $this->onConsecutiveCalls( $this->builder->setValue( 'getNextDataItem' ), false ) );

		return $resultArray;
	}

	/**
	 * Helper method that returns a RequestContext object
	 *
	 * @since 1.9
	 *
	 * @return RequestContext
	 */
	public function RequestContext() {

		$requestContext = $this->getMockForAbstractClass( 'RequestContext' );

		return $requestContext;
	}

	/**
	 * Returns a Language object
	 *
	 * @since 1.9
	 *
	 * @return Language
	 */
	public function Language() {

		$language = $this->getMockBuilder( 'Language' )
			->disableOriginalConstructor()
			->getMock();

		return $language;
	}

}

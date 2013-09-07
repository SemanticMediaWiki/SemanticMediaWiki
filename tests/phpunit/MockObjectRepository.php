<?php

namespace SMW\Test;

use SMWDataItem;

/**
 * MockObject repository
 *
 * @file
 *
 * @license GNU GPL v2+
 * @since   1.9
 *
 * @author mwjames
 */

/**
 * MockObject repository holds specifications on mock objects
 *
 * @ingroup Test
 *
 * @group SMW
 * @group SMWExtension
 *
 * @codeCoverageIgnore
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

		$semanticData->expects( $this->any() )
			->method( 'getSubject' )
			->will( $this->returnValue( $this->builder->setValue( 'getSubject' ) ) );

		// array of SMWDataItem
		$semanticData->expects( $this->any() )
			->method( 'getPropertyValues' )
			->will( $this->returnValue( $this->builder->setValue( 'getPropertyValues' ) ) );

		$semanticData->expects( $this->any() )
			->method( 'hasVisibleSpecialProperties' )
			->will( $this->returnValue( $this->builder->setValue( 'hasVisibleSpecialProperties' ) ) );

		$semanticData->expects( $this->any() )
			->method( 'hasVisibleProperties' )
			->will( $this->returnValue( $this->builder->setValue( 'hasVisibleProperties' ) ) );

		$semanticData->expects( $this->any() )
			->method( 'getProperties' )
			->will( $this->returnValue( $this->builder->setValue( 'getProperties' ) ) );

		$semanticData->expects( $this->any() )
			->method( 'addPropertyObjectValue' )
			->will( $this->builder->setCallback( 'addPropertyObjectValue' ) );

		return $semanticData;
	}

	/**
	 * Helper method that returns a CacheableObjectCollector object
	 *
	 * @since 1.9
	 *
	 * @return CacheableObjectCollector
	 */
	public function CacheableObjectCollector() {

		// CacheableObjectCollector is an abstract class therefore necessary methods
		// are declared by default while other methods are only mocked if needed
		// because setMethods overrides the original signature
		$methods = array( 'cacheSetup', 'doCollect' );

		if (  $this->builder->hasValue( 'getResults' ) ) {
			$methods[] = 'getResults';
		}

		$collector = $this->getMockBuilder( '\SMW\Store\CacheableObjectCollector' )
			->setMethods( $methods )
			->getMock();

		$collector->expects( $this->any() )
			->method( 'doCollect' )
			->will( $this->returnValue( $this->builder->setValue( 'doCollect' ) ) );

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

		$observer = $this->getMockBuilder( 'SMW\Observer' )
			->setMethods( array( 'updateOutput' ) )
			->getMock();

		$observer->expects( $this->any() )
			->method( 'updateOutput' )
			->will( $this->builder->setCallback( 'updateOutput' ) );

		return $observer;
	}

	/**
	 * Returns a ParserData object
	 *
	 * @since 1.9
	 *
	 * @return ParserData
	 */
	public function ParserData() {

		$parserData = $this->getMockBuilder( '\SMW\ParserData' )
			->disableOriginalConstructor()
			->getMock();

		$parserData->expects( $this->any() )
			->method( 'getData' )
			->will( $this->returnValue( $this->builder->setValue( 'getData' ) ) );

		$parserData->expects( $this->any() )
			->method( 'getTitle' )
			->will( $this->returnValue( $this->builder->setValue( 'getTitle' ) ) );

		$parserData->expects( $this->any() )
			->method( 'getSubject' )
			->will( $this->returnValue( $this->builder->setValue( 'getSubject' ) ) );

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

		$parserOutput->expects( $this->any() )
			->method( 'getExtensionData' )
			->will( $this->returnValue( $this->builder->setValue( 'getExtensionData' ) ) );

		$parserOutput->expects( $this->any() )
			->method( 'setExtensionData' )
			->will( $this->returnValue( $this->builder->setValue( 'setExtensionData' ) ) );


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

		$wikiPage->expects( $this->any() )
			->method( 'getTimestamp' )
			->will( $this->returnValue( $this->builder->setValue( 'getTimestamp' ) ) );

		$wikiPage->expects( $this->any() )
			->method( 'getTitle' )
			->will( $this->returnValue( $this->builder->setValue( 'getTitle' ) ) );

		$wikiPage->expects( $this->any() )
			->method( 'getRevision' )
			->will( $this->returnValue( $this->builder->setValue( 'getRevision' ) ) );

		$wikiPage->expects( $this->any() )
			->method( 'getParserOutput' )
			->will( $this->returnValue( $this->builder->setValue( 'getParserOutput' ) ) );

		$wikiPage->expects( $this->any() )
			->method( 'makeParserOptions' )
			->will( $this->returnValue( $this->builder->setValue( 'makeParserOptions' ) ) );

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

		$revision->expects( $this->any() )
			->method( 'getTimestamp' )
			->will( $this->returnValue( $this->builder->setValue( 'getTimestamp' ) ) );

		$revision->expects( $this->any() )
			->method( 'getParentId' )
			->will( $this->returnValue( $this->builder->setValue( 'getParentId' ) ) );

		$revision->expects( $this->any() )
			->method( 'getId' )
			->will( $this->returnValue( $this->builder->setValue( 'getId' ) ) );

		$revision->expects( $this->any() )
			->method( 'getUser' )
			->will( $this->returnValue( $this->builder->setValue( 'getUser' ) ) );

		$revision->expects( $this->any() )
			->method( 'getText' )
			->will( $this->returnValue( $this->builder->setValue( 'getText' ) ) );

		$revision->expects( $this->any() )
			->method( 'getContent' )
			->will( $this->returnValue( $this->builder->setValue( 'getContent' ) ) );

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

		$dataValue->expects( $this->any() )
			->method( 'getProperty' )
			->will( $this->returnValue( $this->builder->setValue( 'getProperty' ) ) );

		$dataValue->expects( $this->any() )
			->method( 'isValid' )
			->will( $this->returnValue( $this->builder->setValue( 'isValid' ) ) );

		$dataValue->expects( $this->any() )
			->method( 'getDataItem' )
			->will( $this->returnValue( $this->builder->setValue( 'getDataItem' ) ) );

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
			->method( 'toArray' )
			->will( $this->returnValue( $this->builder->setValue( 'toArray' ) ) );

		$queryResult->expects( $this->any() )
			->method( 'getErrors' )
			->will( $this->returnValue( $this->builder->setValue( 'getErrors', array() ) ) );

		$queryResult->expects( $this->any() )
			->method( 'hasFurtherResults' )
			->will( $this->returnValue( $this->builder->setValue( 'hasFurtherResults' ) ) );

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
			->method( 'isUserDefined' )
			->will( $this->returnValue( $this->builder->setValue( 'isUserDefined' ) ) );

		$property->expects( $this->any() )
			->method( 'isShown' )
			->will( $this->returnValue( $this->builder->setValue( 'isShown' ) ) );

		$property->expects( $this->any() )
			->method( 'getDiWikiPage' )
			->will( $this->returnValue( $this->builder->setValue( 'getDiWikiPage' ) ) );

		$property->expects( $this->any() )
			->method( 'findPropertyTypeID' )
			->will( $this->returnValue( $this->builder->setValue( 'findPropertyTypeID', '_wpg' ) ) );

		$property->expects( $this->any() )
			->method( 'getKey' )
			->will( $this->returnValue( $this->builder->setValue( 'getKey', '_wpg' ) ) );

		$property->expects( $this->any() )
			->method( 'getDIType' )
			->will( $this->returnValue( SMWDataItem::TYPE_PROPERTY ) );

		$property->expects( $this->any() )
			->method( 'getLabel' )
			->will( $this->returnValue( $this->builder->setValue( 'getLabel' ) ) );

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

		$idTable = $this->getMock( 'stdClass', array( 'getIdTable') );

		$idTable->expects( $this->any() )
			->method( 'getIdTable' )
			->will( $this->returnValue( 'smw_id_table_test' ) );

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->setMethods( array(
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
			) )
			->getMock();

		/**
		 * @param $subject mixed SMWDIWikiPage or null
		 * @param $property SMWDIProperty
		 * @param $requestoptions SMWRequestOptions
		 *
		 * @return array of SMWDataItem
		 */
		$store->expects( $this->any() )
			->method( 'getPropertyValues' )
			->will( $this->builder->setCallback( 'getPropertyValues' ) );

		$store->expects( $this->any() )
			->method( 'getPropertiesSpecial' )
			->will( $this->returnValue( $this->builder->setValue( 'getPropertiesSpecial' ) ) );

		$store->expects( $this->any() )
			->method( 'deleteSubject' )
			->will( $this->returnValue( $this->builder->setValue( 'deleteSubject' ) ) );

		$store->expects( $this->any() )
			->method( 'getSemanticData' )
			->will( $this->returnValue( $this->builder->setValue( 'getSemanticData' ) ) );

		$store->expects( $this->any() )
			->method( 'getUnusedPropertiesSpecial' )
			->will( $this->returnValue( $this->builder->setValue( 'getUnusedPropertiesSpecial' ) ) );

		$store->expects( $this->any() )
			->method( 'getWantedPropertiesSpecial' )
			->will( $this->returnValue( $this->builder->setValue( 'getWantedPropertiesSpecial' ) ) );

		$store->expects( $this->any() )
			->method( 'getSQLConditions' )
			->will( $this->returnValue( $this->builder->setValue( 'getSQLConditions' ) ) );

		$store->expects( $this->any() )
			->method( 'getStatistics' )
			->will( $this->returnValue( $this->builder->setValue( 'getStatistics' ) ) );

		$store->expects( $this->any() )
			->method( 'getPropertyTables' )
			->will( $this->returnValue( $this->builder->setValue( 'getPropertyTables' ) ) );

		$store->expects( $this->any() )
			->method( 'getQueryResult' )
			->will( $this->builder->setCallback( 'getQueryResult' ) );

		$store->expects( $this->any() )
			->method( 'updateData' )
			->will( $this->builder->setCallback( 'updateData' ) );

		$store->expects( $this->any() )
			->method( 'clearData' )
			->will( $this->builder->setCallback( 'clearData' ) );

		$store->expects( $this->any() )
			->method( 'getAllPropertySubjects' )
			->will( $this->builder->setCallback( 'getAllPropertySubjects' ) );

		$store->expects( $this->any() )
			->method( 'getPropertySubjects' )
			->will( $this->builder->setCallback( 'getPropertySubjects' ) );

		$store->expects( $this->any() )
			->method( 'refreshConceptCache' )
			->will( $this->builder->setCallback( 'refreshConceptCache' ) );

		$store->expects( $this->any() )
			->method( 'deleteConceptCache' )
			->will( $this->builder->setCallback( 'deleteConceptCache' ) );

		$store->expects( $this->any() )
			->method( 'getConceptCacheStatus' )
			->will( $this->builder->setCallback( 'getConceptCacheStatus' ) );

		$store->expects( $this->any() )
			->method( 'getObjectIds' )
			->will( $this->returnValue( $idTable ) );

		$store->expects( $this->any() )
			->method( 'getStatisticsTable' )
			->will( $this->returnValue( 'smw_statistics_table_test' ) );

		return $store;
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

		$skin->expects( $this->any() )
			->method( 'getTitle' )
			->will( $this->returnValue( $this->builder->setValue( 'getTitle' ) ) );

		$skin->expects( $this->any() )
			->method( 'getOutput' )
			->will( $this->returnValue( $this->builder->setValue( 'getOutput' ) ) );

		$skin->expects( $this->any() )
			->method( 'getContext' )
			->will( $this->returnValue( $this->builder->setValue( 'getContext' ) ) );

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

		$parser->expects( $this->any() )
			->method( 'getParserOutput' )
			->will( $this->returnValue( $this->builder->setValue( 'getParserOutput' ) ) );

		$parser->expects( $this->any() )
			->method( 'getTitle' )
			->will( $this->returnValue( $this->builder->setValue( 'getTitle' ) ) );

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

		$linksUpdate->expects( $this->any() )
			->method( 'getTitle' )
			->will( $this->returnValue( $this->builder->setValue( 'getTitle' ) ) );

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

		$databaseBase = $this->getMockBuilder( 'DatabaseBase' )
			->disableOriginalConstructor()
			->setMethods( array(
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
			) )
			->getMock();

		$databaseBase->expects( $this->any() )
			->method( 'selectField' )
			->will( $this->returnValue( $this->builder->setValue( 'selectField' ) ) );

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

}

<?php

namespace SMW\Test;

use SMW\ArrayAccessor;
use SMWDataItem;

/**
 * MockObject builder
 *
 * @file
 *
 * @license GNU GPL v2+
 * @since   1.9
 *
 * @author mwjames
 */

/**
 * MockObject builder
 *
 * @ingroup Test
 *
 * @group SMW
 * @group SMWExtension
 *
 * @codeCoverageIgnore
 */
class MockObjectBuilder extends \PHPUnit_Framework_TestCase {

	/** @var ArrayAccessor */
	protected $accessor;

	/**
	 * @since 1.9
	 *
	 * @param ArrayAccessor $accessor
	 */
	public function __construct( ArrayAccessor $accessor ) {
		$this->accessor = $accessor;
	}

	/**
	 * Helper method that returns a random string
	 *
	 * @since 1.9
	 *
	 * @param $length
	 * @param $prefix identify a specific random string during testing
	 *
	 * @return string
	 */
	protected function newRandomString( $length = 10, $prefix = null ) {
		return $prefix . ( $prefix ? '-' : '' ) . substr( str_shuffle( "0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ" ), 0, $length );
	}

	/**
	 * Sets value
	 *
	 * @since 1.9
	 *
	 * @param $key
	 * @param $default
	 *
	 * @return mixed|null
	 */
	protected function setValue( $key, $default = null ) {
		return $this->accessor->has( $key ) ? $this->accessor->get( $key ) : $default;
	}

	/**
	 * Determine callback function otherwise return simple value
	 *
	 * @since 1.9
	 *
	 * @param $key
	 * @param $default
	 *
	 * @return mixed|null
	 */
	protected function setCallback( $key, $default = null ) {
		return is_callable( $this->setValue( $key ) ) ? $this->returnCallback( $this->setValue( $key ) ) : $this->returnValue( $this->setValue( $key, $default ) );
	}

	/**
	 * Returns a SemanticData object
	 *
	 * @since 1.9
	 *
	 * @return SemanticData
	 */
	public function getMockSemanticData() {

		$semanticData = $this->getMockBuilder( 'SMW\SemanticData' )
			->disableOriginalConstructor()
			->getMock();

		$semanticData->expects( $this->any() )
			->method( 'getSubject' )
			->will( $this->returnValue( $this->setValue( 'getSubject' ) ) );

		// array of SMWDataItem
		$semanticData->expects( $this->any() )
			->method( 'getPropertyValues' )
			->will( $this->returnValue( $this->setValue( 'getPropertyValues' ) ) );

		$semanticData->expects( $this->any() )
			->method( 'hasVisibleSpecialProperties' )
			->will( $this->returnValue( $this->setValue( 'hasVisibleSpecialProperties' ) ) );

		$semanticData->expects( $this->any() )
			->method( 'hasVisibleProperties' )
			->will( $this->returnValue( $this->setValue( 'hasVisibleProperties' ) ) );

		$semanticData->expects( $this->any() )
			->method( 'getProperties' )
			->will( $this->returnValue( $this->setValue( 'getProperties' ) ) );

		$semanticData->expects( $this->any() )
			->method( 'addPropertyObjectValue' )
			->will( $this->setCallback( 'addPropertyObjectValue' ) );

		return $semanticData;
	}

	/**
	 * Returns a ParserData object
	 *
	 * @since 1.9
	 *
	 * @return ParserData
	 */
	public function getMockParserData() {

		$parserData = $this->getMockBuilder( '\SMW\ParserData' )
			->disableOriginalConstructor()
			->getMock();

		$parserData->expects( $this->any() )
			->method( 'getData' )
			->will( $this->returnValue( $this->setValue( 'getData' ) ) );

		$parserData->expects( $this->any() )
			->method( 'getSubject' )
			->will( $this->returnValue( $this->setValue( 'getSubject' ) ) );

		return $parserData;
	}

	/**
	 * Returns a Factbox object
	 *
	 * @since 1.9
	 *
	 * @return Factbox
	 */
	public function getMockFactbox() {

		$factbox = $this->getMockBuilder( '\SMW\Factbox' )
			->disableOriginalConstructor()
			->getMock();

		$factbox->expects( $this->any() )
			->method( 'isVisible' )
			->will( $this->returnValue( $this->setValue( 'isVisible' ) ) );

		$factbox->expects( $this->any() )
			->method( 'getContent' )
			->will( $this->returnValue( $this->setValue( 'getContent' ) ) );

		return $factbox;
	}

	/**
	 * Returns a SMWQuery object
	 *
	 * @since 1.9
	 *
	 * @return SMWQuery
	 */
	public function getMockQuery() {

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
	public function getMockUser() {

		$user = $this->getMockBuilder( 'user' )
			->disableOriginalConstructor()
			->getMock();

		$user->expects( $this->any() )
			->method( 'getUserPage' )
			->will( $this->returnValue( $this->setValue( 'getUserPage' ) ) );

		return $user;
	}

	/**
	 * Returns a ParserOptions object
	 *
	 * @since 1.9
	 *
	 * @return ParserOptions
	 */
	public function getMockParserOptions() {

		$parserOptions = $this->getMockBuilder( 'ParserOptions' )
			->disableOriginalConstructor()
			->getMock();

		$parserOptions->expects( $this->any() )
			->method( 'getTargetLanguage' )
			->will( $this->returnValue( $this->setValue( 'getTargetLanguage' ) ) );

		return $parserOptions;
	}

	/**
	 * Returns a ContentParser object
	 *
	 * @since 1.9
	 *
	 * @return ContentParser
	 */
	public function getMockContentParser() {

		$contentParser = $this->getMockBuilder( '\SMW\ContentParser' )
			->disableOriginalConstructor()
			->getMock();

		$contentParser->expects( $this->any() )
			->method( 'getOutput' )
			->will( $this->returnValue( $this->setValue( 'getOutput', $this->getMockParserOutput() ) ) );

		$contentParser->expects( $this->any() )
			->method( 'getErrors' )
			->will( $this->returnValue( $this->setValue( 'getErrors', array() ) ) );

		return $contentParser;
	}

	/**
	 * Returns a ParserOutput object
	 *
	 * @since 1.9
	 *
	 * @return ParserOutput
	 */
	public function getMockParserOutput() {

		$parserOutput = $this->getMockBuilder( 'ParserOutput' )
			->disableOriginalConstructor()
			->getMock();

		$parserOutput->expects( $this->any() )
			->method( 'getExtensionData' )
			->will( $this->returnValue( $this->setValue( 'getExtensionData' ) ) );

		$parserOutput->expects( $this->any() )
			->method( 'setExtensionData' )
			->will( $this->returnValue( $this->setValue( 'setExtensionData' ) ) );


		return $parserOutput;
	}

	/**
	 * Returns a WikiPage object
	 *
	 * @since 1.9
	 *
	 * @return WikiPage
	 */
	public function getMockWikiPage() {

		$wikiPage = $this->getMockBuilder( 'WikiPage' )
			->disableOriginalConstructor()
			->getMock();

		$wikiPage->expects( $this->any() )
			->method( 'getTimestamp' )
			->will( $this->returnValue( $this->setValue( 'getTimestamp' ) ) );

		$wikiPage->expects( $this->any() )
			->method( 'getTitle' )
			->will( $this->returnValue( $this->setValue( 'getTitle' ) ) );

		$wikiPage->expects( $this->any() )
			->method( 'getRevision' )
			->will( $this->returnValue( $this->setValue( 'getRevision' ) ) );

		$wikiPage->expects( $this->any() )
			->method( 'getParserOutput' )
			->will( $this->returnValue( $this->setValue( 'getParserOutput' ) ) );

		$wikiPage->expects( $this->any() )
			->method( 'makeParserOptions' )
			->will( $this->returnValue( $this->setValue( 'makeParserOptions' ) ) );

		return $wikiPage;
	}

	/**
	 * Returns a Revision object
	 *
	 * @since 1.9
	 *
	 * @return Revision
	 */
	public function getMockRevision() {

		$revision = $this->getMockBuilder( 'Revision' )
			->disableOriginalConstructor()
			->getMock();

		$revision->expects( $this->any() )
			->method( 'getTimestamp' )
			->will( $this->returnValue( $this->setValue( 'getTimestamp' ) ) );

		$revision->expects( $this->any() )
			->method( 'getParentId' )
			->will( $this->returnValue( $this->setValue( 'getParentId' ) ) );

		$revision->expects( $this->any() )
			->method( 'getId' )
			->will( $this->returnValue( $this->setValue( 'getId' ) ) );

		$revision->expects( $this->any() )
			->method( 'getUser' )
			->will( $this->returnValue( $this->setValue( 'getUser' ) ) );

		$revision->expects( $this->any() )
			->method( 'getText' )
			->will( $this->returnValue( $this->setValue( 'getText' ) ) );

		return $revision;
	}

	/**
	 * Returns a DataValue object
	 *
	 * @since 1.9
	 *
	 * @return DataValue
	 */
	public function getMockDataValue() {

		$dataValue = $this->getMockBuilder( $this->setValue( 'DataValueType' ) )
			->disableOriginalConstructor()
			->getMock();

		$dataValue->expects( $this->any() )
			->method( 'getProperty' )
			->will( $this->returnValue( $this->setValue( 'getProperty' ) ) );

		$dataValue->expects( $this->any() )
			->method( 'isValid' )
			->will( $this->returnValue( $this->setValue( 'isValid' ) ) );

		$dataValue->expects( $this->any() )
			->method( 'getDataItem' )
			->will( $this->returnValue( $this->setValue( 'getDataItem' ) ) );

		return $dataValue;
	}

	/**
	 * Returns a SMWQueryResult object
	 *
	 * @since 1.9
	 *
	 * @return SMWQueryResult
	 */
	public function getMockQueryResult() {

		$queryResult = $this->getMockBuilder( 'SMWQueryResult' )
			->disableOriginalConstructor()
			->getMock();

		$queryResult->expects( $this->any() )
			->method( 'toArray' )
			->will( $this->returnValue( $this->setValue( 'toArray' ) ) );

		$queryResult->expects( $this->any() )
			->method( 'getErrors' )
			->will( $this->returnValue( $this->setValue( 'getErrors', array() ) ) );

		$queryResult->expects( $this->any() )
			->method( 'hasFurtherResults' )
			->will( $this->returnValue( $this->setValue( 'hasFurtherResults' ) ) );

		return $queryResult;
	}

	/**
	 * Helper method that returns a DIWikiPage object
	 *
	 * @since 1.9
	 *
	 * @return DIWikiPage
	 */
	public function getMockDIWikiPage() {

		$diWikiPage = $this->getMockBuilder( '\SMW\DIWikiPage' )
			->disableOriginalConstructor()
			->getMock();

		$diWikiPage->expects( $this->any() )
			->method( 'getTitle' )
			->will( $this->returnValue( $this->setValue( 'getTitle' ) ) );

		$diWikiPage->expects( $this->any() )
			->method( 'getDBkey' )
			->will( $this->returnValue( $this->setValue( 'getDBkey', $this->newRandomString( 10, 'DIWikiPage-auto-dbkey' ) ) ) );

		$diWikiPage->expects( $this->any() )
			->method( 'getPrefixedText' )
			->will( $this->returnValue( $this->setValue( 'getPrefixedText', $this->newRandomString( 10, 'DIWikiPage-auto-prefixedText' ) ) ) );

		$diWikiPage->expects( $this->any() )
			->method( 'getDIType' )
			->will( $this->returnValue( SMWDataItem::TYPE_WIKIPAGE ) );

		$diWikiPage->expects( $this->any() )
			->method( 'findPropertyTypeID' )
			->will( $this->returnValue( $this->setValue( 'findPropertyTypeID', '_wpg' ) ) );

		return $diWikiPage;
	}

	/**
	 * Returns a DIProperty object
	 *
	 * @par Example:
	 * @code
	 *  $property = array(
	 *   'isUserDefined' => $isUserDefined,
	 *   'getDiWikiPage' => $this->getMockDIWikiPage( true ),
	 *   'getLabel'      => $this->getRandomString(),
	 *  );
	 *
	 *  $mockObject = new MockObjectBuilder( new ArrayAccessor( $property ) );
	 *  $mockObject->getMockDIProperty();
	 * @endcode
	 *
	 * @since 1.9
	 *
	 * @return DIProperty
	 */
	public function getMockDIProperty() {

		$property = $this->getMockBuilder( '\SMW\DIProperty' )
			->disableOriginalConstructor()
			->getMock();

		$property->expects( $this->any() )
			->method( 'isUserDefined' )
			->will( $this->returnValue( $this->setValue( 'isUserDefined' ) ) );

		$property->expects( $this->any() )
			->method( 'isShown' )
			->will( $this->returnValue( $this->setValue( 'isShown' ) ) );

		$property->expects( $this->any() )
			->method( 'getDiWikiPage' )
			->will( $this->returnValue( $this->setValue( 'getDiWikiPage' ) ) );

		$property->expects( $this->any() )
			->method( 'findPropertyTypeID' )
			->will( $this->returnValue( $this->setValue( 'findPropertyTypeID', '_wpg' ) ) );

		$property->expects( $this->any() )
			->method( 'getKey' )
			->will( $this->returnValue( $this->setValue( 'getKey', '_wpg' ) ) );

		$property->expects( $this->any() )
			->method( 'getDIType' )
			->will( $this->returnValue( SMWDataItem::TYPE_PROPERTY ) );

		$property->expects( $this->any() )
			->method( 'getLabel' )
			->will( $this->returnValue( $this->setValue( 'getLabel' ) ) );

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
	public function getMockStore( ) {

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
				'getConceptCacheStatus'
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
			->will( $this->setCallback( 'getPropertyValues' ) );

		$store->expects( $this->any() )
			->method( 'getPropertiesSpecial' )
			->will( $this->returnValue( $this->setValue( 'getPropertiesSpecial' ) ) );

		$store->expects( $this->any() )
			->method( 'deleteSubject' )
			->will( $this->returnValue( $this->setValue( 'deleteSubject' ) ) );

		$store->expects( $this->any() )
			->method( 'getSemanticData' )
			->will( $this->returnValue( $this->setValue( 'getSemanticData' ) ) );

		$store->expects( $this->any() )
			->method( 'getUnusedPropertiesSpecial' )
			->will( $this->returnValue( $this->setValue( 'getUnusedPropertiesSpecial' ) ) );

		$store->expects( $this->any() )
			->method( 'getWantedPropertiesSpecial' )
			->will( $this->returnValue( $this->setValue( 'getWantedPropertiesSpecial' ) ) );

		$store->expects( $this->any() )
			->method( 'getSQLConditions' )
			->will( $this->returnValue( $this->setValue( 'getSQLConditions' ) ) );

		$store->expects( $this->any() )
			->method( 'getStatistics' )
			->will( $this->returnValue( $this->setValue( 'getStatistics' ) ) );

		$store->expects( $this->any() )
			->method( 'getPropertyTables' )
			->will( $this->returnValue( $this->setValue( 'getPropertyTables' ) ) );

		$store->expects( $this->any() )
			->method( 'getQueryResult' )
			->will( $this->setCallback( 'getQueryResult' ) );

		$store->expects( $this->any() )
			->method( 'getAllPropertySubjects' )
			->will( $this->setCallback( 'getAllPropertySubjects' ) );

		$store->expects( $this->any() )
			->method( 'getPropertySubjects' )
			->will( $this->setCallback( 'getPropertySubjects' ) );

		$store->expects( $this->any() )
			->method( 'refreshConceptCache' )
			->will( $this->setCallback( 'refreshConceptCache' ) );

		$store->expects( $this->any() )
			->method( 'deleteConceptCache' )
			->will( $this->setCallback( 'deleteConceptCache' ) );

		$store->expects( $this->any() )
			->method( 'getConceptCacheStatus' )
			->will( $this->setCallback( 'getConceptCacheStatus' ) );

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
	public function getMockDIError() {

		$errors = $this->getMockBuilder( 'SMWDIError' )
			->disableOriginalConstructor()
			->getMock();

		$errors->expects( $this->any() )
			->method( 'getErrors' )
			->will( $this->returnValue( $this->setValue( 'getErrors' ) ) );

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
	public function getMockTitle() {

		$title = $this->getMockBuilder( 'Title' )
			->disableOriginalConstructor()
			->getMock();

		$title->expects( $this->any() )
			->method( 'getDBkey' )
			->will( $this->returnValue( $this->setValue( 'getDBkey', $this->newRandomString( 10, 'Title-auto-dbkey' ) ) ) );

		$title->expects( $this->any() )
			->method( 'getInterwiki' )
			->will( $this->returnValue( $this->setValue( 'getInterwiki', '' ) ) );

		$title->expects( $this->any() )
			->method( 'getArticleID' )
			->will( $this->returnValue( $this->setValue( 'getArticleID', rand( 10, 10000 ) ) ) );

		$title->expects( $this->any() )
			->method( 'getNamespace' )
			->will( $this->returnValue( $this->setValue( 'getNamespace', NS_MAIN ) ) );

		$title->expects( $this->any() )
			->method( 'isKnown' )
			->will( $this->returnValue( $this->setValue( 'exists' ) ) );

		$title->expects( $this->any() )
			->method( 'exists' )
			->will( $this->returnValue( $this->setValue( 'exists' ) ) );

		$title->expects( $this->any() )
			->method( 'getLatestRevID' )
			->will( $this->returnValue( $this->setValue( 'getLatestRevID', rand( 10, 5000 ) ) ) );

		$title->expects( $this->any() )
			->method( 'getFirstRevision' )
			->will( $this->returnValue( $this->setValue( 'getFirstRevision' ) ) );

		$title->expects( $this->any() )
			->method( 'getText' )
			->will( $this->returnValue( $this->setValue( 'getText' ) ) );

		$title->expects( $this->any() )
			->method( 'getPrefixedText' )
			->will( $this->returnValue( $this->setValue( 'getPrefixedText', $this->newRandomString( 10, 'Title-auto-prefixedtext' ) ) ) );

		$title->expects( $this->any() )
			->method( 'isSpecialPage' )
			->will( $this->returnValue( $this->setValue( 'isSpecialPage', false ) ) );

		$title->expects( $this->any() )
			->method( 'getContentModel' )
			->will( $this->returnValue( $this->setValue( 'getContentModel' ) ) );

		$title->expects( $this->any() )
			->method( 'getPageLanguage' )
			->will( $this->returnValue( $this->setValue( 'getPageLanguage' ) ) );

		return $title;
	}

	/**
	 * Helper method that returns a Skin object
	 *
	 * @since 1.9
	 *
	 * @return Skin
	 */
	public function getMockSkin() {

		$skin = $this->getMockBuilder( 'Skin' )
			->disableOriginalConstructor()
			->getMock();

		$skin->expects( $this->any() )
			->method( 'getTitle' )
			->will( $this->returnValue( $this->setValue( 'getTitle' ) ) );

		$skin->expects( $this->any() )
			->method( 'getOutput' )
			->will( $this->returnValue( $this->setValue( 'getOutput' ) ) );

		$skin->expects( $this->any() )
			->method( 'getContext' )
			->will( $this->returnValue( $this->setValue( 'getContext' ) ) );

		return $skin;
	}

	/**
	 * Helper method that returns a OutputPage object
	 *
	 * @since 1.9
	 *
	 * @return OutputPage
	 */
	public function getMockOutputPage() {

		// getHeadLinksArray doesn't exist in MW 1.19

		$outputPage = $this->getMockBuilder( 'OutputPage' )
		->disableOriginalConstructor()
//		->setMethods( array( 'getHeadLinksArray' ) )
		->getMock();

		$outputPage->expects( $this->any() )
			->method( 'getTitle' )
			->will( $this->returnValue( $this->setValue( 'getTitle' ) ) );

		$outputPage->expects( $this->any() )
			->method( 'getContext' )
			->will( $this->returnValue( $this->setValue( 'getContext' ) ) );

		$outputPage->expects( $this->any() )
			->method( 'addModules' )
			->will( $this->returnValue( $this->setValue( 'addModules' ) ) );

		$outputPage->expects( $this->any() )
			->method( 'addLink' )
			->will( $this->returnValue( $this->setValue( 'addLink' ) ) );

		$outputPage->expects( $this->any() )
			->method( 'getHeadLinksArray' )
			->will( $this->setCallback( 'getHeadLinksArray' ) );

		return $outputPage;
	}

	/**
	 * Helper method that returns a DatabaseBase object
	 *
	 * @since 1.9
	 *
	 * @return DatabaseBase
	 */
	public function getMockDatabaseBase() {

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
				'getServerVersion'
			) )
			->getMock();

		$databaseBase->expects( $this->any() )
			->method( 'selectField' )
			->will( $this->returnValue( $this->setValue( 'selectField' ) ) );

		return $databaseBase;
	}

}

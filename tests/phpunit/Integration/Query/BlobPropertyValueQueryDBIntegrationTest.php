<?php

namespace SMW\Tests\Integration\Query;

use SMW\Tests\MwDBaseUnitTestCase;
use SMW\Tests\Utils\UtilityFactory;

use SMW\Query\Language\ThingDescription;
use SMW\Query\Language\SomeProperty;

use SMW\DIWikiPage;
use SMW\DIProperty;
use SMW\DataValueFactory;
use SMW\ApplicationFactory;

use SMWDIBlob as DIBlob;
use SMWQuery as Query;
use SMWQueryResult as QueryResult;
use SMWDataValue as DataValue;
use SMWDataItem as DataItem;
use SMW\Query\PrintRequest as PrintRequest;
use SMWPropertyValue as PropertyValue;
use Title;

/**
 * @group SMW
 * @group SMWExtension
 *
 * @group semantic-mediawiki-integration
 * @group semantic-mediawiki-query
 *
 * @group mediawiki-database
 * @group medium
 *
 * @license GNU GPL v2+
 * @since 2.0
 *
 * @author mwjames
 */
class BlobPropertyValueQueryDBIntegrationTest extends MwDBaseUnitTestCase {

	private $semanticDataFactory;
	private $dataValueFactory;
	private $queryResultValidator;

	private $subjects = array();
	private $pageCreator;

	private $stringBuilder;
	private $stringValidator;

	protected function setUp() {
		parent::setUp();

		$utilityFactory = UtilityFactory::getInstance();

		$this->dataValueFactory = DataValueFactory::getInstance();
		$this->semanticDataFactory = $utilityFactory->newSemanticDataFactory();
		$this->queryResultValidator = $utilityFactory->newValidatorFactory()->newQueryResultValidator();

		$this->pageCreator = $utilityFactory->newPageCreator();
		$this->stringBuilder = $utilityFactory->newStringBuilder();

		$this->queryParser = ApplicationFactory::getInstance()->newQueryParser();
	}

	protected function tearDown() {

		UtilityFactory::getInstance()->newPageDeleter()->doDeletePoolOfPages( $this->subjects );

		parent::tearDown();
	}

	public function testUserDefinedBlobProperty() {

		$property = new DIProperty( 'SomeBlobProperty' );
		$property->setPropertyTypeId( '_txt' );

		$dataItem = new DIBlob( 'SomePropertyBlobValue' );

		$semanticData = $this->semanticDataFactory->newEmptySemanticData( __METHOD__ );

		$semanticData->addDataValue(
			$this->dataValueFactory->newDataItemValue( $dataItem, $property )
		);

		$this->getStore()->updateData( $semanticData );

		$this->assertArrayHasKey(
			$property->getKey(),
			$this->getStore()->getSemanticData( $semanticData->getSubject() )->getProperties()
		);

		$propertyValue = new PropertyValue( '__pro' );
		$propertyValue->setDataItem( $property );

		$description = new SomeProperty(
			$property,
			new ThingDescription()
		);

		$description->addPrintRequest(
			new PrintRequest( PrintRequest::PRINT_PROP, null, $propertyValue )
		);

		$query = new Query(
			$description,
			false,
			false
		);

		$query->querymode = Query::MODE_INSTANCES;

		$queryResult = $this->getStore()->getQueryResult( $query );

		$this->queryResultValidator->assertThatQueryResultContains(
			$dataItem,
			$queryResult
		);

		$this->subjects[] = $semanticData->getSubject();
	}

	public function testRegexSearchForCharactersThatRequireSpecialEscapePattern() {

		$this->skipTestForDatabase(
			array( 'postgres' ),
			'Skipping the test for postgres because of "Query failed: ERROR: duplicate key value violates unique constraint"'
		);

		$property = Title::newFromText( 'Has RegexBlobSearch', SMW_NS_PROPERTY );
		$title = Title::newFromText( __METHOD__ );

		$this->pageCreator
			->createPage( $property )
			->doEdit( '[[Has type::text]]' );

		$this->stringBuilder
			->addString( '[[Has RegexBlobSearch::{(+*. \;)}]]' )
			->addString( '{{#set:|Has RegexBlobSearch=[(*. \=^)]}}' )
			->addString( '{{#subobject:FixedRegexBlobSearch|Has RegexBlobSearch=[(+. \:)]}}' );

		$this->pageCreator
			->createPage( $title )
			->doEdit(  $this->stringBuilder->getString() );

		$this->stringBuilder
			->addString( '[[Has RegexBlobSearch::~*{*]]' )
			->addString( '[[Has RegexBlobSearch::~*}*]]' )
			->addString( '[[Has RegexBlobSearch::~*(*]]' )
			->addString( '[[Has RegexBlobSearch::~*)*]]' )
			->addString( '[[Has RegexBlobSearch::~*]*]]' )
			->addString( '[[Has RegexBlobSearch::~*[*]]' )
			->addString( '[[Has RegexBlobSearch::~*;?}]]' )
			->addString( ' OR ' )
			->addString( '[[Has RegexBlobSearch::~*+.*]]' )
			->addString( ' OR ' )
			->addString( '[[Has RegexBlobSearch::~*=^*]]' );

		$description = $this->queryParser->getQueryDescription( $this->stringBuilder->getString() );

		// Query::applyRestrictions
		$GLOBALS['smwgQMaxSize'] = 20;

		$query = new Query(
			$description,
			false,
			false
		);

		$query->querymode = Query::MODE_INSTANCES;
		$query->setLimit( 10 );

		$this->subjects[] = DIWikiPage::newFromTitle( $title );
		$this->subjects[] = new DIWikiPage( $title->getDBKey(), NS_MAIN, '', 'FixedRegexBlobSearch' );

		$this->queryResultValidator->assertThatQueryResultHasSubjects(
			$this->subjects,
			$this->getStore()->getQueryResult( $query )
		);

		$this->subjects[] = $property;
	}

	public function testRegexSearchForEscapeCharacter() {

		$this->skipTestForDatabase(
			array( 'sqlite', 'postgres'),
			'Skipping the test because "\" needs special attention on sqlite, postgres'
		);

		$property = Title::newFromText( 'Has RegexBlobSearch', SMW_NS_PROPERTY );
		$title = Title::newFromText( __METHOD__ );

		$this->pageCreator
			->createPage( $property )
			->doEdit( '[[Has type::text]]' );

		$this->stringBuilder
			->addString( '{{#set:|Has RegexBlobSearch=[(+*. \.)]}}' )
			->addString( '{{#set:|Has RegexBlobSearch=[(+*. \;)]}}' )
			->addString( '{{#subobject:FixedRegexBlobSearch|Has RegexBlobSearch=[(+*. \:)]}}' );

		$this->pageCreator
			->createPage( $title )
			->doEdit(  $this->stringBuilder->getString() );

		$this->stringBuilder
			->addString( '[[Has RegexBlobSearch::~*\.*]]' )
			->addString( '[[Has RegexBlobSearch::~*\;*]]' )
			->addString( ' OR' )
			->addString( '[[Has RegexBlobSearch::~*\:*]]' );

		$description = $this->queryParser->getQueryDescription( $this->stringBuilder->getString() );

		// Query::applyRestrictions
		$GLOBALS['smwgQMaxSize'] = 20;

		$query = new Query(
			$description,
			false,
			false
		);

		$query->querymode = Query::MODE_INSTANCES;
		$query->setLimit( 10 );

		$this->subjects[] = DIWikiPage::newFromTitle( $title );
		$this->subjects[] = new DIWikiPage( $title->getDBKey(), NS_MAIN, '', 'FixedRegexBlobSearch' );

		$this->queryResultValidator->assertThatQueryResultHasSubjects(
			$this->subjects,
			$this->getStore()->getQueryResult( $query )
		);

		$this->subjects[] = $property;
	}

}

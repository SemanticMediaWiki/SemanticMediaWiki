<?php

namespace SMW\Tests\Integration\Query;

use SMW\Tests\MwDBaseUnitTestCase;
use SMW\Tests\Util\SemanticDataFactory;
use SMW\Tests\Util\QueryResultValidator;

use SMW\DIWikiPage;
use SMW\DIProperty;
use SMW\DataValueFactory;

use SMWDIBlob as DIBlob;
use SMWDINumber as DINumber;
use SMWQuery as Query;
use SMWQueryResult as QueryResult;
use SMWDataValue as DataValue;
use SMWDataItem as DataItem;
use SMWSomeProperty as SomeProperty;
use SMWPrintRequest as PrintRequest;
use SMWPropertyValue as PropertyValue;
use SMWThingDescription as ThingDescription;

/**
 * @ingroup Test
 *
 * @group SMW
 * @group SMWExtension
 * @group semantic-mediawiki-integration
 * @group semantic-mediawiki-query
 * @group mediawiki-database
 * @group medium
 *
 * @license GNU GPL v2+
 * @since 2.0
 *
 * @author mwjames
 */
class SpecialCharactersQueryDBIntegrationTest extends MwDBaseUnitTestCase {

	protected $databaseToBeExcluded = array( 'sqlite' );

	private $subjectsToBeCleared = array();
	private $semanticDataFactory;
	private $dataValueFactory;
	private $queryResultValidator;

	protected function setUp() {
		parent::setUp();

		$this->dataValueFactory = DataValueFactory::getInstance();
		$this->semanticDataFactory = new SemanticDataFactory();
		$this->queryResultValidator = new QueryResultValidator();
	}

	protected function tearDown() {

		foreach ( $this->subjectsToBeCleared as $subject ) {
			$this->getStore()->deleteSubject( $subject->getTitle() );
		}

		parent::tearDown();
	}

	/**
	 * @dataProvider propertyProvider
	 */
	public function testBlobPropertyValueUsingSpecialCharaters( $property, $dataItem ) {

		$dataValue = $this->dataValueFactory->newDataItemValue(
			$dataItem,
			$property
		);

		$semanticData = $this->semanticDataFactory->newEmptySemanticData( '特殊文字のページ' );

		$semanticData->addDataValue( $dataValue );

		$this->getStore()->updateData( $semanticData );

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

		$this->queryResultValidator->assertThatQueryResultHasSubjects(
			$semanticData->getSubject(),
			$this->getStore()->getQueryResult( $query )
		);

		$this->queryResultValidator->assertThatQueryResultContains(
			$dataValue,
			$this->getStore()->getQueryResult( $query )
		);

		$this->subjectsToBeCleared = array(
			$semanticData->getSubject(),
			$property->getDIWikiPage()
		);
	}

	public function propertyProvider() {

		$provider[] = array(
			DIProperty::newFromUserLabel( '特殊文字' )->setPropertyTypeId( '_txt' ),
			new DIBlob( 'Nuñez' )
		);

		$provider[] = array(
			DIProperty::newFromUserLabel( '特殊字符' )->setPropertyTypeId( '_txt' ),
			new DIBlob( '^[0-9]*$' )
		);

		$provider[] = array(
			DIProperty::newFromUserLabel( 'Caractères spéciaux' )->setPropertyTypeId( '_wpg' ),
			new DIWikiPage( 'âêîôûëïçé', NS_MAIN, '' )
		);

		$provider[] = array(
			DIProperty::newFromUserLabel( 'áéíóúñÑü¡¿' )->setPropertyTypeId( '_num' ),
			new DINumber( 8888 )
		);

		return $provider;
	}

}

<?php

namespace SMW\Tests\Integration\Query;

use SMW\DataItems\Blob;
use SMW\DataItems\Number;
use SMW\DataItems\Property;
use SMW\DataItems\WikiPage;
use SMW\DataModel\Subobject;
use SMW\DataValueFactory;
use SMW\DataValues\PropertyValue;
use SMW\Query\Language\SomeProperty;
use SMW\Query\Language\ThingDescription;
use SMW\Query\PrintRequest;
use SMW\Query\Query;
use SMW\Tests\SMWIntegrationTestCase;
use SMW\Tests\Utils\UtilityFactory;

/**
 * @group SMW
 * @group SMWExtension
 *
 * @group semantic-mediawiki-integration
 * @group semantic-mediawiki-query
 *
 * @group mediawiki-database
 * @group Database
 * @group medium
 *
 * @license GPL-2.0-or-later
 * @since 2.0
 *
 * @author mwjames
 */
class SpecialCharactersQueryDBIntegrationTest extends SMWIntegrationTestCase {

	private $subjectsToBeCleared = [];
	private $semanticDataFactory;

	private $dataValueFactory;
	private $queryResultValidator;

	protected function setUp(): void {
		parent::setUp();

		$this->dataValueFactory = DataValueFactory::getInstance();
		$this->queryResultValidator = UtilityFactory::getInstance()->newValidatorFactory()->newQueryResultValidator();
		$this->semanticDataFactory = UtilityFactory::getInstance()->newSemanticDataFactory();
	}

	protected function tearDown(): void {
		foreach ( $this->subjectsToBeCleared as $subject ) {

			if ( $subject->getTitle() === null ) {
				continue;
			}

			$this->getStore()->deleteSubject( $subject->getTitle() );
		}

		parent::tearDown();
	}

	/**
	 * @dataProvider specialCharactersNameProvider
	 */
	public function testSpecialCharactersInQuery( $subject, $subobjectId, $property, $dataItem ) {
		$dataValue = $this->dataValueFactory->newDataValueByItem(
			$dataItem,
			$property
		);

		$semanticData = $this->semanticDataFactory->newEmptySemanticData( $subject );
		$semanticData->addDataValue( $dataValue );

		$subobject = new Subobject( $semanticData->getSubject()->getTitle() );
		$subobject->setEmptyContainerForId( $subobjectId );

		$subobject->addDataValue( $dataValue );
		$semanticData->addSubobject( $subobject );

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
			[
				$semanticData->getSubject(),
				$subobject->getSubject() ],
			$this->getStore()->getQueryResult( $query )
		);

		$this->queryResultValidator->assertThatQueryResultContains(
			$dataValue,
			$this->getStore()->getQueryResult( $query )
		);

		$this->subjectsToBeCleared = [
			$semanticData->getSubject(),
			$subobject->getSubject(),
			$property->getDIWikiPage()
		];
	}

	public function specialCharactersNameProvider() {
		$provider[] = [
			'特殊文字',
			'Nuñez',
			Property::newFromUserLabel( '特殊文字' )->setPropertyTypeId( '_txt' ),
			new Blob( 'Nuñez' )
		];

		$provider[] = [
			'特殊字符',
			'^[0-9]*$',
			Property::newFromUserLabel( '特殊字符' )->setPropertyTypeId( '_txt' ),
			new Blob( '^[0-9]*$' )
		];

		$provider[] = [
			'Caractères spéciaux',
			'Caractères_spéciaux',
			Property::newFromUserLabel( 'Caractères spéciaux' )->setPropertyTypeId( '_wpg' ),
			new WikiPage( 'âêîôûëïçé', NS_MAIN )
		];

		$provider[] = [
			'áéíóúñÑü¡¿',
			'áéíóúñÑü¡¿',
			Property::newFromUserLabel( 'áéíóúñÑü¡¿' )->setPropertyTypeId( '_num' ),
			new Number( 8888 )
		];

		$provider[] = [
			'Foo',
			'{({[[&,,;-]]})}',
			Property::newFromUserLabel( '{({[[&,,;-]]})}' )->setPropertyTypeId( '_wpg' ),
			new WikiPage( '{({[[&,,;-]]})}', NS_MAIN )
		];

		return $provider;
	}

}

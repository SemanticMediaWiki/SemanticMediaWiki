<?php

namespace SMW\Tests\Integration\Query;

use SMW\DataItems\Blob;
use SMW\DataItems\Property;
use SMW\DataItems\WikiPage;
use SMW\DataValueFactory;
use SMW\RequestOptions;
use SMW\StoreFactory;
use SMW\StringCondition;
use SMW\Tests\SMWIntegrationTestCase;

/**
 * @group semantic-mediawiki
 * @group Database
 *
 * @license GPL-2.0-or-later
 * @since 3.1
 *
 * @author mwjames
 */
class SemanticDataLookupTest extends SMWIntegrationTestCase {

	private $subjectsToBeCleared = [];

	private $dataValueFactory;
	private $semanticDataFactory;

	protected function setUp(): void {
		parent::setUp();

		$this->dataValueFactory = DataValueFactory::getInstance();
		$this->semanticDataFactory = $this->testEnvironment->getUtilityFactory()->newSemanticDataFactory();

		$this->testEnvironment->withConfiguration(
			[
				'smwgQueryResultCacheType' => false,
				'smwgEnabledFulltextSearch' => false
			]
		);
	}

	protected function tearDown(): void {
		foreach ( $this->subjectsToBeCleared as $subject ) {
			$this->getStore()->deleteSubject( $subject->getTitle() );
		}

		parent::tearDown();
	}

	public function testPropertyValueMatch_Wpg() {
		$store = StoreFactory::getStore();

		$semanticData = $this->semanticDataFactory->newEmptySemanticData(
			__METHOD__
		);

		$this->subjectsToBeCleared[] = $semanticData->getSubject();

		$property = new Property( 'SomeWpgPropertyToFilter' );
		$property->setPropertyTypeId( '_wpg' );

		$semanticData->addPropertyObjectValue( $property, WikiPage::newFromText( 'Bar' ) );
		$semanticData->addPropertyObjectValue( $property, WikiPage::newFromText( 'Foobar' ) );

		$store->updateData( $semanticData );

		$requestOptions = new RequestOptions();
		$requestOptions->setLimit( 10 );
		$requestOptions->setOffset( 0 );

		$requestOptions->addStringCondition( 'Ba', StringCondition::COND_MID );

		// add OR condition
		$requestOptions->addStringCondition( 'Lola', StringCondition::COND_MID, true );

		$results = $store->getPropertyValues( null, $property, $requestOptions );

		$this->assertTrue(
			end( $results )->equals( WikiPage::newFromText( 'Bar' ) )
		);
	}

	public function testPropertyValueMatch_Wpg_WithSubject() {
		$store = StoreFactory::getStore();

		$semanticData = $this->semanticDataFactory->newEmptySemanticData(
			__METHOD__
		);

		$subject = $semanticData->getSubject();
		$this->subjectsToBeCleared[] = $subject;

		$property = new Property( 'SomeWpgPropertyToFilter' );
		$property->setPropertyTypeId( '_wpg' );

		$semanticData->addPropertyObjectValue( $property, WikiPage::newFromText( 'Bar' ) );
		$semanticData->addPropertyObjectValue( $property, WikiPage::newFromText( 'Foobar' ) );

		$store->updateData( $semanticData );

		$requestOptions = new RequestOptions();
		$requestOptions->setLimit( 10 );
		$requestOptions->setOffset( 0 );

		$requestOptions->addStringCondition( 'Ba', StringCondition::COND_MID );

		$results = $store->getPropertyValues( $subject, $property, $requestOptions );

		$this->assertTrue(
			end( $results )->equals( WikiPage::newFromText( 'Bar' ) )
		);
	}

	public function testPropertyValueMatch_Wpg_Sorted() {
		$store = StoreFactory::getStore();

		$semanticData = $this->semanticDataFactory->newEmptySemanticData(
			__METHOD__
		);

		$this->subjectsToBeCleared[] = $semanticData->getSubject();

		$property = new Property( 'SomeWpgPropertySortedFilter' );
		$property->setPropertyTypeId( '_wpg' );

		$semanticData->addPropertyObjectValue( $property, WikiPage::newFromText( 'FooBar' ) );
		$semanticData->addPropertyObjectValue( $property, WikiPage::newFromText( 'Bar_9' ) );
		$semanticData->addPropertyObjectValue( $property, WikiPage::newFromText( 'Bar_5' ) );
		$semanticData->addPropertyObjectValue( $property, WikiPage::newFromText( 'Bar_1' ) );

		$store->updateData( $semanticData );

		$requestOptions = new RequestOptions();
		$requestOptions->sort = true;
		$requestOptions->ascending = false;
		$requestOptions->setLimit( 10 );
		$requestOptions->setOffset( 0 );

		$requestOptions->addStringCondition( 'Ba', StringCondition::COND_MID );

		$results = $store->getPropertyValues( null, $property, $requestOptions );

		$expected = [
			WikiPage::newFromText( 'FooBar' ),
			WikiPage::newFromText( 'Bar_9' ),
			WikiPage::newFromText( 'Bar_5' ),
			WikiPage::newFromText( 'Bar_1' ),
		];

		foreach ( $expected as $subject ) {
			$subject->setSortKey( $subject->getDBKey() );
		}

		$this->assertEquals(
			$expected,
			$results
		);
	}

	public function testPropertyValueMatch_Txt() {
		$store = StoreFactory::getStore();

		$semanticData = $this->semanticDataFactory->newEmptySemanticData(
			__METHOD__
		);

		$this->subjectsToBeCleared[] = $semanticData->getSubject();

		$property = new Property( 'SomeBlobPropertyToFilter' );
		$property->setPropertyTypeId( '_txt' );

		$semanticData->addPropertyObjectValue( $property, new Blob( 'testfoobar' ) );

		$store->updateData( $semanticData );

		$requestOptions = new RequestOptions();
		$requestOptions->sort = true;
		$requestOptions->setLimit( 10 );
		$requestOptions->setOffset( 0 );

		$requestOptions->addStringCondition( 'foo', StringCondition::COND_MID );

		// add OR condition
		$requestOptions->addStringCondition( 'Lola', StringCondition::COND_MID, true );

		$results = $store->getPropertyValues( null, $property, $requestOptions );

		$this->assertTrue(
			end( $results )->equals( new Blob( 'testfoobar' ) )
		);
	}

}

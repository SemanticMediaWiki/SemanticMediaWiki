<?php

namespace SMW\Tests\Integration\Query;

use SMW\DataValueFactory;
use SMW\DIProperty;
use SMW\DIWikiPage;
use SMW\StringCondition;
use SMW\RequestOptions;
use SMW\StoreFactory;
use SMWQuery as Query;
use SMWDIBlob as DIBlob;
use SMW\Tests\DatabaseTestCase;

/**
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.1
 *
 * @author mwjames
 */
class SemanticDataLookupTest extends DatabaseTestCase {

	private $subjectsToBeCleared = [];
	private $subject;
	private $dataValueFactory;
	private $queryResultValidator;

	protected function setUp() {
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

	protected function tearDown() {

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

		$property = new DIProperty( 'SomeWpgPropertyToFilter' );
		$property->setPropertyTypeId( '_wpg' );

		$semanticData->addPropertyObjectValue( $property, DIWikiPage::newFromText( 'Bar' ) );
		$semanticData->addPropertyObjectValue( $property, DIWikiPage::newFromText( 'Foobar' ) );

		$store->updateData( $semanticData );

		$requestOptions = new RequestOptions();
		$requestOptions->sort = true;
		$requestOptions->setLimit( 10 );
		$requestOptions->setOffset( 0 );

		$requestOptions->addStringCondition( 'Ba', StringCondition::COND_MID );

		// add OR condition
		$requestOptions->addStringCondition( 'Lola', StringCondition::COND_MID, true );

		$results = $store->getPropertyValues( null, $property, $requestOptions );

		$this->assertTrue(
			end( $results )->equals( DIWikiPage::newFromText( 'Bar' ) )
		);
	}

	public function testPropertyValueMatch_Txt() {

		$store = StoreFactory::getStore();

		$semanticData = $this->semanticDataFactory->newEmptySemanticData(
			__METHOD__
		);

		$this->subjectsToBeCleared[] = $semanticData->getSubject();

		$property = new DIProperty( 'SomeBlobPropertyToFilter' );
		$property->setPropertyTypeId( '_txt' );

		$semanticData->addPropertyObjectValue( $property, new DIBlob( 'testfoobar' ) );

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
			end( $results )->equals( new DIBlob( 'testfoobar' ) )
		);
	}

}

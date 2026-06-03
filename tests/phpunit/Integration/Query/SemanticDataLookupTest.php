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
		$property->setPropertyValueType( '_wpg' );

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
		$property->setPropertyValueType( '_wpg' );

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
		$property->setPropertyValueType( '_wpg' );

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
		$property->setPropertyValueType( '_txt' );

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

	public function testPropertyValueMatch_Txt_ShortValuesDistinctAcrossSubjects() {
		$store = StoreFactory::getStore();

		$property = new Property( 'SomeShortBlobValuesProperty' );
		$property->setPropertyValueType( '_txt' );

		// Several subjects sharing a small set of distinct short (<= 72 byte)
		// values: the "few distinct values, many subjects" shape behind the
		// slow Special:PageProperty value listing.
		$values = [ 'alpha', 'beta', 'alpha', 'gamma', 'beta' ];

		foreach ( $values as $i => $value ) {
			$semanticData = $this->semanticDataFactory->newEmptySemanticData( 'ShortBlobSubject' . $i );
			$this->subjectsToBeCleared[] = $semanticData->getSubject();
			$semanticData->addPropertyObjectValue( $property, new Blob( $value ) );
			$store->updateData( $semanticData );
		}

		$requestOptions = new RequestOptions();
		$requestOptions->setLimit( 10 );
		$requestOptions->setOffset( 0 );

		$results = $store->getPropertyValues( null, $property, $requestOptions );

		$strings = array_map( static fn ( $di ) => $di->getString(), $results );
		sort( $strings );

		$this->assertEquals(
			[ 'alpha', 'beta', 'gamma' ],
			$strings
		);
	}

	public function testPropertyValueMatch_Txt_LongValueIsReconstructed() {
		$store = StoreFactory::getStore();

		$property = new Property( 'SomeLongBlobValueProperty' );
		$property->setPropertyValueType( '_txt' );

		// > 72 bytes, so it is stored in o_blob with a truncated + hashed
		// o_hash; the full value must still be reconstructed on read.
		$longValue = str_repeat( 'abcdefghij', 12 );

		$semanticData = $this->semanticDataFactory->newEmptySemanticData( __METHOD__ );
		$this->subjectsToBeCleared[] = $semanticData->getSubject();
		$semanticData->addPropertyObjectValue( $property, new Blob( $longValue ) );
		$store->updateData( $semanticData );

		$requestOptions = new RequestOptions();
		$requestOptions->setLimit( 10 );

		$results = $store->getPropertyValues( null, $property, $requestOptions );

		$this->assertCount( 1, $results );
		$this->assertSame( $longValue, end( $results )->getString() );
	}

	public function testPropertyValueMatch_Keyword_CaseVariantsRemainDistinct() {
		$store = StoreFactory::getStore();

		$property = new Property( 'SomeKeywordValuesProperty' );
		$property->setPropertyValueType( '_keyw' );

		// Keyword values normalise (lowercase, transliterate) to the same
		// o_hash while keeping a distinct o_blob, so listing must preserve
		// the case variants as distinct values.
		$values = [ 'Foo', 'foo', 'FOO' ];

		foreach ( $values as $i => $value ) {
			$semanticData = $this->semanticDataFactory->newEmptySemanticData( 'KeywordSubject' . $i );
			$this->subjectsToBeCleared[] = $semanticData->getSubject();

			$dataValue = $this->dataValueFactory->newDataValueByProperty( $property, $value );
			$semanticData->addPropertyObjectValue( $property, $dataValue->getDataItem() );
			$store->updateData( $semanticData );
		}

		$requestOptions = new RequestOptions();
		$requestOptions->setLimit( 10 );

		$results = $store->getPropertyValues( null, $property, $requestOptions );

		$strings = array_map( static fn ( $di ) => $di->getString(), $results );
		sort( $strings );

		$this->assertEquals(
			[ 'FOO', 'Foo', 'foo' ],
			$strings
		);
	}

	public function testPropertyValueMatch_Txt_MixedShortAndLongValues() {
		$store = StoreFactory::getStore();

		$property = new Property( 'SomeMixedBlobValuesProperty' );
		$property->setPropertyValueType( '_txt' );

		// A single property holding both inline (<= 72 byte, o_blob NULL) and
		// long (> 72 byte, o_blob set) distinct values, so the reconstruction
		// exercises the per-value blob fetch for some values but not others
		// within one result set.
		$short1 = 'cat';
		$short2 = 'dog';
		$long1 = str_repeat( 'long-value-one-', 8 );
		$long2 = str_repeat( 'long-value-two-', 8 );

		$values = [ $short1, $long1, $short2, $long2, $short1, $long1 ];

		foreach ( $values as $i => $value ) {
			$semanticData = $this->semanticDataFactory->newEmptySemanticData( 'MixedBlobSubject' . $i );
			$this->subjectsToBeCleared[] = $semanticData->getSubject();
			$semanticData->addPropertyObjectValue( $property, new Blob( $value ) );
			$store->updateData( $semanticData );
		}

		$requestOptions = new RequestOptions();
		$requestOptions->setLimit( 10 );

		$results = $store->getPropertyValues( null, $property, $requestOptions );

		$strings = array_map( static fn ( $di ) => $di->getString(), $results );
		sort( $strings );

		$expected = [ $short1, $short2, $long1, $long2 ];
		sort( $expected );

		$this->assertEquals( $expected, $strings );
	}

	public function testPropertyValueMatch_Txt_LimitBoundsTheResultSet() {
		$store = StoreFactory::getStore();

		$property = new Property( 'SomeLimitedBlobValuesProperty' );
		$property->setPropertyValueType( '_txt' );

		$values = [ 'one', 'two', 'three', 'four', 'five' ];

		foreach ( $values as $i => $value ) {
			$semanticData = $this->semanticDataFactory->newEmptySemanticData( 'LimitedBlobSubject' . $i );
			$this->subjectsToBeCleared[] = $semanticData->getSubject();
			$semanticData->addPropertyObjectValue( $property, new Blob( $value ) );
			$store->updateData( $semanticData );
		}

		$requestOptions = new RequestOptions();
		$requestOptions->setLimit( 2 );

		$results = $store->getPropertyValues( null, $property, $requestOptions );

		$this->assertCount( 2, $results );

		foreach ( $results as $result ) {
			$this->assertContains( $result->getString(), $values );
		}
	}

}

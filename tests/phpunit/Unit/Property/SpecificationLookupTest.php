<?php

namespace SMW\Tests\Unit\Property;

use PHPUnit\Framework\TestCase;
use SMW\DataItemFactory;
use SMW\DataItems\DataItem;
use SMW\DataItems\Property;
use SMW\DataValues\DataValue;
use SMW\DataValues\MonolingualTextValue;
use SMW\DataValues\StringValue;
use SMW\Property\LanguageFalldownAndInverse;
use SMW\Property\SpecificationLookup;
use SMW\SQLStore\Lookup\MonolingualTextLookup;
use SMW\Store;
use SMW\Tests\TestEnvironment;
use SMW\Tests\Utils\EntityCacheFactory;

/**
 * @covers \SMW\Property\SpecificationLookup
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 2.4
 *
 * @author mwjames
 * @author thomas-topway-it
 */
class SpecificationLookupTest extends TestCase {

	private $monolingualTextLookup;
	private $dataItemFactory;
	private $testEnvironment;
	private $store;
	private $entityCache;

	protected function setUp(): void {
		parent::setUp();

		$this->store = $this->getMockBuilder( Store::class )
			->disableOriginalConstructor()
			->setMethods( [ 'service' ] )
			->getMockForAbstractClass();

		$this->entityCache = EntityCacheFactory::newEntityCache();

		$this->monolingualTextLookup = $this->getMockBuilder( MonolingualTextLookup::class )
			->disableOriginalConstructor()
			->getMock();

		$this->dataItemFactory = new DataItemFactory();
		$this->testEnvironment = new TestEnvironment();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			SpecificationLookup::class,
			new SpecificationLookup( $this->store, $this->entityCache )
		);
	}

	// @see https://github.com/SemanticMediaWiki/SemanticMediaWiki/issues/5342
	public function testTryOutFalldownAndInverse() {
		$property = $this->dataItemFactory->newDIProperty( '_PDESC' );
		$subject = $property->getDiWikiPage();

		$deDataValue = $this->createMock( DataValue::class );
		$deFormalDataValue = $this->createMock( DataValue::class );
		$enDataValue = $this->createMock( DataValue::class );

		$this->monolingualTextLookup->expects( $this->any() )
			->method( 'newDataValue' )
			->with(
				$subject,
				$property,
				$this->anything() )

			->willReturnCallback( static function () use ( $deDataValue, $deFormalDataValue, $enDataValue ) {
				$args = func_get_args();
				switch ( $args[2] ) {
					case 'de':
						return $deDataValue;
					case 'de-formal':
						return $deFormalDataValue;
					case 'en':
						return $enDataValue;
				}
				return null;
			} );

		$instance = new SpecificationLookup(
			$this->store,
			$this->entityCache
		);

		$languageCode = 'de-formal';
		$languageFalldownAndInverse = new LanguageFalldownAndInverse( $this->monolingualTextLookup, $subject, $property, $languageCode );

		// #1 will return languageFalldown
		$result = $languageFalldownAndInverse->tryout();
		$this->assertSame( $deDataValue, $result[0] );
		$this->assertSame( 'de', $result[1] );

		$languageCode = 'de';
		$languageFalldownAndInverse = new LanguageFalldownAndInverse( $this->monolingualTextLookup, $subject, $property, $languageCode );

		// #2 will return the first existing FallbackInverse
		// (de-formal from de)
		$result = $languageFalldownAndInverse->tryout();
		$this->assertSame( $deFormalDataValue, $result[0] );
		$this->assertSame( 'de-formal', $result[1] );

		$languageCode = 'en';
		$languageFalldownAndInverse = new LanguageFalldownAndInverse( $this->monolingualTextLookup, $subject, $property, $languageCode );

		// #3 will return null
		$this->assertEquals(
			[ null, $GLOBALS['wgLanguageCode'] ],
			$languageFalldownAndInverse->tryout()
		);
	}

	public function testGetSpecificationReadsTheStoreOnlyOnce() {
		$property = $this->dataItemFactory->newDIProperty( 'Foo' );

		$this->store->expects( $this->once() )
			->method( 'getPropertyValues' )
			->willReturn( [ $this->dataItemFactory->newDIBlob( 'Bar' ) ] );

		$instance = new SpecificationLookup(
			$this->store,
			$this->entityCache
		);

		$instance->getSpecification( $property, $this->dataItemFactory->newDIProperty( 'Bar' ) );

		$this->assertEquals(
			[ $this->dataItemFactory->newDIBlob( 'Bar' ) ],
			$instance->getSpecification( $property, $this->dataItemFactory->newDIProperty( 'Bar' ) )
		);
	}

	public function testSpecificationsOfDifferentAttributesAreCachedSeparately() {
		$property = $this->dataItemFactory->newDIProperty( 'Foo' );

		$this->store->expects( $this->any() )
			->method( 'getPropertyValues' )
			->willReturnCallback( fn ( $subject, Property $target ) => [ $this->dataItemFactory->newDIBlob( $target->getKey() ) ] );

		$instance = new SpecificationLookup(
			$this->store,
			$this->entityCache
		);

		$instance->getSpecification( $property, new Property( '_PVAL' ) );

		$this->assertEquals(
			[ $this->dataItemFactory->newDIBlob( '_PVAP' ) ],
			$instance->getSpecification( $property, new Property( '_PVAP' ) )
		);
	}

	public function testGetSpecification_SkipCache() {
		$property = $this->dataItemFactory->newDIProperty( 'Foo' );

		$this->store->expects( $this->exactly( 2 ) )
			->method( 'getPropertyValues' )
			->with(
				$property->getDiWikiPage(),
				$this->dataItemFactory->newDIProperty( 'Bar' ),
				$this->anything() );

		$instance = new SpecificationLookup(
			$this->store,
			$this->entityCache
		);

		$instance->skipCache();

		$instance->getSpecification( $property, $this->dataItemFactory->newDIProperty( 'Bar' ) );
		$instance->getSpecification( $property, $this->dataItemFactory->newDIProperty( 'Bar' ) );
	}

	public function testGetFieldList() {
		$property = $this->dataItemFactory->newDIProperty( 'RecordProperty' );

		$this->store->expects( $this->once() )
			->method( 'getPropertyValues' )
			->with(
				$property->getDiWikiPage(),
				$this->dataItemFactory->newDIProperty( '_LIST' ),
				$this->anything() )
			->willReturn( [
					$this->dataItemFactory->newDIBlob( 'Foo' ),
					$this->dataItemFactory->newDIBlob( 'abc;123' ) ] );

		$instance = new SpecificationLookup(
			$this->store,
			$this->entityCache
		);

		$this->assertEquals(
			'abc;123',
			$instance->getFieldListBy( $property )
		);
	}

	public function testGetPreferredPropertyLabel() {
		$property = $this->dataItemFactory->newDIProperty( 'SomeProperty' );
		$property->setPropertyValueType( '_mlt_rec' );

		$this->store->expects( $this->once() )
			->method( 'service' )
			->willReturn( $this->monolingualTextLookup );

		$this->monolingualTextLookup->expects( $this->once() )
			->method( 'newDataValue' )
			->with(
				$property->getDiWikiPage(),
				$this->dataItemFactory->newDIProperty( '_PPLB' ),
				$this->anything() );

		$instance = new SpecificationLookup(
			$this->store,
			$this->entityCache
		);

		$this->assertSame(
			'',
			$instance->getPreferredPropertyLabelByLanguageCode( $property )
		);
	}

	public function testHasUniquenessConstraint() {
		$property = $this->dataItemFactory->newDIProperty( 'Foo' );

		$this->store->expects( $this->once() )
			->method( 'getPropertyValues' )
			->with(
				$property->getDiWikiPage(),
				$this->dataItemFactory->newDIProperty( '_PVUC' ),
				$this->anything() )
			->willReturn( [ $this->dataItemFactory->newDIBoolean( true ) ] );

		$instance = new SpecificationLookup(
			$this->store,
			$this->entityCache
		);

		$this->assertTrue(
			$instance->hasUniquenessConstraint( $property )
		);
	}

	public function testGetExternalFormatterUri() {
		$property = $this->dataItemFactory->newDIProperty( 'Foo' );

		$this->store->expects( $this->once() )
			->method( 'getPropertyValues' )
			->with(
				$property->getDiWikiPage(),
				$this->dataItemFactory->newDIProperty( '_PEFU' ),
				$this->anything() )
			->willReturn( [ $this->dataItemFactory->newDIUri( 'http', 'example.org/$1' ) ] );

		$instance = new SpecificationLookup(
			$this->store,
			$this->entityCache
		);

		$this->assertInstanceOf(
			DataItem::class,
			$instance->getExternalFormatterUri( $property )
		);
	}

	public function testGetAllowedPattern() {
		$property = $this->dataItemFactory->newDIProperty( 'Has allowed pattern' );

		$this->store->expects( $this->once() )
			->method( 'getPropertyValues' )
			->with(
				$property->getDiWikiPage(),
				$this->dataItemFactory->newDIProperty( '_PVAP' ),
				$this->anything() )
			->willReturn( [ $this->dataItemFactory->newDIBlob( 'IPv4' ) ] );

		$instance = new SpecificationLookup(
			$this->store,
			$this->entityCache
		);

		$this->assertEquals(
			'IPv4',
			$instance->getAllowedPatternBy( $property )
		);
	}

	public function testGetAllowedListValueBy() {
		$property = $this->dataItemFactory->newDIProperty( 'Has list' );

		$this->store->expects( $this->once() )
			->method( 'getPropertyValues' )
			->with(
				$property->getDiWikiPage(),
				$this->dataItemFactory->newDIProperty( '_PVALI' ),
				$this->anything() )
			->willReturn( [ $this->dataItemFactory->newDIBlob( 'Foo' ) ] );

		$instance = new SpecificationLookup(
			$this->store,
			$this->entityCache
		);

		$this->assertEquals(
			[ 'Foo' ],
			$instance->getAllowedListValues( $property )
		);
	}

	public function testGetAllowedValues() {
		$expected = [
			$this->dataItemFactory->newDIBlob( 'A' ),
			$this->dataItemFactory->newDIBlob( 'B' )
		];

		$property = $this->dataItemFactory->newDIProperty( 'Has allowed values' );

		$this->store->expects( $this->once() )
			->method( 'getPropertyValues' )
			->with(
				$property->getDiWikiPage(),
				$this->dataItemFactory->newDIProperty( '_PVAL' ),
				$this->anything() )
			->willReturn( $expected );

		$instance = new SpecificationLookup(
			$this->store,
			$this->entityCache
		);

		$this->assertEquals(
			$expected,
			$instance->getAllowedValues( $property )
		);
	}

	public function testGetDisplayPrecision() {
		$property = $this->dataItemFactory->newDIProperty( 'Foo' );

		$this->store->expects( $this->once() )
			->method( 'getPropertyValues' )
			->with(
				$property->getDiWikiPage(),
				$this->dataItemFactory->newDIProperty( '_PREC' ),
				$this->anything() )
			->willReturn( [ $this->dataItemFactory->newDINumber( -2.3 ) ] );

		$instance = new SpecificationLookup(
			$this->store,
			$this->entityCache
		);

		$this->assertEquals(
			2,
			$instance->getDisplayPrecision( $property )
		);
	}

	public function testgetDisplayUnits() {
		$property = $this->dataItemFactory->newDIProperty( 'Foo' );

		$this->store->expects( $this->once() )
			->method( 'getPropertyValues' )
			->with(
				$property->getDiWikiPage(),
				$this->dataItemFactory->newDIProperty( '_UNIT' ),
				$this->anything() )
			->willReturn( [
				$this->dataItemFactory->newDIBlob( 'abc,def' ),
				$this->dataItemFactory->newDIBlob( '123' ) ] );

		$instance = new SpecificationLookup(
			$this->store,
			$this->entityCache
		);

		$this->assertEquals(
			[ 'abc', 'def', '123' ],
			$instance->getDisplayUnits( $property )
		);
	}

	public function testGetPropertyDescriptionForPredefinedProperty() {
		$property = $this->dataItemFactory->newDIProperty( 'Foo' );

		$this->store->expects( $this->once() )
			->method( 'service' )
			->willReturn( $this->monolingualTextLookup );

		$this->monolingualTextLookup->expects( $this->once() )
			->method( 'newDataValue' )
			->with(
				$property->getDiWikiPage(),
				$this->dataItemFactory->newDIProperty( '_PDESC' ),
				$this->anything() );

		$instance = new SpecificationLookup(
			$this->store,
			$this->entityCache
		);

		$this->assertIsString(

			$instance->getPropertyDescriptionByLanguageCode( $property )
		);
	}

	public function testPropertyDescriptionIsCachedPerLanguageCode() {
		$property = $this->dataItemFactory->newDIProperty( 'Foo' );
		$this->givenTextsThatCountTheirLookUps();

		$instance = new SpecificationLookup(
			$this->store,
			$this->entityCache
		);

		$this->assertSame(
			[ "''en 1''", "''de 1''", "''en 1''" ],
			[
				$instance->getPropertyDescriptionByLanguageCode( $property, 'en' ),
				$instance->getPropertyDescriptionByLanguageCode( $property, 'de' ),
				$instance->getPropertyDescriptionByLanguageCode( $property, 'en' )
			]
		);
	}

	public function testPlainAndHtmlPropertyDescriptionsAreCachedSeparately() {
		$property = $this->dataItemFactory->newDIProperty( 'Foo' );
		$this->givenTextsThatCountTheirLookUps();

		$instance = new SpecificationLookup(
			$this->store,
			$this->entityCache
		);

		$instance->getPropertyDescriptionByLanguageCode( $property, 'en' );

		$this->assertStringNotContainsString(
			"''",
			$instance->getPropertyDescriptionAsHtml( $property, 'en' )
		);
	}

	public function testPreferredPropertyLabelIsCachedPerLanguageCode() {
		$property = $this->dataItemFactory->newDIProperty( 'Foo' );
		$this->givenTextsThatCountTheirLookUps();

		$instance = new SpecificationLookup(
			$this->store,
			$this->entityCache
		);

		$this->assertSame(
			[ "''en 1''", "''de 1''", "''en 1''" ],
			[
				$instance->getPreferredPropertyLabelByLanguageCode( $property, 'en' ),
				$instance->getPreferredPropertyLabelByLanguageCode( $property, 'de' ),
				$instance->getPreferredPropertyLabelByLanguageCode( $property, 'en' )
			]
		);
	}

	/**
	 * Each text names its language and how often it was looked up, in wikitext
	 */
	private function givenTextsThatCountTheirLookUps(): void {
		$lookUps = [];

		$this->monolingualTextLookup->expects( $this->any() )
			->method( 'newDataValue' )
			->willReturnCallback( function ( $subject, $property, $languageCode ) use ( &$lookUps ) {
				$lookUps[$languageCode] = ( $lookUps[$languageCode] ?? 0 ) + 1;

				$text = $this->createMock( StringValue::class );
				$text->method( 'getShortWikiText' )->willReturn( "''$languageCode {$lookUps[$languageCode]}''" );

				$value = $this->createMock( MonolingualTextValue::class );
				$value->method( 'getTextValueByLanguageCode' )->willReturn( $text );

				return $value;
			} );

		$this->store->expects( $this->any() )
			->method( 'service' )
			->willReturn( $this->monolingualTextLookup );
	}

	public function testTryToGetLocalPropertyDescriptionForUserdefinedProperty() {
		$stringValue = $this->getMockBuilder( StringValue::class )
			->disableOriginalConstructor()
			->getMock();

		$monolingualTextValue = $this->getMockBuilder( MonolingualTextValue::class )
			->disableOriginalConstructor()
			->getMock();

		$monolingualTextValue->expects( $this->once() )
			->method( 'getTextValueByLanguageCode' )
			->with(	'foo' )
			->willReturn( $stringValue );

		$property = $this->dataItemFactory->newDIProperty( 'Foo' );

		$this->store->expects( $this->once() )
			->method( 'service' )
			->willReturn( $this->monolingualTextLookup );

		$this->monolingualTextLookup->expects( $this->once() )
			->method( 'newDataValue' )
			->with(
				$property->getDiWikiPage(),
				$this->anything(),
				$this->anything() )
			->willReturn( $monolingualTextValue );

		$instance = new SpecificationLookup(
			$this->store,
			$this->entityCache
		);

		$this->assertIsString(

			$instance->getPropertyDescriptionByLanguageCode( $property, 'foo' )
		);
	}

	public function testGetPropertyGroup() {
		$property = $this->dataItemFactory->newDIProperty( 'Foo' );
		$ppgr = $this->dataItemFactory->newDIProperty( '_PPGR' );

		$dataItem = $this->dataItemFactory->newDIWikiPage( 'Bar', NS_CATEGORY );
		$bool = $this->dataItemFactory->newDIBoolean( true );

		$callCount = 0;
		$this->store->expects( $this->exactly( 2 ) )
			->method( 'getPropertyValues' )
			->willReturnCallback( static function () use ( &$callCount, $dataItem, $bool ) {
				return [ [ $dataItem ], [ $bool ] ][$callCount++];
			} );

		$instance = new SpecificationLookup(
			$this->store,
			$this->entityCache
		);

		$this->assertEquals(
			$dataItem,
			$instance->getPropertyGroup( $property )
		);
	}

	public function testHasNoUniquenessConstraintWhenItIsSetToFalse() {
		$this->store->expects( $this->any() )
			->method( 'getPropertyValues' )
			->willReturn( [ $this->dataItemFactory->newDIBoolean( false ) ] );

		$instance = new SpecificationLookup( $this->store, $this->entityCache );

		$this->assertFalse( $instance->hasUniquenessConstraint( $this->dataItemFactory->newDIProperty( 'Foo' ) ) );
	}

	public function testPredefinedPropertyWithoutLocalDescriptionGetsItsSystemDescription() {
		$this->store->expects( $this->any() )
			->method( 'service' )
			->willReturn( $this->monolingualTextLookup );

		$instance = new SpecificationLookup( $this->store, $this->entityCache );

		$this->assertNotSame(
			'',
			$instance->getPropertyDescriptionByLanguageCode( $this->dataItemFactory->newDIProperty( '_MDAT' ), 'en' )
		);
	}

	public function testLastAllowedPatternApplies() {
		$this->store->expects( $this->any() )
			->method( 'getPropertyValues' )
			->willReturn( [ $this->dataItemFactory->newDIBlob( 'first' ), $this->dataItemFactory->newDIBlob( 'last' ) ] );

		$instance = new SpecificationLookup( $this->store, $this->entityCache );

		$this->assertSame( 'last', $instance->getAllowedPatternBy( $this->dataItemFactory->newDIProperty( 'Foo' ) ) );
	}

	public function testInvalidateCacheMakesTheNextLookUpReadTheStore() {
		$property = $this->dataItemFactory->newDIProperty( 'Foo' );
		$target = $this->dataItemFactory->newDIProperty( 'Bar' );

		$this->store->expects( $this->exactly( 2 ) )
			->method( 'getPropertyValues' )
			->willReturnOnConsecutiveCalls(
				[ $this->dataItemFactory->newDIBlob( 'Outdated' ) ],
				[ $this->dataItemFactory->newDIBlob( 'Current' ) ]
			);

		$instance = new SpecificationLookup(
			$this->store,
			$this->entityCache
		);

		$instance->getSpecification( $property, $target );
		$instance->invalidateCache( $property->getDiWikiPage() );

		$this->assertEquals(
			[ $this->dataItemFactory->newDIBlob( 'Current' ) ],
			$instance->getSpecification( $property, $target )
		);
	}

}

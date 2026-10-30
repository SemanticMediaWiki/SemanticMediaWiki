<?php

namespace SMW\Tests\Unit;

use PHPUnit\Framework\TestCase;
use SMW\DataItems\Blob;
use SMW\DataItems\Property;
use SMW\DataItems\WikiPage;
use SMW\DataModel\SemanticData;
use SMW\DisplayTitleFinder;
use SMW\Services\Exception\ServiceNotFoundException;
use SMW\SQLStore\Lookup\DisplayTitleLookup;
use SMW\Store;
use SMW\Tests\Utils\EntityCacheFactory;

/**
 * @covers \SMW\DisplayTitleFinder
 * @group semantic-mediawiki
 * @group Database
 *
 * @license GPL-2.0-or-later
 * @since   3.1
 *
 * @author mwjames
 */
class DisplayTitleFinderTest extends TestCase {

	private $store;
	private $entityCache;

	protected function setUp(): void {
		$this->store = $this->getMockBuilder( Store::class )
			->disableOriginalConstructor()
			->setMethods( [ 'getWikiPageSortKey', 'service' ] )
			->getMockForAbstractClass();

		$this->entityCache = EntityCacheFactory::newEntityCache();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			DisplayTitleFinder::class,
			new DisplayTitleFinder( $this->store, $this->entityCache )
		);
	}

	public function testFindDisplayTitle_WithoutSubobject() {
		$subject = WikiPage::newFromText( 'Foo' );

		$this->store->expects( $this->once() )
			->method( 'getPropertyValues' )
			->with( $subject )
			->willReturn( [ new Blob( 'Bar' ) ] );

		$instance = new DisplayTitleFinder(
			$this->store,
			$this->entityCache
		);

		$this->assertSame(
			'Bar',
			$instance->findDisplayTitle( $subject )
		);
	}

	public function testFindDisplayTitle_WithSubobject() {
		$subject = new WikiPage( 'Foo', NS_MAIN, '', 'abc' );

		$this->store->expects( $this->any() )
			->method( 'getPropertyValues' )
			->withConsecutive(
				[ $this->equalTo( $subject ) ],
				[ $this->equalTo( $subject->asBase() ) ] )
			->willReturnOnConsecutiveCalls( [], [ new Blob( 'foobar' ) ] );

		$instance = new DisplayTitleFinder(
			$this->store,
			$this->entityCache
		);

		$this->assertSame(
			'foobar',
			$instance->findDisplayTitle( $subject )
		);
	}

	public function testNoDisplayTitle_Empty() {
		$subject = new WikiPage( 'Foo', NS_MAIN, '', 'abc' );

		$this->store->expects( $this->any() )
			->method( 'getPropertyValues' )
			->withConsecutive(
				[ $this->equalTo( $subject ) ],
				[ $this->equalTo( $subject->asBase() ) ] )
			->willReturnOnConsecutiveCalls( [], [] );

		$instance = new DisplayTitleFinder(
			$this->store,
			$this->entityCache
		);

		// Trimmed on the output
		$this->assertSame(
			'',
			$instance->findDisplayTitle( $subject )
		);
	}

	public function testPrefetchFromSemanticDataCoversSubjectsValuesAndSubobjects() {
		$subSemanticData = $this->createMock( SemanticData::class );
		$subSemanticData->method( 'getSubject' )->willReturn( WikiPage::doUnserialize( 'Foo#0##123' ) );
		$subSemanticData->method( 'getProperties' )->willReturn( [ new Property( 'SubFoo' ) ] );
		$subSemanticData->method( 'getPropertyValues' )->willReturn( [ WikiPage::newFromText( 'SubValue' ) ] );

		$semanticData = $this->createMock( SemanticData::class );
		$semanticData->method( 'getSubject' )->willReturn( WikiPage::newFromText( 'Bar' ) );
		$semanticData->method( 'getProperties' )->willReturn( [ new Property( 'Foo' ) ] );
		$semanticData->method( 'getPropertyValues' )->willReturn( [ WikiPage::newFromText( 'Value' ) ] );
		$semanticData->method( 'getSubSemanticData' )->willReturn( [ $subSemanticData ] );

		$bar = WikiPage::newFromText( 'Bar' );
		$value = WikiPage::newFromText( 'Value' );
		$subobject = WikiPage::doUnserialize( 'Foo#0##123' );
		$subValue = WikiPage::newFromText( 'SubValue' );

		$this->givenPrefetchedDisplayTitles( [
			WikiPage::newFromText( 'Foo' )->getSha1() => null,
			$bar->getSha1() => 'Title of Bar',
			$value->getSha1() => 'Title of Value',
			$subobject->getSha1() => 'Title of the subobject',
			$subValue->getSha1() => 'Title of SubValue',
		] );

		$this->store->expects( $this->never() )->method( 'getPropertyValues' );

		$instance = new DisplayTitleFinder( $this->store, $this->entityCache );
		$instance->prefetchFromSemanticData( $semanticData );

		$this->assertSame(
			[ 'Title of Bar', 'Title of Value', 'Title of the subobject', 'Title of SubValue' ],
			[
				$instance->findDisplayTitle( $bar ),
				$instance->findDisplayTitle( $value ),
				$instance->findDisplayTitle( $subobject ),
				$instance->findDisplayTitle( $subValue ),
			]
		);
	}

	public function testPageWithoutADisplayTitleFoundByAPrefetchDoesNotReadTheStore() {
		$subject = WikiPage::newFromText( 'Foo' );
		$this->givenPrefetchedDisplayTitles( [ $subject->getSha1() => null ] );
		$this->store->expects( $this->never() )->method( 'getPropertyValues' );

		$instance = new DisplayTitleFinder( $this->store, $this->entityCache );
		$instance->prefetchFromList( [ $subject ] );

		$this->assertSame( '', $instance->findDisplayTitle( $subject ) );
	}

	public function testPageAbsentFromAPrefetchReadsTheStore() {
		$subject = WikiPage::newFromText( 'Foo' );
		$this->givenPrefetchedDisplayTitles( [] );
		$this->store->expects( $this->any() )
			->method( 'getPropertyValues' )
			->willReturn( [ new Blob( 'Bar' ) ] );

		$instance = new DisplayTitleFinder( $this->store, $this->entityCache );
		$instance->prefetchFromList( [ $subject ] );

		$this->assertSame( 'Bar', $instance->findDisplayTitle( $subject ) );
	}

	public function testPrefetchWithoutALookupServiceReadsTheStore() {
		$subject = WikiPage::newFromText( 'Foo' );
		$this->store->expects( $this->any() )
			->method( 'service' )
			->willThrowException( new ServiceNotFoundException( 'DisplayTitleLookup' ) );
		$this->store->expects( $this->any() )
			->method( 'getPropertyValues' )
			->willReturn( [ new Blob( 'Bar' ) ] );

		$instance = new DisplayTitleFinder( $this->store, $this->entityCache );
		$instance->prefetchFromList( [ $subject ] );

		$this->assertSame( 'Bar', $instance->findDisplayTitle( $subject ) );
	}

	public function testFindsNothingWhenDisplayTitlesAreOff() {
		$this->store->expects( $this->any() )
			->method( 'getPropertyValues' )
			->willReturn( [ new Blob( 'Bar' ) ] );

		$instance = new DisplayTitleFinder( $this->store, $this->entityCache );
		$instance->setCanUse( false );

		$this->assertSame( '', $instance->findDisplayTitle( WikiPage::newFromText( 'Foo' ) ) );
	}

	public function testFindDisplayTitleReadsTheStoreOnlyOnce() {
		$subject = WikiPage::newFromText( 'Foo' );

		$this->store->expects( $this->once() )
			->method( 'getPropertyValues' )
			->willReturn( [ new Blob( 'Bar' ) ] );

		$instance = new DisplayTitleFinder(
			$this->store,
			$this->entityCache
		);

		$instance->findDisplayTitle( $subject );

		$this->assertSame(
			'Bar',
			$instance->findDisplayTitle( $subject )
		);
	}

	public function testPrefetchedDisplayTitlesAreFoundWithoutReadingTheStore() {
		$subjects = [
			WikiPage::newFromText( 'Foo' ),
			WikiPage::doUnserialize( 'Foo#0##abc' ),
			WikiPage::doUnserialize( 'Foo#0##123' )
		];

		$this->givenPrefetchedDisplayTitles( [
			$subjects[2]->getSha1() => 'Bar',
			$subjects[0]->getSha1() => 'Foobar',
			$subjects[1]->getSha1() => null,
		] );

		$this->store->expects( $this->never() )
			->method( 'getPropertyValues' );

		$instance = new DisplayTitleFinder(
			$this->store,
			$this->entityCache
		);

		$instance->prefetchFromList( $subjects );

		$this->assertSame(
			[ 'Foobar', 'Foobar', 'Bar' ],
			array_map( [ $instance, 'findDisplayTitle' ], $subjects )
		);
	}

	public function testPrefetchedSubobjectWithoutDisplayTitleFindsTheDisplayTitleOfItsBase() {
		$subject = WikiPage::doUnserialize( 'Foo#0##abc' );

		$this->givenPrefetchedDisplayTitles( [
			$subject->getSha1() => null,
			$subject->asBase()->getSha1() => 'Bar',
		] );

		$this->store->expects( $this->never() )
			->method( 'getPropertyValues' );

		$instance = new DisplayTitleFinder(
			$this->store,
			$this->entityCache
		);

		$instance->prefetchFromList( [ $subject ] );

		$this->assertSame(
			'Bar',
			$instance->findDisplayTitle( $subject )
		);
	}

	public function testPrefetchingASubobjectAlsoPrefetchesItsPage() {
		$subject = WikiPage::doUnserialize( 'Foo#0##abc' );

		$this->givenPrefetchedDisplayTitles( [
			$subject->getSha1() => 'Bar',
			$subject->asBase()->getSha1() => 'Foobar',
		] );

		$this->store->expects( $this->never() )
			->method( 'getPropertyValues' );

		$instance = new DisplayTitleFinder(
			$this->store,
			$this->entityCache
		);

		$instance->prefetchFromList( [ $subject ] );

		$this->assertSame(
			'Foobar',
			$instance->findDisplayTitle( $subject->asBase() )
		);
	}

	public function testPrefetchLooksUpOnlyDisplayTitlesThatAreNotCached() {
		$this->store->expects( $this->any() )
			->method( 'getPropertyValues' )
			->willReturn( [] );

		$displayTitleLookup = $this->createMock( DisplayTitleLookup::class );

		$displayTitleLookup->expects( $this->once() )
			->method( 'prefetchFromList' )
			->with( [ WikiPage::newFromText( 'Bar' )->getSha1() => WikiPage::newFromText( 'Bar' ) ] )
			->willReturn( [] );

		$this->store->expects( $this->any() )
			->method( 'service' )
			->willReturn( $displayTitleLookup );

		$instance = new DisplayTitleFinder(
			$this->store,
			$this->entityCache
		);

		$instance->findDisplayTitle( WikiPage::newFromText( 'Foo' ) );
		$instance->prefetchFromList( [ WikiPage::newFromText( 'Foo' ), WikiPage::newFromText( 'Bar' ) ] );
	}

	private function givenPrefetchedDisplayTitles( array $prefetch ): void {
		$displayTitleLookup = $this->createMock( DisplayTitleLookup::class );

		$displayTitleLookup->expects( $this->any() )
			->method( 'prefetchFromList' )
			->willReturn( $prefetch );

		$this->store->expects( $this->any() )
			->method( 'service' )
			->with( 'DisplayTitleLookup' )
			->willReturn( $displayTitleLookup );
	}

}

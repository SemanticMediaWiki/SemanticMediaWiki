<?php

namespace SMW\Tests;

use SMW\DisplayTitleFinder;
use SMW\DIWikiPage;
use SMW\DIProperty;
use SMWDIBlob as DIBlob;

/**
 * @covers \SMW\DisplayTitleFinder
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since   3.1
 *
 * @author mwjames
 */
class DisplayTitleFinderTest extends \PHPUnit_Framework_TestCase {

	private $store;
	private $entityCache;

	protected function setUp() : void {

		$this->store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->setMethods( [ 'getWikiPageSortKey', 'service' ] )
			->getMockForAbstractClass();

		$this->entityCache = $this->getMockBuilder( '\SMW\EntityCache' )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			DisplayTitleFinder::class,
			new DisplayTitleFinder( $this->store, $this->entityCache )
		);
	}

	public function testFindDisplayTitle_WithoutSubobject() {

		$subject = DIWikiPage::newFromText( 'Foo' );

		$this->store->expects( $this->once() )
			->method( 'getPropertyValues' )
			->with( $this->equalTo( $subject ) )
			->will( $this->returnValue( [ new DIBlob( 'Bar' ) ] ) );

		$this->entityCache->expects( $this->once() )
			->method( 'makeKey' )
			->with(
				$this->equalTo( 'displaytitle' ),
				$this->equalTo( $subject->getHash() ) );

		$this->entityCache->expects( $this->once() )
			->method( 'fetch' )
			->will( $this->returnValue( false ) );

		$this->entityCache->expects( $this->once() )
			->method( 'save' );

		$this->entityCache->expects( $this->once() )
			->method( 'associate' );

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

		$subject = new DIWikiPage( 'Foo', NS_MAIN, '', 'abc' );

		$this->store->expects( $this->any() )
			->method( 'getPropertyValues' )
			->withConsecutive(
				[ $this->equalTo( $subject ) ],
				[ $this->equalTo( $subject->asBase() ) ] )
			->will( $this->onConsecutiveCalls( [], [ new DIBlob( 'foobar' ) ] ) );

		$this->entityCache->expects( $this->once() )
			->method( 'makeKey' )
			->with(
				$this->equalTo( 'displaytitle' ),
				$this->equalTo( $subject->getHash() ) );

		$this->entityCache->expects( $this->once() )
			->method( 'fetch' )
			->will( $this->returnValue( false ) );

		$this->entityCache->expects( $this->once() )
			->method( 'save' )
			->with(
				$this->anything(),
				$this->equalTo( 'foobar' ) );

		$this->entityCache->expects( $this->once() )
			->method( 'associate' );

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

		$subject = new DIWikiPage( 'Foo', NS_MAIN, '', 'abc' );

		$this->store->expects( $this->any() )
			->method( 'getPropertyValues' )
			->withConsecutive(
				[ $this->equalTo( $subject ) ],
				[ $this->equalTo( $subject->asBase() ) ] )
			->will( $this->onConsecutiveCalls( [], [] ) );

		$this->entityCache->expects( $this->once() )
			->method( 'makeKey' )
			->with(
				$this->equalTo( 'displaytitle' ),
				$this->equalTo( $subject->getHash() ) );

		$this->entityCache->expects( $this->once() )
			->method( 'fetch' )
			->will( $this->returnValue( false ) );

		// Stored with a space
		$this->entityCache->expects( $this->once() )
			->method( 'save' )
			->with(
				$this->anything(),
				$this->equalTo( ' ' ) );

		$this->entityCache->expects( $this->once() )
			->method( 'associate' );

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

	public function testPrefetchFromSemanticData() {

		$subSemanticData =  $this->getMockBuilder( '\SMW\SemanticData' )
			->disableOriginalConstructor()
			->getMock();

		$subSemanticData->expects( $this->atLeastOnce() )
			->method( 'getSubject' )
			->will( $this->returnValue( DIWikiPage::doUnserialize( 'Foo#0##123' ) ) );

		$subSemanticData->expects( $this->any() )
			->method( 'getProperties' )
			->will( $this->returnValue( [ new DIProperty( 'SubFoo' ) ] ) );

		$subSemanticData->expects( $this->any() )
			->method( 'getPropertyValues' )
			->will( $this->returnValue( [ DIWikiPage::newFromText( 'SubFoo' ) ] ) );

		$semanticData = $this->getMockBuilder( '\SMW\SemanticData' )
			->disableOriginalConstructor()
			->getMock();

		$semanticData->expects( $this->atLeastOnce() )
			->method( 'getSubject' )
			->will( $this->returnValue( new DIWikiPage( 'Bar', NS_MAIN ) ) );

		$semanticData->expects( $this->any() )
			->method( 'getProperties' )
			->will( $this->returnValue( [ new DIProperty( 'Foo' ) ] ) );

		$semanticData->expects( $this->any() )
			->method( 'getPropertyValues' )
			->will( $this->returnValue( [ DIWikiPage::newFromText( 'Foo' ) ] ) );

		$semanticData->expects( $this->any() )
			->method( 'getSubSemanticData' )
			->will( $this->returnValue( [ $subSemanticData ] ) );

		$prefetchList = [
			DIWikiPage::newFromText( 'Bar' ),
			DIWikiPage::newFromText( 'Foo' ),
			DIWikiPage::doUnserialize( 'Foo#0##123' ),
			DIWikiPage::newFromText( 'SubFoo' )
		];

 		$instance = $this->getMockBuilder( '\SMW\DisplayTitleFinder' )
 			->setConstructorArgs(
 				[
					$this->store,
					$this->entityCache
 				]
 			)
 			->setMethods( [ 'prefetchFromList' ] )
 			->getMock();

		$instance->expects( $this->any() )
			->method( 'prefetchFromList' )
			->with( $this->equalTo( $prefetchList ) );

		$instance->prefetchFromSemanticData( $semanticData );
	}

	public function testPrefetchFromList() {

		$subjects = [
			DIWikiPage::newFromText( 'Foo' ),
			DIWikiPage::doUnserialize( 'Foo#0##abc' ),
			DIWikiPage::doUnserialize( 'Foo#0##123' )
		];

		$prefetch = [
			$subjects[2]->getSha1() => 'Bar',
			$subjects[0]->getSha1() => 'Foobar',
			$subjects[1]->getSha1() => null,
		];

		$displayTitleLookup = $this->getMockBuilder( '\SMW\SQLStore\Lookup\DisplayTitleLookup' )
			->disableOriginalConstructor()
			->getMock();

		$displayTitleLookup->expects( $this->any() )
			->method( 'prefetchFromList' )
			->will( $this->returnValue( $prefetch ) );

		$this->store->expects( $this->any() )
			->method( 'service' )
			->with( $this->equalTo( 'DisplayTitleLookup' ) )
			->will( $this->returnValue( $displayTitleLookup ) );

		$this->entityCache->expects( $this->any() )
			->method( 'fetch' )
			->will( $this->returnValue( false ) );

		// Stored with a space
		$this->entityCache->expects( $this->any() )
			->method( 'save' )
			->withConsecutive(
				[ $this->anything(), $this->equalTo( 'Foobar' ) ],
				[ $this->anything(), $this->equalTo( 'Foobar' ) ],
				[ $this->anything(), $this->equalTo( 'Bar' ) ] );

		$this->entityCache->expects( $this->exactly( 3 ) )
			->method( 'associate' );

		$instance = new DisplayTitleFinder(
			$this->store,
			$this->entityCache
		);

		$instance->prefetchFromList( $subjects );
	}

	public function testPrefetchFromList_Subobject_Base() {

		$subjects = [
			DIWikiPage::doUnserialize( 'Foo#0##abc' ),
		];

		$prefetch = [
			$subjects[0]->getSha1() => null,
			$subjects[0]->asBase()->getSha1() => 'Bar',
		];

		$displayTitleLookup = $this->getMockBuilder( '\SMW\SQLStore\Lookup\DisplayTitleLookup' )
			->disableOriginalConstructor()
			->getMock();

		$displayTitleLookup->expects( $this->any() )
			->method( 'prefetchFromList' )
			->will( $this->returnValue( $prefetch ) );

		$this->store->expects( $this->any() )
			->method( 'service' )
			->with( $this->equalTo( 'DisplayTitleLookup' ) )
			->will( $this->returnValue( $displayTitleLookup ) );

		$this->entityCache->expects( $this->any() )
			->method( 'fetch' )
			->will( $this->returnValue( false ) );

		// Stored with a space
		$this->entityCache->expects( $this->any() )
			->method( 'save' )
			->withConsecutive(
				[ $this->anything(), $this->equalTo( 'Bar' ) ] );

		$this->entityCache->expects( $this->exactly( 2 ) )
			->method( 'associate' );

		$instance = new DisplayTitleFinder(
			$this->store,
			$this->entityCache
		);

		$instance->prefetchFromList( $subjects );
	}

}

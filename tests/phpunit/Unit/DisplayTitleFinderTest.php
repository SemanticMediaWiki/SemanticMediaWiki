<?php

namespace SMW\Tests;

use SMW\DisplayTitleFinder;
use SMW\DIWikiPage;
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

	protected function setUp() {

		$this->store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->setMethods( [ 'getWikiPageSortKey' ] )
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
			->method( 'save' );

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

}

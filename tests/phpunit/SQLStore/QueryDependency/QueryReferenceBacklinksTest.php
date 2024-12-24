<?php

namespace SMW\Tests\SQLStore\QueryDependency;

use SMW\DataItemFactory;
use SMW\RequestOptions;
use SMW\SQLStore\QueryDependency\QueryReferenceBacklinks;

/**
 * @covers \SMW\SQLStore\QueryDependency\QueryReferenceBacklinks
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class QueryReferenceBacklinksTest extends \PHPUnit\Framework\TestCase {

	private $dataItemFactory;

	protected function setUp(): void {
		parent::setUp();

		$this->dataItemFactory = new DataItemFactory();
	}

	protected function tearDown(): void {
		parent::tearDown();
	}

	public function testCanConstruct() {
		$queryDependencyLinksStore = $this->getMockBuilder( '\SMW\SQLStore\QueryDependency\QueryDependencyLinksStore' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			'\SMW\SQLStore\QueryDependency\QueryReferenceBacklinks',
			new QueryReferenceBacklinks( $queryDependencyLinksStore )
		);
	}

	public function testAddQueryReferenceBacklinksTo() {
		$subject = $this->dataItemFactory->newDIWikiPage( 'Bar', NS_MAIN, '', 'foobar' );

		$semanticData = $this->getMockBuilder( '\SMW\SemanticData' )
			->disableOriginalConstructor()
			->getMock();

		$semanticData->expects( $this->atLeastOnce() )
			->method( 'getSubject' )
			->willReturn( $subject );

		$semanticData->expects( $this->any() )
			->method( 'addPropertyObjectValue' )
			->with(
				$this->dataItemFactory->newDIProperty( '_ASK' ),
				$this->dataItemFactory->newDIWikiPage( 'Foo', NS_MAIN ) );

		$queryDependencyLinksStore = $this->getMockBuilder( '\SMW\SQLStore\QueryDependency\QueryDependencyLinksStore' )
			->disableOriginalConstructor()
			->getMock();

		$queryDependencyLinksStore->expects( $this->once() )
			->method( 'isEnabled' )
			->willReturn( true );

		$queryDependencyLinksStore->expects( $this->any() )
			->method( 'findEmbeddedQueryIdListBySubject' )
			->with( $subject )
			->willReturn( [ 'Foo#0##' => 42 ] );

		$queryDependencyLinksStore->expects( $this->once() )
			->method( 'findDependencyTargetLinksForSubject' )
			->willReturn( [ 'Foo#0##' ] );

		$instance = new QueryReferenceBacklinks(
			$queryDependencyLinksStore
		);

		$requestOptions = new RequestOptions();

		$this->assertTrue(
			$instance->addReferenceLinksTo( $semanticData, $requestOptions )
		);
	}

	public function testFindQueryReferenceBacklinks() {
		$subject = $this->dataItemFactory->newDIWikiPage( 'Bar', NS_MAIN, '', '' );

		$queryDependencyLinksStore = $this->getMockBuilder( '\SMW\SQLStore\QueryDependency\QueryDependencyLinksStore' )
			->disableOriginalConstructor()
			->getMock();

		$queryDependencyLinksStore->expects( $this->any() )
			->method( 'findDependencyTargetLinksForSubject' )
			->willReturn( [ 'Foo#0##' ] );

		$instance = new QueryReferenceBacklinks(
			$queryDependencyLinksStore
		);

		$requestOptions = new RequestOptions();

		$this->assertEquals(
			 [ 'Foo#0##' ],
			$instance->findReferenceLinks( $subject, $requestOptions )
		);
	}

	public function testInspectFurtherLinkRequirement() {
		$property = $this->dataItemFactory->newDIProperty( '_ASK' );
		$subject = $this->dataItemFactory->newDIWikiPage( 'Bar', NS_MAIN, '', '' );

		$queryDependencyLinksStore = $this->getMockBuilder( '\SMW\SQLStore\QueryDependency\QueryDependencyLinksStore' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new QueryReferenceBacklinks(
			$queryDependencyLinksStore
		);

		$html = '';

		$this->assertFalse(
			$instance->doesRequireFurtherLink( $property, $subject, $html )
		);
	}

}

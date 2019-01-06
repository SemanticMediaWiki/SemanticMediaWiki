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
class QueryReferenceBacklinksTest extends \PHPUnit_Framework_TestCase {

	private $dataItemFactory;

	protected function setUp() {
		parent::setUp();

		$this->dataItemFactory = new DataItemFactory();
	}

	protected function tearDown() {
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
			->will( $this->returnValue( $subject ) );

		$semanticData->expects( $this->any() )
			->method( 'addPropertyObjectValue' )
			->with(
				$this->equalTo( $this->dataItemFactory->newDIProperty( '_ASK' ) ),
				$this->equalTo( $this->dataItemFactory->newDIWikiPage( 'Foo', NS_MAIN ) ) );

		$queryDependencyLinksStore = $this->getMockBuilder( '\SMW\SQLStore\QueryDependency\QueryDependencyLinksStore' )
			->disableOriginalConstructor()
			->getMock();

		$queryDependencyLinksStore->expects( $this->once() )
			->method( 'isEnabled' )
			->will( $this->returnValue( true ) );

		$queryDependencyLinksStore->expects( $this->any() )
			->method( 'findEmbeddedQueryIdListBySubject' )
			->with( $this->equalTo( $subject ) )
			->will( $this->returnValue( [ 'Foo#0##' => 42 ] ) );

		$queryDependencyLinksStore->expects( $this->once() )
			->method( 'findDependencyTargetLinksForSubject' )
			->will( $this->returnValue( [ 'Foo#0##' ] ) );

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
			->will( $this->returnValue( [ 'Foo#0##' ] ) );

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

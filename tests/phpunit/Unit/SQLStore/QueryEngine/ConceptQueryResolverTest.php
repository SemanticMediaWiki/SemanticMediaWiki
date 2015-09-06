<?php

namespace SMW\Tests\SQLStore\QueryEngine;

use SMW\SQLStore\QueryEngine\ConceptQueryResolver;
use Title;

/**
 * @covers \SMW\SQLStore\QueryEngine\ConceptQueryResolver
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.2
 *
 * @author mwjames
 */
class ConceptQueryResolverTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$queryEngine = $this->getMockBuilder( '\SMW\SQLStore\QueryEngine\QueryEngine' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			'\SMW\SQLStore\QueryEngine\ConceptQueryResolver',
			new ConceptQueryResolver( $queryEngine )
		);
	}

	public function testPrepareQuerySegmentForNull() {

		$querySegmentListBuilder = $this->getMockBuilder( '\SMW\SQLStore\QueryEngine\QuerySegmentListBuilder' )
			->disableOriginalConstructor()
			->getMock();

		$querySegmentListItemResolver = $this->getMockBuilder( '\SMW\SQLStore\QueryEngine\QuerySegmentListItemResolver' )
			->disableOriginalConstructor()
			->getMock();

		$queryEngine = $this->getMockBuilder( '\SMW\SQLStore\QueryEngine\QueryEngine' )
			->disableOriginalConstructor()
			->getMock();

		$queryEngine->expects( $this->any() )
			->method( 'getQuerySegmentListBuilder' )
			->will( $this->returnValue( $querySegmentListBuilder ) );

		$queryEngine->expects( $this->any() )
			->method( 'getQuerySegmentListItemResolver' )
			->will( $this->returnValue( $querySegmentListItemResolver ) );

		$instance = new ConceptQueryResolver( $queryEngine );

		$this->assertNull(
			$instance->prepareQuerySegmentFor( '[[Foo]]' )
		);
	}

}

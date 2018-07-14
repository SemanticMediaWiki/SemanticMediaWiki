<?php

namespace SMW\Tests\SQLStore\QueryEngine;

use SMW\SQLStore\QueryEngine\QuerySegmentListProcessor;
use SMW\Tests\PHPUnitCompat;

/**
 * @covers \SMW\SQLStore\QueryEngine\QuerySegmentListProcessor
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.2
 *
 * @author mwjames
 */
class QuerySegmentListProcessorTest extends \PHPUnit_Framework_TestCase {

	use PHPUnitCompat;

	public function testCanConstruct() {

		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$temporaryTableBuilder = $this->getMockBuilder( '\SMW\SQLStore\TableBuilder\TemporaryTableBuilder' )
			->disableOriginalConstructor()
			->getMock();

		$hierarchyTempTableBuilder = $this->getMockBuilder( '\SMW\SQLStore\QueryEngine\HierarchyTempTableBuilder' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			'\SMW\SQLStore\QueryEngine\QuerySegmentListProcessor',
			new QuerySegmentListProcessor( $connection, $temporaryTableBuilder, $hierarchyTempTableBuilder )
		);
	}

	public function testTryResolveSegmentForInvalidIdThrowsException() {

		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$temporaryTableBuilder = $this->getMockBuilder( '\SMW\SQLStore\TableBuilder\TemporaryTableBuilder' )
			->disableOriginalConstructor()
			->getMock();

		$hierarchyTempTableBuilder = $this->getMockBuilder( '\SMW\SQLStore\QueryEngine\HierarchyTempTableBuilder' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new QuerySegmentListProcessor(
			$connection,
			$temporaryTableBuilder,
			$hierarchyTempTableBuilder
		);

		$this->setExpectedException( 'RuntimeException' );
		$instance->process( 42 );
	}

}

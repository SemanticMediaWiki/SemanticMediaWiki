<?php

namespace SMW\Tests\SQLStore\QueryEngine;

use SMW\SQLStore\QueryEngine\QuerySegmentListProcessor;

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

	public function testCanConstruct() {

		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$temporaryIdTableCreator = $this->getMockBuilder( '\SMW\SQLStore\TemporaryIdTableCreator' )
			->disableOriginalConstructor()
			->getMock();

		$hierarchyTempTableBuilder = $this->getMockBuilder( '\SMW\SQLStore\QueryEngine\HierarchyTempTableBuilder' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			'\SMW\SQLStore\QueryEngine\QuerySegmentListProcessor',
			new QuerySegmentListProcessor( $connection, $temporaryIdTableCreator, $hierarchyTempTableBuilder )
		);
	}

	public function testTryResolveSegmentForInvalidIdThrowsException() {

		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$temporaryIdTableCreator = $this->getMockBuilder( '\SMW\SQLStore\TemporaryIdTableCreator' )
			->disableOriginalConstructor()
			->getMock();

		$hierarchyTempTableBuilder = $this->getMockBuilder( '\SMW\SQLStore\QueryEngine\HierarchyTempTableBuilder' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new QuerySegmentListProcessor(
			$connection,
			$temporaryIdTableCreator,
			$hierarchyTempTableBuilder
		);

		$this->setExpectedException( 'RuntimeException' );
		$instance->doExecuteSubqueryJoinDependenciesFor( 42 );
	}

}

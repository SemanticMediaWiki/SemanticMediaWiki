<?php

namespace SMW\Tests\SQLStore\QueryEngine;

use SMW\SQLStore\QueryEngine\ConceptQuerySegmentBuilder;
use Title;

/**
 * @covers \SMW\SQLStore\QueryEngine\ConceptQuerySegmentBuilder
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.2
 *
 * @author mwjames
 */
class ConceptQuerySegmentBuilderTest extends \PHPUnit_Framework_TestCase {

	private $querySegmentListBuilder;
	private $querySegmentListProcessor;

	protected function setUp() {
		parent::setUp();

		$this->querySegmentListBuilder = $this->getMockBuilder( '\SMW\SQLStore\QueryEngine\QuerySegmentListBuilder' )
			->disableOriginalConstructor()
			->getMock();

		$this->querySegmentListProcessor = $this->getMockBuilder( '\SMW\SQLStore\QueryEngine\QuerySegmentListProcessor' )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			'\SMW\SQLStore\QueryEngine\ConceptQuerySegmentBuilder',
			new ConceptQuerySegmentBuilder( $this->querySegmentListBuilder, $this->querySegmentListProcessor )
		);
	}

	public function testGetQuerySegmentFromOnNull() {

		$instance = new ConceptQuerySegmentBuilder(
			$this->querySegmentListBuilder,
			$this->querySegmentListProcessor
		);

		$this->assertNull(
			$instance->getQuerySegmentFrom( '[[Foo]]' )
		);
	}

}

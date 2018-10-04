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
	private $queryParser;

	protected function setUp() {
		parent::setUp();

		$this->querySegmentListBuilder = $this->getMockBuilder( '\SMW\SQLStore\QueryEngine\QuerySegmentListBuilder' )
			->disableOriginalConstructor()
			->getMock();

		$this->querySegmentListProcessor = $this->getMockBuilder( '\SMW\SQLStore\QueryEngine\QuerySegmentListProcessor' )
			->disableOriginalConstructor()
			->getMock();

		$this->queryParser = $this->getMockBuilder( '\SMW\Query\Parser' )
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

		$description = $this->getMockBuilder( '\SMW\Query\Language\Description' )
			->disableOriginalConstructor()
			->getMock();

		$this->queryParser->expects( $this->any() )
			->method( 'getQueryDescription' )
			->will( $this->returnValue( $description ) );

		$instance = new ConceptQuerySegmentBuilder(
			$this->querySegmentListBuilder,
			$this->querySegmentListProcessor
		);

		$instance->setQueryParser(
			$this->queryParser
		);

		$this->assertNull(
			$instance->getQuerySegmentFrom( '[[Foo]]' )
		);
	}

}

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
class ConceptQuerySegmentBuilderTest extends \PHPUnit\Framework\TestCase {

	private $conditionBuilder;
	private $querySegmentListProcessor;
	private $queryParser;

	protected function setUp(): void {
		parent::setUp();

		$this->conditionBuilder = $this->getMockBuilder( '\SMW\SQLStore\QueryEngine\ConditionBuilder' )
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
			new ConceptQuerySegmentBuilder( $this->conditionBuilder, $this->querySegmentListProcessor )
		);
	}

	public function testGetQuerySegmentFromOnNull() {
		$description = $this->getMockBuilder( '\SMW\Query\Language\Description' )
			->disableOriginalConstructor()
			->getMock();

		$this->queryParser->expects( $this->any() )
			->method( 'getQueryDescription' )
			->willReturn( $description );

		$instance = new ConceptQuerySegmentBuilder(
			$this->conditionBuilder,
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

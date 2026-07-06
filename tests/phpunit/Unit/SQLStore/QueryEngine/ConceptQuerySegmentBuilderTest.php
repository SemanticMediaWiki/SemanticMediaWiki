<?php

namespace SMW\Tests\Unit\SQLStore\QueryEngine;

use PHPUnit\Framework\TestCase;
use SMW\Query\Language\Description;
use SMW\Query\Parser;
use SMW\SQLStore\QueryEngine\ConceptQuerySegmentBuilder;
use SMW\SQLStore\QueryEngine\ConditionBuilder;
use SMW\SQLStore\QueryEngine\QuerySegmentListProcessor;

/**
 * @covers \SMW\SQLStore\QueryEngine\ConceptQuerySegmentBuilder
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 2.2
 *
 * @author mwjames
 */
class ConceptQuerySegmentBuilderTest extends TestCase {

	private $conditionBuilder;
	private $querySegmentListProcessor;
	private $queryParser;

	protected function setUp(): void {
		parent::setUp();

		$this->conditionBuilder = $this->getMockBuilder( ConditionBuilder::class )
			->disableOriginalConstructor()
			->getMock();

		$this->querySegmentListProcessor = $this->getMockBuilder( QuerySegmentListProcessor::class )
			->disableOriginalConstructor()
			->getMock();

		$this->queryParser = $this->getMockBuilder( Parser::class )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			ConceptQuerySegmentBuilder::class,
			new ConceptQuerySegmentBuilder( $this->conditionBuilder, $this->querySegmentListProcessor )
		);
	}

	public function testGetQuerySegmentFromOnNull() {
		$description = $this->getMockBuilder( Description::class )
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

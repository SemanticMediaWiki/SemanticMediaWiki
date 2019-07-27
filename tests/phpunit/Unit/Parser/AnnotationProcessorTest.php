<?php

namespace SMW\Tests\Parser;

use SMW\Parser\LinksProcessor;
use SMW\Parser\AnnotationProcessor;

/**
 * @covers \SMW\Parser\AnnotationProcessor
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.1
 *
 * @author mwjames
 */
class AnnotationProcessorTest extends \PHPUnit_Framework_TestCase {

	private $semanticData;
	private $dataValueFactory;

	protected function setUp() {

		$this->semanticData = $this->getMockBuilder( 'SMW\SemanticData' )
			->disableOriginalConstructor()
			->getMock();

		$this->dataValueFactory = $this->getMockBuilder( 'SMW\DataValueFactory' )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			AnnotationProcessor::class,
			new AnnotationProcessor( $this->semanticData )
		);
	}

	public function testGetSemanticData() {

		$instance = new AnnotationProcessor(
			$this->semanticData,
			$this->dataValueFactory
		);

		$this->assertSame(
			$this->semanticData,
			$instance->getSemanticData()
		);
	}

	public function testCanAnnotate() {

		$instance = new AnnotationProcessor(
			$this->semanticData,
			$this->dataValueFactory
		);

		$this->assertTrue(
			$instance->canAnnotate()
		);

		$instance->setCanAnnotate( false );

		$this->assertFalse(
			$instance->canAnnotate()
		);
	}

	public function testNewDataValueByText() {

		$this->dataValueFactory->expects( $this->once() )
			->method( 'newDataValueByText' );

		$instance = new AnnotationProcessor(
			$this->semanticData,
			$this->dataValueFactory
		);

		$instance->newDataValueByText( 'Foo', 'Bar' );
	}

	public function testNewDataValueByItem() {

		$dataItem = $this->getMockBuilder( 'SMWDataItem' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$property = $this->getMockBuilder( 'SMW\DIProperty' )
			->disableOriginalConstructor()
			->getMock();

		$this->dataValueFactory->expects( $this->once() )
			->method( 'newDataValueByItem' );

		$instance = new AnnotationProcessor(
			$this->semanticData,
			$this->dataValueFactory
		);

		$instance->newDataValueByItem( $dataItem, $property );
	}

}
